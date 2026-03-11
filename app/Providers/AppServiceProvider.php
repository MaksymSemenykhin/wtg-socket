<?php

namespace App\Providers;

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
        // Force Reverb as broadcaster only when configured (CI uses BROADCAST_CONNECTION=log)
        if (config('broadcasting.connections.reverb.key')) {
            config(['broadcasting.default' => 'reverb']);
        }
    }
}
