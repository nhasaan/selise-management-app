<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Repositories\EmployeeRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class GenerateEmployeeReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 900; // 15 minutes

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * The report type to generate.
     *
     * @var string
     */
    protected $reportType;

    /**
     * The filters to apply to the report.
     *
     * @var array
     */
    protected $filters;

    /**
     * The user ID requesting the report.
     *
     * @var int|null
     */
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $reportType, array $filters = [], ?int $userId = null)
    {
        $this->reportType = $reportType;
        $this->filters = $filters;
        $this->userId = $userId;
        $this->onQueue('employee-reports');
    }

    /**
     * Execute the job.
     */
    public function handle(EmployeeRepository $repository): void
    {
        try {
            Log::info("Starting employee report generation: {$this->reportType}");

            $filename = 'employee_' . $this->reportType . '_' . time() . '.csv';
            $tempFile = storage_path('app/temp/' . $filename);

            // Create temp directory if it doesn't exist
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Open file for writing
            $file = fopen($tempFile, 'w');

            // Write headers based on report type
            $this->writeReportHeaders($file);

            // Process data in chunks to avoid memory issues
            $this->processReportData($file, $repository);

            // Close file
            fclose($file);

            // Move to final storage location
            $finalPath = 'reports/' . $filename;
            Storage::put($finalPath, file_get_contents($tempFile));

            // Clean up temp file
            unlink($tempFile);

            Log::info("Completed employee report generation: {$this->reportType}. File: {$finalPath}");

            // Notify user that report is ready if userId is provided
            if ($this->userId) {
                $this->notifyUser($finalPath);
            }
        } catch (\Exception $e) {
            Log::error("Error generating employee report: " . $e->getMessage());
            $this->fail($e);
        }
    }

    /**
     * Write report headers based on report type.
     */
    private function writeReportHeaders($file): void
    {
        $headers = [];

        switch ($this->reportType) {
            case 'department_summary':
                $headers = ['Department ID', 'Department Name', 'Employee Count', 'Average Salary', 'Min Salary', 'Max Salary'];
                break;
            case 'salary_distribution':
                $headers = ['Salary Range', 'Employee Count', 'Percentage'];
                break;
            case 'joining_trends':
                $headers = ['Month', 'Year', 'Employee Count'];
                break;
            default:
                $headers = ['ID', 'Name', 'Email', 'Department', 'Designation', 'Salary', 'Joined Date'];
                break;
        }

        fputcsv($file, $headers);
    }

    /**
     * Process report data in chunks.
     */
    private function processReportData($file, EmployeeRepository $repository): void
    {
        switch ($this->reportType) {
            case 'department_summary':
                $this->processDepartmentSummary($file, $repository);
                break;
            case 'salary_distribution':
                $this->processSalaryDistribution($file, $repository);
                break;
            case 'joining_trends':
                $this->processJoiningTrends($file, $repository);
                break;
            default:
                $this->processFullEmployeeList($file, $repository);
                break;
        }
    }

    /**
     * Process department summary report.
     */
    private function processDepartmentSummary($file, EmployeeRepository $repository): void
    {
        $departmentStats = $repository->getDepartmentStatistics();

        foreach ($departmentStats as $stat) {
            fputcsv($file, [
                $stat->id,
                $stat->name,
                $stat->employee_count,
                number_format($stat->average_salary, 2),
                number_format($stat->min_salary, 2),
                number_format($stat->max_salary, 2)
            ]);
        }
    }

    /**
     * Process salary distribution report.
     */
    private function processSalaryDistribution($file, EmployeeRepository $repository): void
    {
        // Define salary ranges
        $ranges = [
            '0-30,000' => [0, 30000],
            '30,001-50,000' => [30001, 50000],
            '50,001-75,000' => [50001, 75000],
            '75,001-100,000' => [75001, 100000],
            '100,001-125,000' => [100001, 125000],
            'Over 125,000' => [125001, PHP_INT_MAX]
        ];

        $totalEmployees = Employee::count();

        foreach ($ranges as $label => [$min, $max]) {
            $count = Employee::join('employee_details', 'employees.id', '=', 'employee_details.employee_id')
                ->where('employee_details.salary', '>=', $min)
                ->where('employee_details.salary', '<=', $max)
                ->count();

            $percentage = ($totalEmployees > 0) ? ($count / $totalEmployees) * 100 : 0;

            fputcsv($file, [
                $label,
                $count,
                number_format($percentage, 2) . '%'
            ]);
        }
    }

    /**
     * Process joining trends report.
     */
    private function processJoiningTrends($file, EmployeeRepository $repository): void
    {
        // Get trends by month and year
        $trends = DB::table('employee_details')
            ->select(
                DB::raw('MONTH(joined_date) as month'),
                DB::raw('YEAR(joined_date) as year'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        foreach ($trends as $trend) {
            $monthName = date('F', mktime(0, 0, 0, $trend->month, 1));

            fputcsv($file, [
                $monthName,
                $trend->year,
                $trend->count
            ]);
        }
    }

    /**
     * Process full employee list report.
     */
    private function processFullEmployeeList($file, EmployeeRepository $repository): void
    {
        // Process in chunks to avoid memory issues
        Employee::with(['department', 'detail'])
            ->chunk(1000, function ($employees) use ($file) {
                foreach ($employees as $employee) {
                    fputcsv($file, [
                        $employee->id,
                        $employee->name,
                        $employee->email,
                        $employee->department->name,
                        $employee->detail->designation,
                        number_format($employee->detail->salary, 2),
                        $employee->detail->joined_date->format('Y-m-d')
                    ]);
                }
            });
    }

    /**
     * Notify user that report is ready.
     */
    private function notifyUser(string $reportPath): void
    {
        // Implementation depends on notification system
        // This could send an email, push notification, etc.
        Log::info("Report notification would be sent to user {$this->userId} for file {$reportPath}");
    }
}
