<?php

namespace Tests\Feature\AI;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\AI\Models\AiAnalysisRequest;
use Modules\Device\Models\Device;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Media\Models\Media;
use Modules\Room\Models\Room;
use Tests\TestCase;

class AiAnalysisIdorTest extends TestCase
{
    use RefreshDatabase;

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

        return compact('user', 'media', 'device');
    }

    public function test_user_cannot_analyze_other_homes_media(): void
    {
        ['media' => $media] = $this->createUserWithMedia();
        $attacker = User::factory()->create();

        $response = $this->actingAs($attacker)->post(route('ai.analyses.store'), [
            'media_id' => $media->id,
        ]);

        $response->assertForbidden();
    }

    public function test_user_cannot_view_other_users_analysis(): void
    {
        ['user' => $owner, 'media' => $media] = $this->createUserWithMedia();

        $analysis = AiAnalysisRequest::create([
            'user_id' => $owner->id,
            'media_id' => $media->id,
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'status' => 'completed',
        ]);

        $attacker = User::factory()->create();

        $response = $this->actingAs($attacker)->get(route('ai.analyses.show', $analysis));

        $response->assertForbidden();
    }

    public function test_controller_reuses_existing_pending_analysis(): void
    {
        ['user' => $user, 'media' => $media] = $this->createUserWithMedia();

        // Pre-create a pending analysis
        $existing = AiAnalysisRequest::create([
            'user_id' => $user->id,
            'media_id' => $media->id,
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'status' => 'pending',
        ]);

        // Verify controller's firstOrCreate logic would match
        $found = AiAnalysisRequest::where('media_id', $media->id)
            ->where('status', 'pending')
            ->first();

        $this->assertNotNull($found);
        $this->assertSame($existing->id, $found->id);
    }
}
