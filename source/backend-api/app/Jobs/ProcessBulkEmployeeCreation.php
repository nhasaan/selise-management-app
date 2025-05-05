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

class ProcessBulkEmployeeCreation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutes

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The employee data for bulk creation.
     *
     * @var array
     */
    protected $employeeData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $employeeData)
    {
        $this->employeeData = $employeeData;
        $this->onQueue('employee-operations');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting bulk employee creation job. Count: ' . count($this->employeeData));

            // Process in batches for better performance
            $batchSize = 100;
            $batches = array_chunk($this->employeeData, $batchSize);

            foreach ($batches as $index => $batch) {
                $this->processBatch($batch);
                Log::info('Processed batch ' . ($index + 1) . ' of ' . count($batches));
            }

            // Clear employee cache once the job is done
            Cache::forget('employees_*');

            Log::info('Completed bulk employee creation job');
        } catch (\Exception $e) {
            Log::error('Error in bulk employee creation: ' . $e->getMessage());
            $this->fail($e);
        }
    }

    /**
     * Process a batch of employee records.
     */
    private function processBatch(array $batch): void
    {
        // Use a transaction for each batch
        DB::transaction(function () use ($batch) {
            foreach ($batch as $data) {
                // Create employee
                $employee = Employee::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'department_id' => $data['department_id'],
                ]);

                // Create employee detail
                $employee->detail()->create([
                    'designation' => $data['designation'],
                    'salary' => $data['salary'],
                    'address' => $data['address'],
                    'joined_date' => $data['joined_date'],
                ]);
            }
        });
    }
}
