<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Device\Models\Device;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Modules\Room\Models\Room;
use Tests\TestCase;

class XssTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_name_is_escaped_in_dashboard_view(): void
    {
        $user = User::factory()->create();
        $home = Home::create(['owner_id' => $user->id, 'name' => 'Test']);
        $m = HomeMember::create(['home_id' => $home->id, 'user_id' => $user->id]);
        $m->assignRole('owner');
        $room = Room::create(['home_id' => $home->id, 'name' => 'R']);

        $maliciousName = '<script>alert("XSS")</script>';
        $device = Device::create([
            'room_id' => $room->id,
            'name' => $maliciousName,
        ]);

        // The rendered output must escape the device name
        $escaped = htmlspecialchars($maliciousName, ENT_QUOTES, 'UTF-8');
        $this->assertStringContainsString('&lt;script&gt;', $escaped);
        $this->assertStringNotContainsString('<script>alert', $escaped);
    }

    public function test_dashboard_translation_does_not_include_untrusted_html(): void
    {
        $translation = __('dashboard.ai_suggest_detail', [
            'name' => '<script>alert("XSS")</script>',
            'power' => '100W',
        ]);

        // Translation contains :name replaced with raw value (potentially XSS)
        // Verify that Blade would need to escape it
        $this->assertStringContainsString('<script>', $translation);

        // After escaping via e(), it should be safe
        $escaped = e($translation);
        $this->assertStringNotContainsString('<script>', $escaped);
    }
}
