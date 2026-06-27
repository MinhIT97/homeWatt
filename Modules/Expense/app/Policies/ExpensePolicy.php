<?php

namespace Modules\Expense\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Expense\Models\Expense;
use Modules\Home\Models\Home;

class ExpensePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Expense $expense): bool
    {
        return $expense->home?->isMember($user->id) ?? false;
    }

    public function create(User $user, ?Home $home = null): bool
    {
        if (! $home) {
            return false;
        }
        $member = $home->member($user->id);

        return $member && $member->canEdit();
    }

    public function update(User $user, Expense $expense): bool
    {
        if ($expense->belongsToTransfer()) {
            return false;
        }

        $member = $expense->home?->member($user->id);

        return $member && $member->canEdit();
    }

    public function delete(User $user, Expense $expense): bool
    {
        if ($expense->belongsToTransfer()) {
            return false;
        }

        $member = $expense->home?->member($user->id);

        return $member && ($member->isEditor() || $expense->user_id === $user->id);
    }
}
