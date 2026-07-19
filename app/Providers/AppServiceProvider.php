<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Modules\Device\Models\Device;
use Modules\Home\Models\Home;
use Modules\Media\Models\Media;
use Modules\Media\Policies\MediaPolicy;
use Modules\Room\Models\Room;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Gate::policy(Media::class, MediaPolicy::class);

        Relation::morphMap([
            'device' => Device::class,
            'room' => Room::class,
            'home' => Home::class,
        ]);
    }
}
