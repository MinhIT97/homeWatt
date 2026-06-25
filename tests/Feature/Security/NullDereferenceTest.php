<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Device\Models\Device;
use Modules\Energy\Models\EnergyReading;
use Modules\Energy\Policies\EnergyReadingPolicy;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Room\Models\Room;
use Tests\TestCase;

class NullDereferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_returns_false_when_device_relation_is_missing(): void
    {
        $user = User::factory()->create();
        $home = Home::create(['owner_id' => $user->id, 'name' => 'H']);
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $m->assignRole('owner');

        $reading = new EnergyReading([
            'device_id' => 99999,
            'recorded_at' => now(),
            'watts' => 100,
            'kwh' => 1.5,
            'source' => 'manual',
            'measurement_type' => 'instant',
        ]);

        $policy = new EnergyReadingPolicy;
        $result = $policy->view($user, $reading);

        $this->assertFalse($result);
    }

    public function test_policy_returns_false_when_room_relation_is_missing(): void
    {
        $user = User::factory()->create();
        $home = Home::create(['owner_id' => $user->id, 'name' => 'H']);
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $m->assignRole('owner');

        $room = Room::create(['home_id' => $home->id, 'name' => 'R']);

        // Create device but don't persist (so room_id is null)
        $device = new Device(['name' => 'D']);

        $reading = new EnergyReading([
            'recorded_at' => now(),
            'watts' => 100,
            'kwh' => 1.5,
            'source' => 'manual',
            'measurement_type' => 'instant',
        ]);
        $reading->setRelation('device', $device);

        $policy = new EnergyReadingPolicy;
        $result = $policy->view($user, $reading);

        $this->assertFalse($result);
    }
}
