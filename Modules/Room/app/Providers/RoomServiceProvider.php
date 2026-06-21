<?php

namespace Modules\Room\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Room\Models\Room;
use Modules\Room\Policies\RoomPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class RoomServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Room';

    protected string $nameLower = 'room';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Room::class, RoomPolicy::class);
    }
}
