<?php

namespace Modules\Home\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Home\Models\Home;
use Modules\Home\Policies\HomePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class HomeServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Home';

    protected string $nameLower = 'home';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Home::class, HomePolicy::class);
    }
}
