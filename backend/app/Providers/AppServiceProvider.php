<?php

namespace App\Providers;

use App\Repositories\TravelRequestRepository;
use App\Services\TravelRequestService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TravelRequestRepository::class);
        $this->app->singleton(TravelRequestService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
