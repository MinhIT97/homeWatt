<?php

namespace Modules\AI\Providers;

use Modules\AI\Contracts\DeviceImageAnalyzer;
use Modules\AI\Services\FakeDeviceImageAnalyzer;
use Modules\AI\Services\OpenAiVisionAnalyzer;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AIServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'AI';
    protected string $nameLower = 'ai';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->singleton(DeviceImageAnalyzer::class, function ($app) {
            if (config('ai.providers.fake.enabled', false) || $app->environment('testing')) {
                return new FakeDeviceImageAnalyzer;
            }
            return new OpenAiVisionAnalyzer;
        });
    }

    public function boot(): void
    {
        parent::boot();
        $this->mergeConfigFrom(module_path($this->name, 'config/ai.php'), 'ai');
    }
}
