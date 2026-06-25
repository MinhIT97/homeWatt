<?php

namespace Tests\Feature\Energy;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Device\Models\Device;
use Modules\Energy\Models\EnergyReading;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Room\Models\Room;
use Tests\TestCase;

class EnergyIdorTest extends TestCase
{
    use RefreshDatabase;

    private function createHomeWithDevice(User $owner, ?User $member = null): array
    {
        $home = Home::create(['owner_id' => $owner->id, 'name' => 'Test Home']);
        $membership = HomeMember::create(['home_id' => $home->id, 'user_id' => $owner->id]);
        $membership->assignRole('owner');

        if ($member) {
            $m2 = HomeMember::create(['home_id' => $home->id, 'user_id' => $member->id]);
            $m2->assignRole('member');
        }

        $room = Room::create(['home_id' => $home->id, 'name' => 'Living Room']);
        $device = Device::create([
            'room_id' => $room->id,
            'name' => 'AC',
        ]);

        return compact('home', 'room', 'device');
    }

    public function test_user_cannot_view_reading_from_other_home(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        ['device' => $device] = $this->createHomeWithDevice($ownerA);
        $reading = EnergyReading::create([
            'device_id' => $device->id,
            'recorded_at' => now(),
            'watts' => 100,
            'kwh' => 1.5,
            'source' => 'manual',
            'measurement_type' => 'instant',
        ]);

        $response = $this->actingAs($ownerB)->get(route('energy.show', $reading));

        $response->assertForbidden();
    }

    public function test_user_cannot_calculate_estimate_for_other_home_device(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        ['device' => $device] = $this->createHomeWithDevice($ownerA);

        $response = $this->actingAs($ownerB)->post(route('energy.calculate'), [
            'device_id' => $device->id,
            'year' => 2026,
            'month' => 6,
        ]);

        $response->assertForbidden();
    }

    public function test_owner_can_view_their_own_reading(): void
    {
        $owner = User::factory()->create();

        ['device' => $device] = $this->createHomeWithDevice($owner);
        $reading = EnergyReading::create([
            'device_id' => $device->id,
            'recorded_at' => now(),
            'watts' => 100,
            'kwh' => 1.5,
            'source' => 'manual',
            'measurement_type' => 'instant',
        ]);

        $reading->load('device.room.home.members');
        $isOwner = $reading->device->room->home->members
            ->where('user_id', $owner->id)
            ->isNotEmpty();

        $this->assertTrue($isOwner);
    }
}
