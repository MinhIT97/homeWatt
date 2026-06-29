<?php

namespace Modules\Expense\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Expense\Models\Transfer;
use Modules\Home\Models\Home;

class TransferPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Transfer $transfer): bool
    {
        return $transfer->home?->isMember($user->id) ?? false;
    }

    public function create(User $user, ?Home $home = null): bool
    {
        if (! $home) {
            return false;
        }
        $member = $home->member($user->id);

        return $member && $member->canEdit();
    }

    public function update(User $user, Transfer $transfer): bool
    {
        $member = $transfer->home?->member($user->id);

        return $member && $member->canEdit();
    }

    public function delete(User $user, Transfer $transfer): bool
    {
        $member = $transfer->home?->member($user->id);

        return $member && ($member->isEditor() || $transfer->user_id === $user->id);
    }
}
