<?php

namespace App\Providers;

use App\Repositories\EmployeeRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register repositories as singletons for performance
        $this->app->singleton(EmployeeRepository::class, function ($app) {
            return new EmployeeRepository();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
