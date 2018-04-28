<?php

namespace Febalist\Laravel\Media;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class MediaServiceProvider extends IlluminateServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/media.php');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/media.php', 'media');
        $this->publishes([
            __DIR__.'/../config/media.php' => config_path('media.php'),
        ]);
    }
}
