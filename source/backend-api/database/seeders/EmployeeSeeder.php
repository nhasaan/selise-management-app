<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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

        // Get all department IDs once to avoid redundant queries
        $departmentIds = Department::pluck('id')->toArray();

        // Disable foreign key checks temporarily for faster insertion
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        for ($i = 0; $i < $totalEmployees; $i += $batchSize) {
            $employees = [];
            $employeeDetails = [];

            // Process in batches
            $currentBatchSize = min($batchSize, $totalEmployees - $i);

            // Create employee records
            $employeeRecords = Employee::factory($currentBatchSize)
                ->make(['department_id' => fn() => $departmentIds[array_rand($departmentIds)]])
                ->toArray();

            // Chunk insert employees
            $employeeIds = [];
            foreach ($employeeRecords as $employee) {
                $employeeIds[] = $employee['id'];
            }

            Employee::insert($employeeRecords);

            // Create employee detail records
            $now = now();
            for ($j = 0; $j < $currentBatchSize; $j++) {
                $employeeDetails[] = [
                    'employee_id' => $employeeIds[$j],
                    'designation' => fake()->jobTitle(),
                    'salary' => fake()->randomFloat(2, 30000, 150000),
                    'address' => fake()->address(),
                    'joined_date' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Chunk insert employee details
            EmployeeDetail::insert($employeeDetails);

            // Log progress
            $this->command->info("Inserted " . ($i + $currentBatchSize) . " of $totalEmployees employees");
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
