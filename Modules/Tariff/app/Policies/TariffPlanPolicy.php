<?php

namespace Modules\Tariff\Policies;

use App\Models\User;
use Modules\Tariff\Models\TariffPlan;

class TariffPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TariffPlan $tariff): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->is_admin ?? false;
    }

    public function update(User $user, TariffPlan $tariff): bool
    {
        return ($user->is_admin ?? false) && ! $tariff->is_system;
    }

    public function delete(User $user, TariffPlan $tariff): bool
    {
        return ($user->is_admin ?? false) && ! $tariff->is_system;
    }
}
