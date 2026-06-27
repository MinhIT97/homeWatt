<?php

namespace App\Http\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Device\Models\Device;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Room\Models\Room;

trait AuthorizesHomeResource
{
    /**
     * Resolve the home ID from a route-bound model (Device, Room, or Home).
     */
    protected function resolveHomeIdFromModel(Model $model): ?int
    {
        return match (true) {
            $model instanceof Home => $model->id,
            $model instanceof Room => $model->home_id,
            $model instanceof Device => $model->room?->home_id,
            default => null,
        };
    }

    /**
     * Resolve the home from request input (owner_type + owner_id or home_id).
     */
    protected function resolveHomeIdFromRequest(Request $request): ?int
    {
        $ownerType = $request->input('owner_type');
        $ownerId = $request->input('owner_id');

        if (! $ownerType || ! $ownerId) {
            return $request->input('home_id');
        }

        return match ($ownerType) {
            'device' => Device::where('id', $ownerId)->value('room_id')
                ? Room::where('id', Device::where('id', $ownerId)->value('room_id'))->value('home_id')
                : null,
            'room' => Room::where('id', $ownerId)->value('home_id'),
            'home' => Home::where('id', $ownerId)->exists() ? (int) $ownerId : null,
            default => null,
        };
    }

    /**
     * Verify the current user is a member of the given home.
     */
    protected function userCanAccessHome(User $user, int $homeId, array $roles = []): bool
    {
        $query = HomeMember::where('home_id', $homeId)
            ->where('user_id', $user->id);

        if (! empty($roles)) {
            $query->whereIn('role', $roles);
        }

        return $query->exists();
    }

    /**
     * Verify user can edit (is owner or manager) the given home.
     */
    protected function userCanEditHome(User $user, int $homeId): bool
    {
        return $this->userCanAccessHome($user, $homeId, ['owner', 'manager']);
    }
}
