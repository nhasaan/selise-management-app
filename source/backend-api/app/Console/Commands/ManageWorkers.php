<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class ManageWorkers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workers:manage
                            {action : Action to perform (start|stop|restart|status)}
                            {--queue= : Specific queue to manage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage queue workers for the application';

    /**
     * The available queues in the system.
     *
     * @var array
     */
    protected $queues = [
        'default',
        'employee-operations',
        'employee-reports',
        'maintenance',
        'destructive-operations'
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $queue = $this->option('queue');

        if ($queue && !in_array($queue, $this->queues)) {
            $this->error("Invalid queue: {$queue}");
            $this->info("Available queues: " . implode(', ', $this->queues));
            return Command::FAILURE;
        }

        // If no specific queue is provided, apply to all queues
        $targetQueues = $queue ? [$queue] : $this->queues;

        switch ($action) {
            case 'start':
                $this->startWorkers($targetQueues);
                break;
            case 'stop':
                $this->stopWorkers($targetQueues);
                break;
            case 'restart':
                $this->restartWorkers($targetQueues);
                break;
            case 'status':
                $this->showStatus($targetQueues);
                break;
            default:
                $this->error("Invalid action: {$action}");
                $this->info("Available actions: start, stop, restart, status");
                return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Start workers for the specified queues.
     */
    private function startWorkers(array $queues): void
    {
        $this->info("Starting workers for queues: " . implode(', ', $queues));

        foreach ($queues as $queue) {
            $this->info("Starting workers for queue: {$queue}");

            // Determine worker count based on queue type
            $workerCount = $this->getWorkerCount($queue);

            // Start Horizon (if we're using it) or regular queue workers
            if (class_exists('Laravel\Horizon\Horizon')) {
                $this->info("Using Horizon to manage workers");
                Artisan::call('horizon', ['--queue' => $queue]);
            } else {
                $this->info("Starting {$workerCount} Laravel queue workers");

                // Start multiple worker processes
                for ($i = 0; $i < $workerCount; $i++) {
                    $process = new \Symfony\Component\Process\Process([
                        'php',
                        base_path('artisan'),
                        'queue:work',
                        '--queue=' . $queue,
                        '--tries=3',
                        '--backoff=5',
                        '--sleep=3',
                    ]);

                    $process->start();
                    $this->info("Started worker process #{$i} for queue: {$queue}");
                }
            }
        }

        $this->info("Workers started successfully");
    }

    /**
     * Stop workers for the specified queues.
     */
    private function stopWorkers(array $queues): void
    {
        $this->info("Stopping workers for queues: " . implode(', ', $queues));

        if (class_exists('Laravel\Horizon\Horizon')) {
            $this->info("Using Horizon to manage workers");
            Artisan::call('horizon:terminate');
            $this->info("Horizon workers terminated");
        } else {
            // This is a simplified approach - in production you would want
            // to track worker PIDs and terminate them properly
            $this->info("Sending STOP command to queue workers");

            foreach ($queues as $queue) {
                Artisan::call('queue:restart', ['--queue' => $queue]);
            }

            $this->info("Workers stop command sent");
        }
    }

    /**
     * Restart workers for the specified queues.
     */
    private function restartWorkers(array $queues): void
    {
        $this->info("Restarting workers for queues: " . implode(', ', $queues));

        // Stop existing workers
        $this->stopWorkers($queues);

        // Give workers time to finish current jobs
        $this->info("Waiting for workers to finish current jobs...");
        sleep(5);

        // Start new workers
        $this->startWorkers($queues);

        $this->info("Workers restarted successfully");
    }

    /**
     * Show status of workers for the specified queues.
     */
    private function showStatus(array $queues): void
    {
        $this->info("Queue Worker Status");
        $this->info("=================");

        // Display queue sizes
        foreach ($queues as $queue) {
            try {
                $size = Redis::connection()->command('LLEN', ["queues:{$queue}"]);
                $failed = Redis::connection()->command('LLEN', ["failed:{$queue}"]);

                $this->info("Queue: {$queue}");
                $this->info("  Jobs waiting: {$size}");
                $this->info("  Failed jobs: {$failed}");
                $this->info("  Status: " . ($size > 0 ? 'Active' : 'Idle'));
                $this->info("");
            } catch (\Exception $e) {
                $this->error("Error retrieving status for queue {$queue}: " . $e->getMessage());
            }
        }

        // If using Horizon, show more detailed worker status
        if (class_exists('Laravel\Horizon\Horizon')) {
            $this->info("Horizon Worker Processes");
            $this->info("======================");

            try {
                Artisan::call('horizon:list');
                $this->info(Artisan::output());
            } catch (\Exception $e) {
                $this->error("Error retrieving Horizon status: " . $e->getMessage());
            }
        }
    }

    /**
     * Get the number of workers to start based on queue type.
     */
    private function getWorkerCount(string $queue): int
    {
        switch ($queue) {
            case 'destructive-operations':
                // Limit destructive operations to prevent overload
                return 1;
            case 'employee-reports':
                // Reports can be CPU intensive
                return 2;
            case 'maintenance':
                // Maintenance tasks should be limited
                return 1;
            case 'employee-operations':
                // Employee operations may be more frequent
                return 3;
            case 'default':
            default:
                // Default queue gets most workers
                return 4;
        }
    }
}
