<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Device\Models\Device;
use Modules\Device\Policies\DevicePolicy;
use Modules\Home\Models\Home;
use Modules\Home\Policies\HomePolicy;
use Modules\Media\Models\Media;
use Modules\Media\Policies\MediaPolicy;
use Modules\Room\Models\Room;
use Modules\Room\Policies\RoomPolicy;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Home::class, HomePolicy::class);
        Gate::policy(Room::class, RoomPolicy::class);
        Gate::policy(Device::class, DevicePolicy::class);
        Gate::policy(Media::class, MediaPolicy::class);
    }
}
