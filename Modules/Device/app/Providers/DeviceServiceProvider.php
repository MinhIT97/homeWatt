<?php

namespace Modules\Device\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Device\Models\Device;
use Modules\Device\Policies\DevicePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class DeviceServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Device';

    protected string $nameLower = 'device';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();
        Gate::policy(Device::class, DevicePolicy::class);
    }
}
