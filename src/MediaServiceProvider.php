<?php

namespace Febalist\Laravel\Media;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class MediaServiceProvider extends IlluminateServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/media.php');
        $this->loadMigrationsFrom(__DIR__.'/../migrations');
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\MediaConvert::class,
                Commands\MediaMove::class,
                Commands\MediaClear::class,
                Commands\MediaDelete::class,
                Commands\MediaUpdateSha1::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/media.php', 'media');
        $this->publishes([
            __DIR__.'/../config/media.php' => config_path('media.php'),
        ]);
    }
}
