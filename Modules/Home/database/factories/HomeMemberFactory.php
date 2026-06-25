<?php

namespace Modules\Home\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Home\Models\Home;
use Modules\Home\Models\HomeMember;

/**
 * @extends Factory<HomeMember>
 */
class HomeMemberFactory extends Factory
{
    protected $model = HomeMember::class;

    public function definition(): array
    {
        return [
            'home_id' => Home::factory(),
            'user_id' => User::factory(),
            'role' => HomeMember::ROLE_MEMBER,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn () => ['role' => HomeMember::ROLE_OWNER]);
    }

    public function manager(): static
    {
        return $this->state(fn () => ['role' => HomeMember::ROLE_MANAGER]);
    }

    public function member(): static
    {
        return $this->state(fn () => ['role' => HomeMember::ROLE_MEMBER]);
    }

    public function viewer(): static
    {
        return $this->state(fn () => ['role' => HomeMember::ROLE_VIEWER]);
    }
}