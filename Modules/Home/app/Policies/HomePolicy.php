<?php

namespace Modules\Home\Policies;

use App\Models\User;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Illuminate\Auth\Access\HandlesAuthorization;

class HomePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Home $home): bool
    {
        return $home->members()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Home $home): bool
    {
        $member = $home->members()->where('user_id', $user->id)->first();
        return $member && $member->canEdit();
    }

    public function delete(User $user, Home $home): bool
    {
        return $home->owner_id === $user->id;
    }

    public function manageMembers(User $user, Home $home): bool
    {
        $member = $home->members()->where('user_id', $user->id)->first();
        return $member && $member->canEdit();
    }
}
