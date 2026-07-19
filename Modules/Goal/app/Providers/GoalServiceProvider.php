<?php

namespace Modules\Goal\Providers;

use Modules\Goal\Console\SnapshotGoals;
use Nwidart\Modules\Support\ModuleServiceProvider;

class GoalServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Goal';

    protected string $nameLower = 'goal';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([
                SnapshotGoals::class,
            ]);
        }
    }
}
