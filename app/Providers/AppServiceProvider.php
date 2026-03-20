<?php

namespace App\Providers;

use App\Contracts\AuthServiceInterface;
use App\Contracts\FileServiceInterface;
use App\Contracts\ImageServiceInterface;
use App\Services\AuthService;
use App\Services\FileService;
use App\Services\ImageService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public array $bindings = [
        AuthServiceInterface::class => AuthService::class,
        FileServiceInterface::class => FileService::class,
        ImageServiceInterface::class => ImageService::class,
    ];

    public function register(): void
    {
        $this->app->register(\Tymon\JWTAuth\Providers\LaravelServiceProvider::class);
    }

    public function boot(): void
    {
        //
    }
}
