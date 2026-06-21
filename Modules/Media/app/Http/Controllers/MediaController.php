<?php

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;

class MediaController extends \App\Http\Controllers\Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpeg,png,webp', 'max:20480'],
            'owner_type' => ['required', 'string'],
            'owner_id' => ['required', 'integer'],
        ]);

        $file = $request->file('file');

        $path = $file->store('media/' . date('Y/m'), 'private');

        $media = Media::create([
            'owner_type' => $request->input('owner_type'),
            'owner_id' => $request->input('owner_id'),
            'disk' => 'private',
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'status' => 'ready',
        ]);

        return back()->with('success', 'File uploaded.')
            ->with('media_id', $media->id);
    }

    public function serve(Media $media)
    {
        if (!Storage::disk($media->disk)->exists($media->path)) {
            abort(404);
        }

        return response()->file(
            Storage::disk($media->disk)->path($media->path),
            ['Content-Type' => $media->mime_type]
        );
    }

    public function destroy(Request $request, Media $media): RedirectResponse
    {
        $this->authorize('delete', $media);

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        return back()->with('success', 'File deleted.');
    }
}
