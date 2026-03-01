<?php

namespace App\Providers;

use App\Models\Document;
use App\Service\DocumentImportService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole() && Document::count() === 0) {
            DocumentImportService::importAllConfiguredDisks();
        }
    }
}
