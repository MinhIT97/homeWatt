<?php

namespace Modules\Media\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Device\Models\Device;
use Modules\Home\Models\Home;
use Modules\Media\Models\Media;
use Modules\Room\Models\Room;

class MediaPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Media $media): bool
    {
        $home = $this->resolveOwnerHome($media);

        return $home?->members()
            ->where('user_id', $user->id)
            ->exists() ?? false;
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
        $home = $this->resolveOwnerHome($media);

        return $home?->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'manager'])
            ->exists() ?? false;
    }

    private function resolveOwnerHome(Media $media): ?Home
    {
        $owner = $media->owner;

        return match (true) {
            $owner instanceof Device => $owner->room?->home,
            $owner instanceof Room => $owner->home,
            $owner instanceof Home => $owner,
            default => null,
        };
    }
}
