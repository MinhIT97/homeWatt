<?php

namespace Modules\Room\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Room\Models\Room;

class RoomPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Room $room): bool
    {
        $room->loadMissing('home.members');

        return $room->home->members->contains('user_id', $user->id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Room $room): bool
    {
        $room->loadMissing('home.members');
        $member = $room->home->members->firstWhere('user_id', $user->id);

        return $member && $member->canEdit();
    }

    public function delete(User $user, Room $room): bool
    {
        $room->loadMissing('home.members');
        $member = $room->home->members->firstWhere('user_id', $user->id);

        return $member && $member->canEdit();
    }
}
