<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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
        // Globally disable lazy loading in production for performance
        Model::preventLazyLoading(!app()->isProduction());

        // Enable query log in local environment for debugging
        if (app()->isLocal()) {
            DB::enableQueryLog();
        }

        // Set a reasonable timeout for database queries to prevent long-running queries
        DB::statement('SET SESSION wait_timeout=60');

        // Adjust MySQL settings for performance when loading large datasets
        if (!app()->isLocal()) {
            DB::statement('SET SESSION innodb_buffer_pool_size=256M');
            DB::statement('SET SESSION innodb_flush_log_at_trx_commit=2');
        }
    }
}
