<?php

namespace App\Providers;

use App\Contracts\Repositories\SuccessfulEmailRepositoryInterface;
use App\Repositories\SuccessfulEmailRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SuccessfulEmailRepositoryInterface::class, SuccessfulEmailRepository::class);    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
