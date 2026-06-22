<?php

namespace Tests\Feature;

use Tests\TestCase;

class SystemStatusTest extends TestCase
{
    public function test_version_endpoint_exposes_current_release_without_caching(): void
    {
        config(['app.release' => 'test-release-sha']);

        $response = $this->getJson(route('version'));

        $response
            ->assertOk()
            ->assertExactJson([
                'application' => config('app.name'),
                'release' => 'test-release-sha',
            ]);

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
    }
}
