<?php

namespace Tests\Feature\Device;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Room\Models\Room;
use Tests\TestCase;

class DeviceImportSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function createHomeWithRoom(User $owner): array
    {
        $home = new Home(['name' => 'Test Home']);
        $home->forceFill(['owner_id' => $owner->id])->save();

        $membership = HomeMember::create([
            'home_id' => $home->id,
            'user_id' => $owner->id,
        ]);
        $membership->assignRole('owner');

        $room = Room::create([
            'home_id' => $home->id,
            'name' => 'Living Room',
        ]);

        return compact('home', 'room');
    }

    public function test_user_cannot_import_devices_into_room_from_other_home(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        ['room' => $room] = $this->createHomeWithRoom($ownerA);
        $this->createHomeWithRoom($ownerB);

        $file = UploadedFile::fake()->createWithContent('devices.csv', "name\nAir Conditioner\n");

        $response = $this->actingAs($ownerB)->post(route('devices.import'), [
            'room_id' => $room->id,
            'file' => $file,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('devices', [
            'room_id' => $room->id,
            'name' => 'Air Conditioner',
        ]);
    }
}
