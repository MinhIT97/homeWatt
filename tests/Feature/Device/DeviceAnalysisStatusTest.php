<?php

namespace Tests\Feature\Device;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Modules\Device\Jobs\ScanDevicePhotoJob;
use Tests\TestCase;

class DeviceAnalysisStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_read_their_own_device_analysis_status(): void
    {
        Queue::fake();

        $owner = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->actingAs($owner)->post(route('devices.analyze-image'), [
            'image' => UploadedFile::fake()->image('label.jpg', 200, 200),
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'async' => true,
            ]);

        $analysisId = $response->json('analysis_id');
        $this->assertIsString($analysisId);

        Queue::assertPushedOn('ai', ScanDevicePhotoJob::class);

        $this->actingAs($owner)
            ->get(route('devices.analysis.status', $analysisId))
            ->assertOk()
            ->assertJson([
                'status' => 'pending',
            ])
            ->assertJsonMissing([
                'user_id' => $owner->id,
            ]);

        $this->actingAs($other)
            ->get(route('devices.analysis.status', $analysisId))
            ->assertForbidden();
    }

    public function test_completed_device_analysis_status_does_not_leak_owner_id(): void
    {
        $owner = User::factory()->create();
        $analysisId = 'analysis_test';

        Cache::put("device_analysis:{$analysisId}", [
            'status' => 'completed',
            'success' => true,
            'user_id' => $owner->id,
            'data' => [
                'brand' => 'ACME',
            ],
        ], 3600);

        $this->actingAs($owner)
            ->get(route('devices.analysis.status', $analysisId))
            ->assertOk()
            ->assertJsonPath('data.brand', 'ACME')
            ->assertJsonMissing([
                'user_id' => $owner->id,
            ]);
    }
}
