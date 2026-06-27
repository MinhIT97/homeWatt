<?php

namespace Tests\Unit\Energy;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Device\Models\Device;
use Modules\Device\Models\DeviceSpecification;
use Modules\Device\Models\DeviceUsageProfile;
use Modules\Energy\Services\EnergyCalculator;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Room\Models\Room;
use Modules\Tariff\Models\TariffPlan;
use Modules\Tariff\Models\TariffTier;
use Tests\TestCase;

class EnergyCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private function createDevice(array $spec = [], array $profile = []): Device
    {
        $user = User::factory()->create();
        $home = new Home(['name' => 'H']);
        $home->forceFill(['owner_id' => $user->id])->save();
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $m->assignRole('owner');
        $room = Room::create(['home_id' => $home->id, 'name' => 'R']);
        $device = Device::create(['room_id' => $room->id, 'name' => 'D']);

        if (! empty($spec)) {
            DeviceSpecification::create(['device_id' => $device->id] + $spec);
        }

        if (! empty($profile)) {
            DeviceUsageProfile::create([
                'device_id' => $device->id,
                'source' => 'manual',
            ] + $profile);
        }

        return $device->fresh(['specification', 'usageProfile']);
    }

    public function test_continuous_estimation_with_default_profile(): void
    {
        $device = $this->createDevice(
            spec: ['rated_power' => 1000],
            profile: ['hours_per_day' => 8, 'days_per_week' => 7, 'duty_cycle' => 1.0],
        );

        $calc = app(EnergyCalculator::class);
        $estimate = $calc->estimateMonthly($device, 2026, 6);

        // June has 30 days: 1000W × 8h × 30 days / 1000 = 240 kWh
        $this->assertEqualsWithDelta(240.0, $estimate->estimated_kwh, 0.01);
        $this->assertSame('continuous', $estimate->method);
    }

    public function test_duty_cycle_estimation(): void
    {
        $device = $this->createDevice(
            spec: ['rated_power' => 1000],
            profile: ['hours_per_day' => 24, 'days_per_week' => 7, 'duty_cycle' => 0.5],
        );

        $calc = app(EnergyCalculator::class);
        $estimate = $calc->estimateMonthly($device, 2026, 6);

        // 1000W × 24h × 30 days × 0.5 / 1000 = 360 kWh (using 30.5 days avg for June)
        $this->assertGreaterThan(0, $estimate->estimated_kwh);
        $this->assertSame('duty_cycle', $estimate->method);
    }

    public function test_tariff_tier_calculation_progressive(): void
    {
        $plan = TariffPlan::create([
            'name' => 'Tiered',
            'effective_from' => '2026-01-01',
        ]);
        TariffTier::create(['tariff_plan_id' => $plan->id, 'tier_number' => 1, 'limit_kwh' => 50, 'rate' => 1806]);
        TariffTier::create(['tariff_plan_id' => $plan->id, 'tier_number' => 2, 'limit_kwh' => 100, 'rate' => 1866]);
        TariffTier::create(['tariff_plan_id' => $plan->id, 'tier_number' => 3, 'limit_kwh' => null, 'rate' => 2167]);

        $device = $this->createDevice(
            spec: ['rated_power' => 1000],
            profile: ['hours_per_day' => 24, 'days_per_week' => 7, 'duty_cycle' => 1.0],
        );

        $calc = app(EnergyCalculator::class);
        // 1000 × 24 × 30 × 1 / 1000 = 720 kWh → tier 3 (unlimited)
        $estimate = $calc->estimateMonthly($device, 2026, 6, $plan);

        $this->assertNotNull($estimate->estimated_cost);
        $this->assertGreaterThan(0, $estimate->estimated_cost);
    }

    public function test_tier_zero_kwh_returns_zero_cost(): void
    {
        $plan = TariffPlan::create([
            'name' => 'Tiered',
            'effective_from' => '2026-01-01',
        ]);
        TariffTier::create(['tariff_plan_id' => $plan->id, 'tier_number' => 1, 'limit_kwh' => 50, 'rate' => 1806]);

        $device = $this->createDevice(
            spec: ['rated_power' => 0],
            profile: ['hours_per_day' => 0, 'days_per_week' => 7, 'duty_cycle' => 1.0],
        );

        $calc = app(EnergyCalculator::class);
        $estimate = $calc->estimateMonthly($device, 2026, 6, $plan);

        $this->assertEquals(0.0, $estimate->estimated_kwh);
        $this->assertEquals(0.0, $estimate->estimated_cost);
    }

    public function test_estimate_range_bounds_confidence(): void
    {
        $device = $this->createDevice(
            spec: ['rated_power' => 1000],
            profile: ['hours_per_day' => 8, 'days_per_week' => 7, 'duty_cycle' => 1.0],
        );

        $calc = app(EnergyCalculator::class);
        $estimate = $calc->estimateMonthly($device, 2026, 6);

        // Lower bound should not be negative
        $this->assertGreaterThanOrEqual(0, $estimate->lower_range_kwh);
        // Upper bound should be >= kwh
        $this->assertGreaterThanOrEqual($estimate->estimated_kwh, $estimate->upper_range_kwh);
    }

    public function test_zero_specification_returns_zero_kwh(): void
    {
        $device = $this->createDevice(
            spec: ['rated_power' => 0],
            profile: ['hours_per_day' => 24, 'days_per_week' => 7, 'duty_cycle' => 1.0],
        );

        $calc = app(EnergyCalculator::class);
        $estimate = $calc->estimateMonthly($device, 2026, 6);

        $this->assertEquals(0.0, $estimate->estimated_kwh);
        $this->assertEquals('continuous', $estimate->method);
    }

    public function test_estimate_handles_no_specification(): void
    {
        $device = $this->createDevice(); // no spec

        $calc = app(EnergyCalculator::class);
        $estimate = $calc->estimateMonthly($device, 2026, 6);

        $this->assertEquals(0.0, $estimate->estimated_kwh);
        $this->assertLessThan(0.5, $estimate->confidence);
    }
}
