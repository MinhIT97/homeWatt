<?php

namespace Tests\Feature\Energy;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Device\Models\Device;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Room\Models\Room;
use Tests\TestCase;

class SmartPlugSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function createDevice(): int
    {
        $user = User::factory()->create();
        $home = new Home(['name' => 'H']);
        $home->forceFill(['owner_id' => $user->id])->save();
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $m->assignRole('owner');
        $room = Room::create(['home_id' => $home->id, 'name' => 'R']);
        $device = Device::create(['room_id' => $room->id, 'name' => 'D']);

        return $device->id;
    }

    public function test_smartplug_requires_valid_bearer_token(): void
    {
        $deviceId = $this->createDevice();
        $response = $this->postJson('/api/v1/smartplug/reading', [
            'device_id' => $deviceId,
        ]);

        $response->assertStatus(401);
    }

    public function test_smartplug_rejects_invalid_bearer_token(): void
    {
        $deviceId = $this->createDevice();
        config(['services.smartplug.api_key' => 'correct-secret-key-12345']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer wrong-token',
            'Accept' => 'application/json',
        ])->postJson('/api/v1/smartplug/reading', [
            'device_id' => $deviceId,
        ]);

        $response->assertStatus(401);
    }

    public function test_smartplug_idempotency_key_prevents_duplicates(): void
    {
        $deviceId = $this->createDevice();
        config(['services.smartplug.api_key' => 'correct-secret-key-12345']);

        $headers = [
            'Authorization' => 'Bearer correct-secret-key-12345',
            'Accept' => 'application/json',
        ];

        $payload = [
            'device_id' => $deviceId,
            'watts' => 100,
            'kwh' => 0.1,
            'idempotency_key' => 'unique-key-123',
        ];

        $r1 = $this->withHeaders($headers)->postJson('/api/v1/smartplug/reading', $payload);
        $r1->assertStatus(201);

        $r2 = $this->withHeaders($headers)->postJson('/api/v1/smartplug/reading', $payload);
        $r2->assertStatus(200);
        $r2->assertJson(['status' => 'duplicate_ignored']);

        $this->assertDatabaseCount('energy_readings', 1);
    }
}
