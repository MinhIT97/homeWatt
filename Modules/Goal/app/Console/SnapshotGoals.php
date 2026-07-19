<?php

namespace Modules\Goal\Console;

use Illuminate\Console\Command;
use Modules\Goal\Models\Goal;

class SnapshotGoals extends Command
{
    protected $signature = 'goals:snapshot';
    protected $description = 'Take daily snapshots of active goals';

    public function handle(): void
    {
        Goal::where('status', 'active')
            ->whereDate('starts_at', '<=', now())
            ->whereDate('ends_at', '>=', now())
            ->chunkById(100, function ($goals) {
                foreach ($goals as $goal) {
                    $goal->recalculate();
                    $goal->snapshots()->firstOrCreate(
                        ['snapshot_date' => now()->toDateString()],
                        [
                            'current_amount' => $goal->current_amount,
                            'percentage' => $goal->percentage(),
                        ]
                    );

                    if ($goal->percentage() >= 100) {
                        $goal->forceFill([
                            'status' => 'completed',
                            'completed_at' => now(),
                        ])->save();
                    }
                }
            });

        $this->info('Goal snapshots taken successfully.');
    }
}
