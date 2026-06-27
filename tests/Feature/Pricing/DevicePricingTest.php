<?php

namespace Tests\Feature\Pricing;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Device\Models\Device;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Room\Models\Room;
use Tests\TestCase;

class DevicePricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_device_with_purchase_price(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $home = new Home(['name' => 'H']);
        $home->forceFill(['owner_id' => $user->id])->save();
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $m->assignRole('owner');
        $room = Room::create(['home_id' => $home->id, 'name' => 'R']);

        $response = $this->actingAs($user)->post(route('devices.store'), [
            'room_id' => $room->id,
            'name' => 'AC',
            'purchase_price' => 8000000,
            'purchased_at' => '2025-01-15',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('devices', [
            'name' => 'AC',
            'purchase_price' => '8000000.00',
        ]);
    }

    public function test_negative_purchase_price_rejected(): void
    {
        $user = User::factory()->create();
        $home = new Home(['name' => 'H']);
        $home->forceFill(['owner_id' => $user->id])->save();
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $m->assignRole('owner');
        $room = Room::create(['home_id' => $home->id, 'name' => 'R']);

        $response = $this->actingAs($user)->post(route('devices.store'), [
            'room_id' => $room->id,
            'name' => 'AC',
            'purchase_price' => -1000,
        ]);

        $response->assertSessionHasErrors('purchase_price');
        $this->assertDatabaseCount('devices', 0);
    }

    public function test_device_purchase_price_is_optional(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $home = new Home(['name' => 'H']);
        $home->forceFill(['owner_id' => $user->id])->save();
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $m->assignRole('owner');
        $room = Room::create(['home_id' => $home->id, 'name' => 'R']);

        $response = $this->actingAs($user)->post(route('devices.store'), [
            'room_id' => $room->id,
            'name' => 'Lamp',
        ]);

        $response->assertRedirect();
        $device = Device::where('name', 'Lamp')->first();
        $this->assertNull($device->purchase_price);
    }

    public function test_user_can_update_device_purchase_price(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $home = new Home(['name' => 'H']);
        $home->forceFill(['owner_id' => $user->id])->save();
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $m->assignRole('owner');
        $room = Room::create(['home_id' => $home->id, 'name' => 'R']);
        $device = Device::create([
            'room_id' => $room->id,
            'name' => 'TV',
            'purchase_price' => 5000000,
        ]);

        $response = $this->actingAs($user)->put(route('devices.update', $device), [
            'name' => 'Smart TV',
            'purchase_price' => 7500000,
        ]);

        $response->assertRedirect();
        $device->refresh();
        $this->assertSame('Smart TV', $device->name);
        $this->assertEquals(7500000.0, (float) $device->purchase_price);
    }
}
