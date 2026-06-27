<?php

namespace Modules\Wallet\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Home\Models\Home;
use Modules\Wallet\Models\Wallet;

class WalletPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Wallet $wallet): bool
    {
        return $wallet->isAccessibleBy($user);
    }

    public function create(User $user, ?Home $home = null): bool
    {
        if (! $home) {
            return false;
        }
        $member = $home->member($user->id);

        return $member && $member->canEdit();
    }

    public function update(User $user, Wallet $wallet): bool
    {
        $member = $wallet->home?->member($user->id);

        return $member && $member->canEdit();
    }

    public function delete(User $user, Wallet $wallet): bool
    {
        $member = $wallet->home?->member($user->id);

        return $member && $member->canEdit() && $member->isOwner();
    }

    public function archive(User $user, Wallet $wallet): bool
    {
        $member = $wallet->home?->member($user->id);

        return $member && $member->canEdit();
    }
}
