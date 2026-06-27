<?php

namespace Modules\Home\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;

class MemberService
{
    /**
     * Invite a user to a home with the given role.
     *
     * @return HomeMember The newly created membership
     *
     * @throws \RuntimeException When user is already a member
     */
    public function invite(Home $home, User $inviter, string $email, string $role): HomeMember
    {
        $inviterMember = $home->member($inviter->id);

        // Role hierarchy check: cannot invite users with a higher role
        if ($inviterMember
            && $role !== HomeMember::ROLE_VIEWER
            && ! $inviterMember->hasRoleAtLeast($role)
        ) {
            throw new \RuntimeException(__('home.cannot_invite_higher_role'));
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            throw new \RuntimeException(__('home.user_not_found'));
        }

        return DB::transaction(function () use ($home, $user, $role) {
            Home::where('id', $home->id)->lockForUpdate()->first();

            if ($home->isMember($user->id)) {
                throw new \RuntimeException(__('home.user_already_member'));
            }

            $membership = HomeMember::create([
                'home_id' => $home->id,
                'user_id' => $user->id,
            ]);
            $membership->assignRole($role);

            return $membership;
        });
    }

    /**
     * Remove a member from a home.
     *
     * @throws \RuntimeException When trying to remove the owner or self
     */
    public function remove(Home $home, HomeMember $member, User $actor): void
    {
        DB::transaction(function () use ($home, $member, $actor) {
            $locked = HomeMember::where('id', $member->id)
                ->where('home_id', $home->id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                abort(404);
            }

            if ($locked->role === HomeMember::ROLE_OWNER) {
                throw new \RuntimeException(__('common.cannot_remove_owner'));
            }

            if ($locked->user_id === $actor->id) {
                throw new \RuntimeException(__('home.cannot_remove_self'));
            }

            $locked->delete();
        });
    }

    /**
     * Create the owner membership for a newly created home.
     */
    public function createOwnerMembership(Home $home, User $owner): HomeMember
    {
        $membership = HomeMember::create([
            'home_id' => $home->id,
            'user_id' => $owner->id,
        ]);
        $membership->assignRole(HomeMember::ROLE_OWNER);

        return $membership;
    }
}
