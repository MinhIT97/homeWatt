<?php

namespace Tests\Unit\Tariff;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tariff\Models\TariffPlan;
use Tests\TestCase;

class TariffPlanScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_scope_returns_only_active_current_plans(): void
    {
        TariffPlan::create(['name' => 'Future', 'status' => 'active', 'effective_from' => '2030-01-01']);
        TariffPlan::create(['name' => 'Past', 'status' => 'active', 'effective_from' => '2020-01-01', 'effective_to' => '2020-12-31']);
        $current = TariffPlan::create(['name' => 'Current', 'status' => 'active', 'effective_from' => '2025-01-01']);
        TariffPlan::create(['name' => 'Inactive', 'status' => 'inactive', 'effective_from' => '2025-01-01']);

        $active = TariffPlan::active()->get();

        $this->assertCount(1, $active);
        $this->assertSame('Current', $active->first()->name);
    }

    public function test_effective_for_scope_with_specific_date(): void
    {
        TariffPlan::create(['name' => 'Q1', 'status' => 'active', 'effective_from' => '2025-01-01', 'effective_to' => '2025-03-31']);
        TariffPlan::create(['name' => 'Q2', 'status' => 'active', 'effective_from' => '2025-04-01', 'effective_to' => '2025-06-30']);
        TariffPlan::create(['name' => 'Open', 'status' => 'active', 'effective_from' => '2024-01-01']);

        $april = TariffPlan::effectiveFor('2025-04-15')->get();
        $this->assertCount(2, $april); // Q2 and Open

        $march = TariffPlan::effectiveFor('2025-03-15')->get();
        $this->assertCount(2, $march); // Q1 and Open

        $july = TariffPlan::effectiveFor('2025-07-15')->get();
        $this->assertCount(1, $july); // Open
    }

    public function test_find_effective_for_returns_most_recent(): void
    {
        TariffPlan::create(['name' => 'Old', 'status' => 'active', 'effective_from' => '2020-01-01', 'effective_to' => '2025-12-31']);
        TariffPlan::create(['name' => 'New', 'status' => 'active', 'effective_from' => '2025-01-01']);

        $plan = TariffPlan::findEffectiveFor('2025-06-15');
        $this->assertSame('New', $plan->name);
    }

    public function test_find_effective_for_with_region_filter(): void
    {
        TariffPlan::create(['name' => 'Hanoi', 'region' => 'hn', 'status' => 'active', 'effective_from' => '2025-01-01']);
        TariffPlan::create(['name' => 'Saigon', 'region' => 'sg', 'status' => 'active', 'effective_from' => '2025-01-01']);

        $plan = TariffPlan::findEffectiveFor('2025-06-15', 'hn');
        $this->assertSame('Hanoi', $plan->name);

        $plan = TariffPlan::findEffectiveFor('2025-06-15', 'sg');
        $this->assertSame('Saigon', $plan->name);

        $plan = TariffPlan::findEffectiveFor('2025-06-15', 'unknown');
        $this->assertNull($plan);
    }
}
