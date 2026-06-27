<?php

namespace Modules\Media\Http\Controllers;

use App\Http\Concerns\AuthorizesHomeResource;
use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;

class MediaController extends Controller
{
    use AuthorizesHomeResource;

    private const OWNER_TYPES = ['device', 'room', 'home'];

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpeg,png,webp', 'max:20480'],
            'owner_type' => ['required', 'string', 'in:'.implode(',', self::OWNER_TYPES)],
            'owner_id' => ['required', 'integer'],
        ]);

        $homeId = $this->resolveHomeIdFromRequest($request);

        abort_unless($homeId !== null, 404);
        abort_unless($this->userCanEditHome($request->user(), $homeId), 403);

        $ownerType = $request->input('owner_type');
        $ownerId = (int) $request->input('owner_id');
        $file = $request->file('file');

        $media = DB::transaction(function () use ($file, $ownerType, $ownerId) {
            $storedPath = $file->store('media/'.date('Y/m'), 'private');

            return Media::create([
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'disk' => 'private',
                'path' => $storedPath,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'status' => 'ready',
            ]);
        });

        AuditLogger::log('media.uploaded', [
            'media_id' => $media->id,
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'size' => $media->size,
            'mime_type' => $media->mime_type,
        ]);

        return back()->with('success', __('messages.file_uploaded'))
            ->with('media_id', $media->id);
    }

    public function serve(Request $request, Media $media)
    {
        logger()->debug('Media serve request received', [
            'url' => $request->fullUrl(),
            'media_id' => $media->id,
            'user_id' => $request->user()?->id,
        ]);

        if (! $request->hasValidSignature()) {
            logger()->warning('Media serve: invalid signature', [
                'url' => $request->fullUrl(),
                'expected_key' => config('app.key'),
            ]);
            abort(403, __('messages.invalid_signature'));
        }

        try {
            $this->authorize('view', $media);
        } catch (\Exception $e) {
            logger()->warning('Media serve: authorization failed', [
                'user_id' => $request->user()?->id,
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        if (! Storage::disk($media->disk)->exists($media->path)) {
            logger()->warning('Media serve: file not found on disk', [
                'disk' => $media->disk,
                'path' => $media->path,
            ]);
            abort(404);
        }

        logger()->debug('Media serve: serving file successfully', [
            'path' => Storage::disk($media->disk)->path($media->path),
        ]);

        return response()->file(
            Storage::disk($media->disk)->path($media->path),
            [
                'Content-Type' => $media->mime_type,
                'Cache-Control' => 'private, max-age=300',
            ]
        );
    }

    public function destroy(Request $request, Media $media): RedirectResponse
    {
        $this->authorize('delete', $media);

        $mediaId = $media->id;
        $ownerType = $media->owner_type;
        $ownerId = $media->owner_id;

        DB::transaction(function () use ($media) {
            $lockedMedia = Media::where('id', $media->id)->lockForUpdate()->first();
            if (! $lockedMedia) {
                return;
            }

            Storage::disk($lockedMedia->disk)->delete($lockedMedia->path);
            $lockedMedia->delete();
        });

        AuditLogger::log('media.deleted', [
            'media_id' => $mediaId,
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
        ]);

        return back()->with('success', __('messages.file_deleted'));
    }
}
