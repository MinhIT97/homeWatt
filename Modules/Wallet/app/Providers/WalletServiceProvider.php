<?php

namespace Modules\Wallet\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Policies\WalletPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class WalletServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Wallet';

    protected string $nameLower = 'wallet';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Wallet::class, WalletPolicy::class);
    }
}
