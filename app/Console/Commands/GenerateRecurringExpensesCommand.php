<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Expense\Services\QuickEntryService;

class GenerateRecurringExpensesCommand extends Command
{
    protected $signature = 'expenses:generate-recurring';

    protected $description = 'Generate due recurring income and expense transactions';

    public function handle(QuickEntryService $quickEntryService): int
    {
        $count = $quickEntryService->generateDueRecurring();

        $this->info("Generated {$count} recurring transaction(s).");

        return self::SUCCESS;
    }
}
