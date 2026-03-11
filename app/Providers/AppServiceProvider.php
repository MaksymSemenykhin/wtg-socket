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
        // Force Reverb as broadcaster (avoids Pusher when config is cached or env is wrong)
        config(['broadcasting.default' => 'reverb']);
    }
}
