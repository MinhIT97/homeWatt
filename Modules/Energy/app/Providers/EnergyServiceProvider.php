<?php

namespace Modules\Energy\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Energy\Console\CheckThresholds;
use Modules\Energy\Console\DetectAnomaliesCommand;
use Modules\Energy\Console\GenerateMonthlySummary;
use Modules\Energy\Console\SendBillReminders;
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

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckThresholds::class,
                DetectAnomaliesCommand::class,
                GenerateMonthlySummary::class,
                SendBillReminders::class,
            ]);
        }
    }
}
