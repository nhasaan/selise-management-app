<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // For better performance, we'll insert in batches
        $batchSize = 1000;
        $totalEmployees = 100000;
        $faker = Faker::create();

        // Get all department IDs once to avoid redundant queries
        $departmentIds = Department::pluck('id')->toArray();
        $departmentCount = count($departmentIds);

        // Disable foreign key checks temporarily for faster insertion
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Increase MySQL packet size for larger batch inserts
        DB::statement('SET GLOBAL max_allowed_packet=67108864'); // 64MB

        // Configure InnoDB for bulk loading
        DB::statement('SET autocommit=0');
        DB::statement('SET unique_checks=0');
        DB::statement('SET foreign_key_checks=0');

        $startTime = microtime(true);
        $this->command->info("Starting bulk employee insertion...");

        for ($i = 0; $i < $totalEmployees; $i += $batchSize) {
            $employees = [];
            $employeeDetails = [];
            $employeeIds = [];

            // Process in batches
            $currentBatchSize = min($batchSize, $totalEmployees - $i);
            $now = now()->format('Y-m-d H:i:s');

            // Pre-generate UUIDs for better performance
            for ($j = 0; $j < $currentBatchSize; $j++) {
                $employeeIds[] = (string) Str::uuid();
            }

            // Create employee records
            for ($j = 0; $j < $currentBatchSize; $j++) {
                $employees[] = [
                    'id' => $employeeIds[$j],
                    'name' => $faker->name,
                    'email' => $faker->unique()->safeEmail,
                    'department_id' => $departmentIds[array_rand($departmentIds)],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Chunk insert employees
            Employee::insert($employees);

            // Create employee detail records
            for ($j = 0; $j < $currentBatchSize; $j++) {
                $employeeDetails[] = [
                    'employee_id' => $employeeIds[$j],
                    'designation' => $faker->jobTitle(),
                    'salary' => $faker->randomFloat(2, 30000, 150000),
                    'address' => $faker->address(),
                    'joined_date' => $faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Chunk insert employee details
            EmployeeDetail::insert($employeeDetails);

            // Log progress every 10,000 records
            if (($i + $currentBatchSize) % 10000 === 0 || ($i + $currentBatchSize) === $totalEmployees) {
                $progress = ($i + $currentBatchSize) / $totalEmployees * 100;
                $elapsedTime = microtime(true) - $startTime;
                $estimatedTotalTime = $elapsedTime / ($progress / 100);
                $remainingTime = $estimatedTotalTime - $elapsedTime;

                $this->command->info(sprintf(
                    "Inserted %d of %d employees (%.2f%%). Elapsed: %.2f sec. Remaining: %.2f sec.",
                    $i + $currentBatchSize,
                    $totalEmployees,
                    $progress,
                    $elapsedTime,
                    $remainingTime
                ));
            }
        }

        // Re-enable all checks
        DB::statement('SET foreign_key_checks=1');
        DB::statement('SET unique_checks=1');
        DB::statement('SET autocommit=1');

        $totalTime = microtime(true) - $startTime;
        $this->command->info("Completed seeding $totalEmployees employees in $totalTime seconds.");
    }
}
