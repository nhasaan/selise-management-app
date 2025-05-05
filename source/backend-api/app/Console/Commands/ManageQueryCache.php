<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ManageQueryCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:query {action : The action to perform (warmup, clear)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manages API query cache for improved performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        if ($action === 'clear') {
            $this->clearQueryCache();
        } elseif ($action === 'warmup') {
            $this->warmupQueryCache();
        } else {
            $this->error("Invalid action. Use 'warmup' or 'clear'.");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Clear all query cache entries.
     */
    private function clearQueryCache(): void
    {
        $this->info('Clearing query cache...');

        // Clear all employee-related caches
        Cache::forget('employees_*');

        // Clear specific employee caches - we'll truncate after first 1000 for performance
        $employeeKeys = Cache::get('employee_cache_keys', []);
        $count = 0;

        foreach ($employeeKeys as $key) {
            Cache::forget($key);
            $count++;

            if ($count >= 1000) {
                break;
            }
        }

        // Reset the tracking key
        Cache::put('employee_cache_keys', [], now()->addDay());

        $this->info('Query cache cleared successfully!');
    }

    /**
     * Warm up the query cache with commonly used queries.
     */
    private function warmupQueryCache(): void
    {
        $this->info('Warming up query cache...');

        // Common page sizes
        $pageSizes = [15, 25, 50];

        // Common sort fields
        $sortFields = ['name', 'email', 'joined_date', 'salary'];

        // Cache the most common queries
        foreach ($pageSizes as $pageSize) {
            foreach ($sortFields as $sortField) {
                // Default sort direction
                $this->cacheSortedEmployeeQuery($pageSize, $sortField, 'asc');

                // Also cache descending for date fields which are commonly sorted both ways
                if (in_array($sortField, ['joined_date', 'salary'])) {
                    $this->cacheSortedEmployeeQuery($pageSize, $sortField, 'desc');
                }
            }
        }

        $this->info('Query cache warmed up successfully!');
    }

    /**
     * Cache a specific sorted employee query.
     */
    private function cacheSortedEmployeeQuery(int $pageSize, string $sortField, string $sortDirection): void
    {
        $this->line("  Caching query: page size {$pageSize}, sort by {$sortField} {$sortDirection}");

        // Build the query parameters
        $params = [
            'page' => 1,
            'per_page' => $pageSize,
            'sort_by' => $sortField,
            'sort_dir' => $sortDirection,
        ];

        // Generate cache key
        $cacheKey = 'employees_' . md5(json_encode($params));

        // Use the EmployeeRepository to get the employees
        $employees = app(\App\Repositories\EmployeeRepository::class)
            ->getEmployees($params);

        // Cache the result
        Cache::put($cacheKey, $employees, now()->addHours(6));

        // Track the key for later management
        $employeeKeys = Cache::get('employee_cache_keys', []);
        $employeeKeys[] = $cacheKey;
        Cache::put('employee_cache_keys', $employeeKeys, now()->addDay());
    }
}
