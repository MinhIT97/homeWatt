<?php

namespace Modules\Energy\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Energy\Models\EnergyReading;
use Modules\Home\Models\Home;

class EnergyReadingPolicy
{
    use HandlesAuthorization;

    public function view(User $user, EnergyReading $reading): bool
    {
        $home = $this->resolveHome($reading);

        return $home !== null && $home->isMember($user->id);
    }

    public function create(User $user, EnergyReading $reading): bool
    {
        return $this->view($user, $reading);
    }

    public function update(User $user, EnergyReading $reading): bool
    {
        $home = $this->resolveHome($reading);
        if ($home === null) {
            return false;
        }

        $member = $home->member($user->id);

        return $member && $member->canEdit();
    }

    public function delete(User $user, EnergyReading $reading): bool
    {
        return $this->update($user, $reading);
    }

    private function resolveHome(EnergyReading $reading): ?Home
    {
        $device = $reading->device;
        if (! $device || ! $device->room || ! $device->room->home) {
            return null;
        }

        return $device->room->home;
    }
}
