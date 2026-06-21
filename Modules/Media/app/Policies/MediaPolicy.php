<?php

namespace Modules\Media\Policies;

use App\Models\User;
use Modules\Media\Models\Media;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Media $media): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, Media $media): bool
    {
        return true;
    }
}
