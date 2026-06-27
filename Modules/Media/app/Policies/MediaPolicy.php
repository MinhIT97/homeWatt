<?php

namespace Modules\Media\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Device\Models\Device;
use Modules\Media\Models\Media;

class MediaPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Media $media): bool
    {
        return $media->owner_type === 'device'
            && $media->owner
            && $media->owner->room
            && $media->owner->room->home
            && $media->owner->room->home->members()
                ->where('user_id', $user->id)
                ->exists();
    }

    public function create(User $user, ?Device $device = null): bool
    {
        if (! $device) {
            return false;
        }
        if (! $device->room) {
            return false;
        }

        return $device->room->home->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'manager'])
            ->exists();
    }

    public function delete(User $user, Media $media): bool
    {
        if (! $media->owner || ! $media->owner->room) {
            return false;
        }

        return $media->owner->room->home->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'manager'])
            ->exists();
    }
}
