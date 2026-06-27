<?php

namespace Modules\Expense\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Expense\Models\ExpenseCategory;

class ExpenseCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ExpenseCategory $category): bool
    {
        return $category->home?->isMember($user->id) ?? false;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ExpenseCategory $category): bool
    {
        $member = $category->home?->member($user->id);

        return $member && $member->canEdit();
    }

    public function delete(User $user, ExpenseCategory $category): bool
    {
        if ($category->is_system) {
            return false;
        }

        $member = $category->home?->member($user->id);

        return $member && $member->canEdit();
    }
}
