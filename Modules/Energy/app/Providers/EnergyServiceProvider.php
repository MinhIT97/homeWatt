<?php

namespace Modules\Energy\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Energy\Models\EnergyReading;
use Modules\Energy\Policies\EnergyReadingPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class EnergyServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Energy';

    protected string $nameLower = 'energy';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(EnergyReading::class, EnergyReadingPolicy::class);
    }
}
