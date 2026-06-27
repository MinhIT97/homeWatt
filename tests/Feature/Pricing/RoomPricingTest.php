<?php

namespace Tests\Feature\Pricing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Device\Models\Device;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Room\Models\Room;
use Tests\TestCase;

class RoomPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_room_with_price(): void
    {
        $user = User::factory()->create();
        $home = new Home(['name' => 'H']);
        $home->forceFill(['owner_id' => $user->id])->save();
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $m->assignRole('owner');

        $response = $this->actingAs($user)->post(route('rooms.store'), [
            'home_id' => $home->id,
            'name' => 'Master Bedroom',
            'type' => 'bedroom',
            'floor' => 2,
            'price' => 3000000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('rooms', [
            'name' => 'Master Bedroom',
            'price' => '3000000.00',
        ]);
    }

    public function test_negative_room_price_rejected(): void
    {
        $user = User::factory()->create();
        $home = new Home(['name' => 'H']);
        $home->forceFill(['owner_id' => $user->id])->save();
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $m->assignRole('owner');

        $response = $this->actingAs($user)->post(route('rooms.store'), [
            'home_id' => $home->id,
            'name' => 'Test Room',
            'price' => -500,
        ]);

        $response->assertSessionHasErrors('price');
        $this->assertDatabaseCount('rooms', 0);
    }

    public function test_room_price_is_optional(): void
    {
        $user = User::factory()->create();
        $home = new Home(['name' => 'H']);
        $home->forceFill(['owner_id' => $user->id])->save();
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $m->assignRole('owner');

        $response = $this->actingAs($user)->post(route('rooms.store'), [
            'home_id' => $home->id,
            'name' => 'Free Room',
        ]);

        $response->assertRedirect();
        $room = Room::where('name', 'Free Room')->first();
        $this->assertNull($room->price);
    }

    public function test_user_can_update_room_price(): void
    {
        $user = User::factory()->create();
        $home = new Home(['name' => 'H']);
        $home->forceFill(['owner_id' => $user->id])->save();
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $m->assignRole('owner');
        $room = Room::create([
            'home_id' => $home->id,
            'name' => 'Kitchen',
            'price' => 1500000,
        ]);

        $response = $this->actingAs($user)->put(route('rooms.update', $room), [
            'name' => 'Updated Kitchen',
            'price' => 2500000,
        ]);

        $response->assertRedirect();
        $room->refresh();
        $this->assertSame('Updated Kitchen', $room->name);
        $this->assertEquals(2500000.0, (float) $room->price);
    }

    public function test_home_show_displays_total_price(): void
    {
        $user = User::factory()->create();
        $home = Home::create([
            'owner_id' => $user->id,
            'name' => 'Price Home',
            'currency' => 'VND',
        ]);
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $m->assignRole('owner');

        $room1 = Room::create(['home_id' => $home->id, 'name' => 'R1', 'price' => 2000000]);
        $room2 = Room::create(['home_id' => $home->id, 'name' => 'R2', 'price' => 3000000]);
        Device::create(['room_id' => $room1->id, 'name' => 'D1', 'purchase_price' => 5000000]);

        $response = $this->actingAs($user)->get(route('homes.show', $home));

        $response->assertOk();
        $response->assertViewHas('priceSummary', function ($summary) {
            return $summary['rooms'] === 5000000.0
                && $summary['devices'] === 5000000.0
                && $summary['total'] === 10000000.0
                && $summary['room_count'] === 2
                && $summary['device_count'] === 1;
        });
    }
}
