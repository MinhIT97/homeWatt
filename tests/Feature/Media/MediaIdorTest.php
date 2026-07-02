<?php

namespace Tests\Feature\Media;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Device\Models\Device;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Media\Models\Media;
use Modules\Room\Models\Room;
use Tests\TestCase;

class MediaIdorTest extends TestCase
{
    use RefreshDatabase;

    private function setupHomeWithDevice(User $owner): array
    {
        $home = new Home(['name' => 'Test']);
        $home->forceFill(['owner_id' => $owner->id])->save();
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $owner->id]);
        $m->assignRole('owner');
        $room = Room::create(['home_id' => $home->id, 'name' => 'R']);
        $device = Device::create(['room_id' => $room->id, 'name' => 'D']);

        return compact('home', 'room', 'device');
    }

    public function test_user_cannot_upload_media_to_other_homes_device(): void
    {
        Storage::fake('private');
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        ['device' => $deviceA] = $this->setupHomeWithDevice($ownerA);

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($ownerB)->post(route('media.store'), [
            'file' => $file,
            'owner_type' => 'device',
            'owner_id' => $deviceA->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('media', 0);
    }

    public function test_user_can_upload_media_to_their_own_device(): void
    {
        Storage::fake('private');
        $owner = User::factory()->create();
        ['device' => $device] = $this->setupHomeWithDevice($owner);

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($owner)->post(route('media.store'), [
            'file' => $file,
            'owner_type' => 'device',
            'owner_id' => $device->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('media', 1);
    }

    public function test_owner_type_must_be_in_whitelist(): void
    {
        Storage::fake('private');
        $owner = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($owner)->post(route('media.store'), [
            'file' => $file,
            'owner_type' => 'random_table',
            'owner_id' => 1,
        ]);

        $response->assertSessionHasErrors('owner_type');
    }

    public function test_room_media_can_be_served_by_home_member_only(): void
    {
        Storage::fake('private');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        ['room' => $room] = $this->setupHomeWithDevice($owner);

        $response = $this->actingAs($owner)->post(route('media.store'), [
            'file' => UploadedFile::fake()->image('room.jpg'),
            'owner_type' => 'room',
            'owner_id' => $room->id,
        ]);

        $response->assertRedirect();
        $media = Media::first();
        $this->assertNotNull($media);

        $this->actingAs($owner)
            ->get($media->url())
            ->assertOk();

        $this->actingAs($other)
            ->get($media->url())
            ->assertForbidden();
    }
}
