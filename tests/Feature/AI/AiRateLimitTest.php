<?php

namespace Tests\Feature\AI;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Modules\AI\Models\AiAnalysisRequest;
use Modules\Device\Models\Device;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Media\Models\Media;
use Modules\Room\Models\Room;
use Tests\TestCase;

class AiRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function createUserWithMedia(): array
    {
        Storage::fake('private');
        $user = User::factory()->create();
        $home = Home::create(['owner_id' => $user->id, 'name' => 'H']);
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $m->assignRole('owner');
        $room = Room::create(['home_id' => $home->id, 'name' => 'R']);
        $device = Device::create(['room_id' => $room->id, 'name' => 'D']);

        $file = UploadedFile::fake()->image('test.jpg');
        $path = $file->store('media/test', 'private');
        $media = Media::create([
            'owner_type' => 'device',
            'owner_id' => $device->id,
            'disk' => 'private',
            'path' => $path,
            'mime_type' => 'image/jpeg',
            'size' => 100,
            'checksum' => 'abc',
            'status' => 'ready',
        ]);

        return compact('user', 'media');
    }

    public function test_user_can_make_analyses_under_limit(): void
    {
        ['user' => $user, 'media' => $media] = $this->createUserWithMedia();

        $response = $this->actingAs($user)->post(route('ai.analyses.store'), [
            'media_id' => $media->id,
        ]);

        $response->assertRedirect();
    }

    public function test_user_exceeding_per_user_limit_gets_429(): void
    {
        config(['ai.rate_limits.per_user_per_hour' => 2]);

        ['user' => $user, 'media' => $media] = $this->createUserWithMedia();

        // First request - creates pending
        $r1 = $this->actingAs($user)->post(route('ai.analyses.store'), [
            'media_id' => $media->id,
        ]);
        $r1->assertRedirect();

        // Mark the analysis completed so we can create a new one
        AiAnalysisRequest::where('user_id', $user->id)
            ->update(['status' => 'completed']);

        $r2 = $this->actingAs($user)->post(route('ai.analyses.store'), [
            'media_id' => $media->id,
        ]);
        $r2->assertRedirect();

        AiAnalysisRequest::where('user_id', $user->id)
            ->update(['status' => 'completed']);

        // Third request should be rate-limited
        $r3 = $this->actingAs($user)->postJson(route('ai.analyses.store'), [
            'media_id' => $media->id,
        ]);
        $r3->assertStatus(429);
        $r3->assertJson(['error' => 'rate_limit_exceeded']);
    }
}
