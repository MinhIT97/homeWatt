<?php

namespace Modules\Device\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Device\Models\Device;

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

    public function create(User $user): bool
    {
        return true;
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
