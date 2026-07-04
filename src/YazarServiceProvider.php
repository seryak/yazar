<?php

namespace Yazar;

use Illuminate\Support\ServiceProvider;
use Yazar\Console\Commands\BuildCommand;
use Yazar\Console\Commands\InstallCommand;

class YazarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/yazar.php', 'yazar');
    }

    public function boot(): void
    {
        foreach (config('yazar.disks', []) as $name => $diskConfig) {
            config(["filesystems.disks.$name" => $diskConfig]);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/yazar.php' => config_path('yazar.php'),
            ], 'yazar-config');

            $this->publishes([
                __DIR__.'/../stubs/views' => resource_path('views'),
            ], 'yazar-views');

            $this->publishes([
                __DIR__.'/../stubs/content' => base_path('_content'),
            ], 'yazar-content');

            $this->commands([
                BuildCommand::class,
                InstallCommand::class,
            ]);
        }
    }
}
