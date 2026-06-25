<?php

namespace Tests\Feature\Tariff;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tariff\Models\TariffPlan;
use Modules\Tariff\Models\TariffTier;
use Tests\TestCase;

class TariffAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_create_tariff_plan(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->post(route('tariff.store'), [
            'name' => 'Test Tariff',
            'effective_from' => '2026-01-01',
            'tiers' => [
                ['tier_number' => 1, 'limit_kwh' => 50, 'rate' => 1806],
            ],
        ]);

        $response->assertForbidden();
    }

    public function test_regular_user_cannot_delete_tariff_plan(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $tariff = TariffPlan::create([
            'name' => 'Existing',
            'effective_from' => '2026-01-01',
            'is_system' => false,
        ]);
        TariffTier::create([
            'tariff_plan_id' => $tariff->id,
            'tier_number' => 1,
            'rate' => 1000,
        ]);

        $response = $this->actingAs($user)->delete(route('tariff.destroy', $tariff));

        $response->assertForbidden();
    }

    public function test_admin_can_create_tariff_plan(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('tariff.store'), [
            'name' => 'Admin Tariff',
            'effective_from' => '2026-01-01',
            'tiers' => [
                ['tier_number' => 1, 'limit_kwh' => 50, 'rate' => 1806],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tariff_plans', ['name' => 'Admin Tariff']);
    }
}
