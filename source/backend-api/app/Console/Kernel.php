<?php

namespace App\Console;

use App\Jobs\SyncEmployeeData;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run the employee data sync job every day at midnight
        $schedule->job(new SyncEmployeeData())->dailyAt('00:00')
            ->name('sync-employee-data')
            ->withoutOverlapping()
            ->onFailure(function () {
                Log::error('Employee data sync job failed during scheduled run');
            });

        // Run the database optimization command weekly
        $schedule->command('db:optimize')->weekly()->sundays()->at('01:00')
            ->name('optimize-database')
            ->withoutOverlapping()
            ->runInBackground();

        // Warm up cache daily
        $schedule->command('cache:query warmup')->dailyAt('04:00')
            ->name('warmup-query-cache')
            ->withoutOverlapping()
            ->runInBackground();

        // Clear expired cache entries daily
        $schedule->command('cache:prune-stale')->dailyAt('03:00')
            ->name('prune-stale-cache')
            ->runInBackground();

        // Run queue monitoring and restart if needed
        $schedule->command('horizon:snapshot')->everyFiveMinutes()
            ->name('horizon-snapshot')
            ->runInBackground();

        // Clean failed jobs table weekly
        $schedule->command('queue:prune-failed --hours=168')->weekly() // 168 hours = 1 week
            ->name('prune-failed-jobs')
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
