<?php

namespace Febalist\Laravel\Media;

use CreateMediaTable;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    public function boot()
    {
        if (!class_exists(CreateMediaTable::class)) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_media_table.php' => database_path('migrations/'.date('Y_m_d_His').'_create_media_table.php'),
            ], 'migrations');
        }

        $this->publishes([
            __DIR__.'/../config/media.php' => base_path('config/media.php'),
        ], 'config');
    }
}
