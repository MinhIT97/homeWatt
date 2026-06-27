<?php

namespace Modules\Device\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Device\Models\Device;
use Modules\Room\Models\Room;

class DevicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Device $device): bool
    {
        $device->loadMissing('room.home.members');

        return $device->room->home->members->contains('user_id', $user->id);
    }

    public function create(User $user, ?Room $room = null): bool
    {
        if (! $room) {
            return false;
        }
        $room->loadMissing('home.members');
        $member = $room->home->members->firstWhere('user_id', $user->id);

        return $member && $member->canEdit();
    }

    public function update(User $user, Device $device): bool
    {
        $device->loadMissing('room.home.members');
        $member = $device->room->home->members->firstWhere('user_id', $user->id);

        return $member && $member->canEdit();
    }

    public function delete(User $user, Device $device): bool
    {
        $device->loadMissing('room.home.members');
        $member = $device->room->home->members->firstWhere('user_id', $user->id);

        return $member && $member->canEdit();
    }
}
