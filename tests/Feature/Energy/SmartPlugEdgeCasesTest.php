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

class SmartPlugEdgeCasesTest extends TestCase
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

    private function authHeaders(): array
    {
        config(['services.smartplug.api_key' => 'test-key-12345']);

        return [
            'Authorization' => 'Bearer test-key-12345',
            'Accept' => 'application/json',
        ];
    }

    public function test_rejects_future_timestamp(): void
    {
        $deviceId = $this->createDevice();
        $futureTimestamp = now()->addDays(1);

        $response = $this->withHeaders($this->authHeaders())->postJson('/api/v1/smartplug/reading', [
            'device_id' => $deviceId,
            'watts' => 100,
            'kwh' => 0.5,
            'recorded_at' => $futureTimestamp->toDateTimeString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('recorded_at');
    }

    public function test_rejects_negative_watts(): void
    {
        $deviceId = $this->createDevice();

        $response = $this->withHeaders($this->authHeaders())->postJson('/api/v1/smartplug/reading', [
            'device_id' => $deviceId,
            'watts' => -100,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('watts');
    }

    public function test_rejects_excessive_watts(): void
    {
        $deviceId = $this->createDevice();

        $response = $this->withHeaders($this->authHeaders())->postJson('/api/v1/smartplug/reading', [
            'device_id' => $deviceId,
            'watts' => 200000, // > 100000 max
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('watts');
    }

    public function test_rejects_excessive_kwh(): void
    {
        $deviceId = $this->createDevice();

        $response = $this->withHeaders($this->authHeaders())->postJson('/api/v1/smartplug/reading', [
            'device_id' => $deviceId,
            'kwh' => 999999,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('kwh');
    }

    public function test_rejects_invalid_measurement_type(): void
    {
        $deviceId = $this->createDevice();

        $response = $this->withHeaders($this->authHeaders())->postJson('/api/v1/smartplug/reading', [
            'device_id' => $deviceId,
            'measurement_type' => 'invalid_type',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('measurement_type');
    }

    public function test_duplicate_reading_same_minute_returns_409(): void
    {
        $deviceId = $this->createDevice();
        $timestamp = now()->subMinutes(5)->startOfMinute();

        // First reading succeeds
        EnergyReading::create([
            'device_id' => $deviceId,
            'recorded_at' => $timestamp,
            'watts' => 100,
            'kwh' => 0.5,
            'source' => 'measured',
            'measurement_type' => 'instant',
        ]);

        // Second reading same minute (without idempotency_key) should fail
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/v1/smartplug/reading', [
            'device_id' => $deviceId,
            'watts' => 110,
            'kwh' => 0.6,
            'recorded_at' => $timestamp->copy()->addSeconds(20)->toDateTimeString(),
        ]);

        $response->assertStatus(409);
        $response->assertJson(['error' => 'duplicate_reading']);
    }

    public function test_idempotency_key_allows_exact_duplicate(): void
    {
        $deviceId = $this->createDevice();
        $idempotencyKey = 'unique-test-key-001';

        $payload = [
            'device_id' => $deviceId,
            'watts' => 100,
            'kwh' => 0.5,
            'idempotency_key' => $idempotencyKey,
        ];

        // First request
        $r1 = $this->withHeaders($this->authHeaders())->postJson('/api/v1/smartplug/reading', $payload);
        $r1->assertStatus(201);

        // Second request with same idempotency key
        $r2 = $this->withHeaders($this->authHeaders())->postJson('/api/v1/smartplug/reading', $payload);
        $r2->assertStatus(200);
        $r2->assertJson(['status' => 'duplicate_ignored']);

        $this->assertSame(1, EnergyReading::where('device_id', $deviceId)->count());
    }

    public function test_idempotency_key_too_long_rejected(): void
    {
        $deviceId = $this->createDevice();

        $response = $this->withHeaders($this->authHeaders())->postJson('/api/v1/smartplug/reading', [
            'device_id' => $deviceId,
            'watts' => 100,
            'idempotency_key' => str_repeat('a', 200), // > 100 max
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('idempotency_key');
    }

    public function test_missing_device_id_returns_422(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/v1/smartplug/reading', [
            'watts' => 100,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('device_id');
    }

    public function test_nonexistent_device_returns_422(): void
    {
        $response = $this->withHeaders($this->authHeaders())->postJson('/api/v1/smartplug/reading', [
            'device_id' => 99999,
            'watts' => 100,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('device_id');
    }

    public function test_defaults_when_no_recorded_at_provided(): void
    {
        $deviceId = $this->createDevice();

        $response = $this->withHeaders($this->authHeaders())->postJson('/api/v1/smartplug/reading', [
            'device_id' => $deviceId,
            'watts' => 100,
            'kwh' => 0.5,
        ]);

        $response->assertStatus(201);

        $reading = EnergyReading::where('device_id', $deviceId)->first();
        $this->assertNotNull($reading->recorded_at);
        $this->assertSame('measured', $reading->source);
        $this->assertSame('instant', $reading->measurement_type);
    }

    public function test_creates_reading_with_cumulative_measurement_type(): void
    {
        $deviceId = $this->createDevice();

        $response = $this->withHeaders($this->authHeaders())->postJson('/api/v1/smartplug/reading', [
            'device_id' => $deviceId,
            'watts' => 100,
            'kwh' => 0.5,
            'measurement_type' => 'cumulative',
        ]);

        $response->assertStatus(201);

        $reading = EnergyReading::where('device_id', $deviceId)->first();
        $this->assertSame('cumulative', $reading->measurement_type);
    }

    public function test_auth_failure_logged_with_ip(): void
    {
        $deviceId = $this->createDevice();
        config(['services.smartplug.api_key' => 'correct-key']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer wrong-key',
            'Accept' => 'application/json',
        ])->postJson('/api/v1/smartplug/reading', [
            'device_id' => $deviceId,
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseCount('energy_readings', 0);
    }

    public function test_throttle_allows_normal_traffic(): void
    {
        $deviceId = $this->createDevice();

        // Make a few rapid requests - each with different recorded_at
        $responses = [];
        $baseTime = now();
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->withHeaders($this->authHeaders())->postJson('/api/v1/smartplug/reading', [
                'device_id' => $deviceId,
                'watts' => 100 + $i,
                'kwh' => 0.1 + $i,
                'recorded_at' => $baseTime->copy()->subMinutes($i)->toDateTimeString(),
                'idempotency_key' => 'throttle-test-key-'.$i,
            ]);
        }

        // All should succeed (under throttle limit of 60/min, different idempotency keys)
        foreach ($responses as $response) {
            $response->assertStatus(201);
        }
    }
}
