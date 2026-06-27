<?php

namespace Database\Factories\Modules\AI\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AI\Models\AiAnalysisRequest;

/**
 * @extends Factory<AiAnalysisRequest>
 */
class AiAnalysisRequestFactory extends Factory
{
    protected $model = AiAnalysisRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'media_id' => 1,
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'status' => 'pending',
            'attempts' => 0,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed']);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'error' => 'Test failure',
        ]);
    }
}
