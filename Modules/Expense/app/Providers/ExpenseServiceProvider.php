<?php

namespace Modules\Expense\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Expense\Models\Expense;
use Modules\Expense\Models\ExpenseCategory;
use Modules\Expense\Models\Transfer;
use Modules\Expense\Policies\ExpenseCategoryPolicy;
use Modules\Expense\Policies\ExpensePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ExpenseServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Expense';

    protected string $nameLower = 'expense';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Expense::class, ExpensePolicy::class);
        Gate::policy(ExpenseCategory::class, ExpenseCategoryPolicy::class);
        Gate::policy(Transfer::class, ExpensePolicy::class);
    }
}
