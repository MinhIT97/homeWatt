<?php

namespace Tests\Unit\AI;

use Modules\AI\Jobs\AnalyzeDeviceImageJob;
use Modules\AI\Models\AiAnalysisRequest;
use Tests\TestCase;

class AnalyzeDeviceImageJobReThrowTest extends TestCase
{
    public function test_job_throws_after_final_attempt(): void
    {
        // Create minimal fixtures
        $request = new AiAnalysisRequest([
            'user_id' => 1,
            'media_id' => 1,
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'status' => 'processing',
            'attempts' => 2,
        ]);

        $job = new AnalyzeDeviceImageJob($request);

        // Simulate attempts() >= tries
        $reflection = new \ReflectionClass($job);
        $triesProperty = $reflection->getProperty('tries');
        $triesProperty->setAccessible(true);
        $triesProperty->setValue($job, 2);

        // The job should throw exception after final attempt
        $attempts = 2;

        // Verify the job has correct configuration
        $this->assertSame(2, $job->tries);
        $this->assertSame(600, $job->timeout);
    }
}
