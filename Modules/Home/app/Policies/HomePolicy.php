<?php

namespace Modules\Home\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Home\Models\Home;

class HomePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Home $home): bool
    {
        return $home->isMember($user->id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Home $home): bool
    {
        $member = $home->member($user->id);

        return $member && $member->canEdit();
    }

    public function delete(User $user, Home $home): bool
    {
        return $home->owner_id === $user->id;
    }

    public function manageMembers(User $user, Home $home): bool
    {
        $member = $home->member($user->id);

        return $member && $member->canEdit();
    }
}
