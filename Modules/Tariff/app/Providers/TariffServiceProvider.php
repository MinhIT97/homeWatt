<?php

namespace Modules\Tariff\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Tariff\Models\TariffPlan;
use Modules\Tariff\Policies\TariffPlanPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class TariffServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Tariff';

    protected string $nameLower = 'tariff';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(TariffPlan::class, TariffPlanPolicy::class);
    }
}
