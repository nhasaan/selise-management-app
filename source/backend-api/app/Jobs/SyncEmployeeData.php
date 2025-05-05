<?php

namespace App\Jobs;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncEmployeeData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1800; // 30 minutes

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Indicate if the job should be marked as failed on timeout.
     *
     * @var bool
     */
    public $failOnTimeout = true;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting employee data sync job');

            // 1. Update materialized views or denormalized tables
            $this->syncDenormalizedData();

            // 2. Recalculate department statistics
            $this->updateDepartmentStatistics();

            // 3. Clean up soft deleted records older than 30 days
            $this->cleanupOldDeletedRecords();

            // 4. Rebuild key caches
            $this->rebuildCaches();

            Log::info('Completed employee data sync job');
        } catch (\Exception $e) {
            Log::error('Error in employee data sync job: ' . $e->getMessage());
            $this->fail($e);
        }
    }

    /**
     * Sync denormalized data for better query performance.
     * (If your application uses denormalized tables to speed up queries)
     */
    private function syncDenormalizedData(): void
    {
        Log::info('Syncing denormalized employee data');

        // Note: This is a placeholder implementation. In a real application,
        // you might have a denormalized table for faster querying.

        // Example with a hypothetical employee_search_view table:
        /*
        DB::statement("
            INSERT INTO employee_search_view (id, name, email, department_name, designation, salary, joined_date)
            SELECT
                e.id,
                e.name,
                e.email,
                d.name as department_name,
                ed.designation,
                ed.salary,
                ed.joined_date
            FROM
                employees e
                JOIN departments d ON e.department_id = d.id
                JOIN employee_details ed ON e.id = ed.employee_id
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                email = VALUES(email),
                department_name = VALUES(department_name),
                designation = VALUES(designation),
                salary = VALUES(salary),
                joined_date = VALUES(joined_date)
        ");
        */

        Log::info('Denormalized data sync completed');
    }

    /**
     * Update department statistics for fast retrieval.
     */
    private function updateDepartmentStatistics(): void
    {
        Log::info('Updating department statistics');

        $stats = DB::table('employees')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->join('employee_details', 'employees.id', '=', 'employee_details.employee_id')
            ->whereNull('employees.deleted_at')
            ->select(
                'departments.id',
                'departments.name',
                DB::raw('COUNT(employees.id) as employee_count'),
                DB::raw('AVG(employee_details.salary) as average_salary'),
                DB::raw('MIN(employee_details.salary) as min_salary'),
                DB::raw('MAX(employee_details.salary) as max_salary')
            )
            ->groupBy('departments.id', 'departments.name')
            ->get();

        // Store in cache for fast retrieval
        Cache::put('department_statistics', $stats, now()->addDay());

        Log::info('Department statistics updated');
    }

    /**
     * Clean up soft deleted records older than 30 days.
     */
    private function cleanupOldDeletedRecords(): void
    {
        Log::info('Cleaning up old soft-deleted employee records');

        // Find employees soft-deleted more than 30 days ago
        $cutoffDate = now()->subDays(30);

        // Get IDs before deleting to handle related records
        $oldDeletedEmployeeIds = Employee::onlyTrashed()
            ->where('deleted_at', '<', $cutoffDate)
            ->pluck('id')
            ->toArray();

        if (count($oldDeletedEmployeeIds) > 0) {
            Log::info('Found ' . count($oldDeletedEmployeeIds) . ' old deleted employee records to purge');

            // Use chunks to avoid memory issues with large datasets
            foreach (array_chunk($oldDeletedEmployeeIds, 1000) as $chunk) {
                // Start a transaction for each chunk to ensure data integrity
                DB::transaction(function () use ($chunk) {
                    // Force delete the employee records (and cascade to related records)
                    Employee::onlyTrashed()
                        ->whereIn('id', $chunk)
                        ->forceDelete();
                });
            }
        } else {
            Log::info('No old deleted employee records found to purge');
        }
    }

    /**
     * Rebuild key caches for fast retrieval.
     */
    private function rebuildCaches(): void
    {
        Log::info('Rebuilding employee data caches');

        // Clear existing caches
        Cache::forget('employees_*');

        // Rebuild department list cache
        $departments = DB::table('departments')
            ->whereNull('deleted_at')
            ->select('id', 'name', 'description')
            ->get();

        Cache::put('all_departments', $departments, now()->addDay());

        // Rebuild recent employees cache
        $recentEmployees = Employee::with(['department', 'detail'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        Cache::put('recent_employees', $recentEmployees, now()->addHours(6));

        // Rebuild employee count cache
        $employeeCount = Employee::count();
        Cache::put('employee_count', $employeeCount, now()->addDay());

        Log::info('Employee data caches rebuilt successfully');
    }
}
