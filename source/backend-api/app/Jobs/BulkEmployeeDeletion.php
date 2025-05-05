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

class BulkEmployeeDeletion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600; // 1 hour

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The IDs of employees to delete.
     *
     * @var array
     */
    protected $employeeIds;

    /**
     * Whether to force delete records (bypass soft delete).
     *
     * @var bool
     */
    protected $forceDelete;

    /**
     * Create a new job instance.
     */
    public function __construct(array $employeeIds, bool $forceDelete = false)
    {
        $this->employeeIds = $employeeIds;
        $this->forceDelete = $forceDelete;

        // Use a dedicated queue for destructive operations
        $this->onQueue('destructive-operations');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $count = count($this->employeeIds);
            Log::info("Starting bulk employee deletion job. Count: {$count}, Force Delete: " . ($this->forceDelete ? 'Yes' : 'No'));

            // Process in chunks to avoid memory issues
            $batchSize = 1000;
            $batches = array_chunk($this->employeeIds, $batchSize);

            foreach ($batches as $index => $batchIds) {
                $this->processBatch($batchIds);
                Log::info("Processed batch " . ($index + 1) . " of " . count($batches));
            }

            // Clear caches
            $this->clearCaches();

            Log::info("Completed bulk employee deletion job. Deleted {$count} employees.");
        } catch (\Exception $e) {
            Log::error("Error in bulk employee deletion: " . $e->getMessage());
            $this->fail($e);
        }
    }

    /**
     * Process a batch of employee deletions.
     */
    private function processBatch(array $batchIds): void
    {
        // Use a transaction for each batch to ensure data integrity
        DB::transaction(function () use ($batchIds) {
            $query = Employee::whereIn('id', $batchIds);

            if ($this->forceDelete) {
                // For force delete, we need to include soft-deleted records
                $query = $query->withTrashed();

                // Force delete each employee (cascade to related records)
                foreach ($query->cursor() as $employee) {
                    $employee->forceDelete();
                }
            } else {
                // Regular soft delete
                $query->delete();
            }
        });
    }

    /**
     * Clear relevant caches after deletion.
     */
    private function clearCaches(): void
    {
        // Clear employee list caches
        Cache::forget('employees_*');

        // Clear count caches
        Cache::forget('employee_count');

        // Clear department statistics (affected by employee counts)
        Cache::forget('department_statistics');

        // Clear recent employees cache
        Cache::forget('recent_employees');
    }
}
