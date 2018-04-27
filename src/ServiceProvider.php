<?php

namespace Febalist\Laravel\Media;

use CreateMediaTable;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Route;

class ServiceProvider extends IlluminateServiceProvider
{
    public function boot()
    {
        if (!class_exists(CreateMediaTable::class)) {
            $this->publishes([
                __DIR__.'/migration.php' => database_path('migrations/'.date('Y_m_d_His').'_create_media_table.php'),
            ], 'migrations');
        }

        $this->publishes([
            __DIR__.'/config.php' => base_path('config/media.php'),
        ], 'config');

        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }
}
