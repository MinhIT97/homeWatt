<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;
use Tests\TestCase;

class MassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_change_home_status_via_mass_assignment(): void
    {
        $user = User::factory()->create();
        $home = new Home(['name' => 'My Home']);
        $home->forceFill(['owner_id' => $user->id])->save();
        $membership = HomeMember::create([
            'home_id' => $home->id,
            'user_id' => $user->id,
        ]);
        $membership->assignRole('owner');

        $response = $this->actingAs($user)
            ->withoutMiddleware([
                VerifyCsrfToken::class,
            ])
            ->patch(route('homes.update', $home), [
                'name' => 'New Name',
                'status' => 'inactive',
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect();
        $home->refresh();
        $this->assertSame('New Name', $home->name);
        $this->assertSame('active', $home->status);
    }

    public function test_user_cannot_promote_their_role_via_mass_assignment(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $home = Home::create([
            'owner_id' => $owner->id,
            'name' => 'My Home',
        ]);
        HomeMember::create([
            'home_id' => $home->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $existingRow = HomeMember::where('home_id', $home->id)
            ->where('user_id', $member->id)
            ->first();

        $this->assertNotNull($existingRow);
        $this->assertSame('member', $existingRow->role);

        HomeMember::unguard();
        try {
            HomeMember::create([
                'home_id' => $home->id,
                'user_id' => $member->id,
                'role' => 'owner',
            ]);
        } catch (\Throwable $e) {
            // duplicate unique key — expected
        }
        HomeMember::reguard();

        $roleAfter = HomeMember::where('home_id', $home->id)
            ->where('user_id', $member->id)
            ->value('role');

        $this->assertSame('member', $roleAfter);
    }

    public function test_home_member_role_attribute_is_not_mass_assignable(): void
    {
        $homeMember = new HomeMember;
        $fillable = $homeMember->getFillable();

        $this->assertNotContains('role', $fillable, 'role must not be mass-assignable to prevent privilege escalation');
    }

    public function test_home_status_attribute_is_not_mass_assignable(): void
    {
        $home = new Home;
        $fillable = $home->getFillable();

        $this->assertNotContains('status', $fillable, 'status must not be mass-assignable to prevent deactivation attack');
    }
}
