<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(\Tymon\JWTAuth\Providers\LaravelServiceProvider::class);
    }

    public function boot(): void
    {
        //
    }
}
