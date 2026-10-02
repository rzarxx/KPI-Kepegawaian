<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        // Force HTTPS scheme for all generated URLs in production.
        // This ensures asset(), route(), and redirect() never generate http:// URLs
        // even when PHP-FPM sits behind an SSL-terminating reverse proxy.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
