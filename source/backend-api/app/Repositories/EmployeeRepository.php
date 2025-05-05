<?php

namespace App\Repositories;

use App\Models\Employee;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EmployeeRepository
{
    /**
     * Get employees based on query parameters.
     *
     * @param array $params Query parameters
     * @return LengthAwarePaginator Paginated employee results
     */
    public function getEmployees(array $params): LengthAwarePaginator
    {
        // Default parameters
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? 15;
        $search = $params['search'] ?? null;
        $departmentId = $params['department_id'] ?? null;
        $minSalary = $params['min_salary'] ?? null;
        $maxSalary = $params['max_salary'] ?? null;
        $sortBy = $params['sort_by'] ?? 'name';
        $sortDir = $params['sort_dir'] ?? 'asc';

        // Start building the query
        $query = Employee::query()
            ->select('employees.*')
            ->join('employee_details', 'employees.id', '=', 'employee_details.employee_id');

        // Apply eager loading on related models
        $query->with([
            'department:id,name,description',
            'detail:id,employee_id,designation,salary,address,joined_date'
        ]);

        // Apply filters
        if ($search) {
            $searchTerm = '%' . $search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('employees.name', 'like', $searchTerm)
                    ->orWhere('employees.email', 'like', $searchTerm)
                    ->orWhere('employee_details.designation', 'like', $searchTerm);
            });
        }

        if ($departmentId) {
            $query->where('employees.department_id', $departmentId);
        }

        if ($minSalary) {
            $query->where('employee_details.salary', '>=', $minSalary);
        }

        if ($maxSalary) {
            $query->where('employee_details.salary', '<=', $maxSalary);
        }

        // Apply sorting
        switch ($sortBy) {
            case 'joined_date':
                $query->orderBy('employee_details.joined_date', $sortDir);
                break;
            case 'salary':
                $query->orderBy('employee_details.salary', $sortDir);
                break;
            case 'email':
                $query->orderBy('employees.email', $sortDir);
                break;
            default:
                $query->orderBy('employees.name', $sortDir);
                break;
        }

        // Add a second order by to ensure consistent results
        $query->orderBy('employees.id', 'asc');

        // Get paginated results
        $employees = $query->paginate($perPage, ['*'], 'page', $page);

        return $employees;
    }

    /**
     * Find an employee by ID with relationships loaded.
     *
     * @param string $id Employee ID
     * @return Employee|null Employee model or null if not found
     */
    public function findById(string $id): ?Employee
    {
        return Employee::with(['department', 'detail'])->find($id);
    }

    /**
     * Create a new employee with details.
     *
     * @param array $employeeData Main employee data
     * @param array $detailData Employee detail data
     * @return Employee The created employee
     */
    public function create(array $employeeData, array $detailData): Employee
    {
        return DB::transaction(function () use ($employeeData, $detailData) {
            // Create employee
            $employee = Employee::create($employeeData);

            // Create employee detail
            $employee->detail()->create($detailData);

            // Reload with relationships
            $employee->load(['department', 'detail']);

            return $employee;
        });
    }

    /**
     * Update an existing employee and its details.
     *
     * @param string $id Employee ID
     * @param array $employeeData Main employee data
     * @param array $detailData Employee detail data
     * @return Employee|null The updated employee or null if not found
     */
    public function update(string $id, array $employeeData, array $detailData): ?Employee
    {
        return DB::transaction(function () use ($id, $employeeData, $detailData) {
            // Find employee with lock for update
            $employee = Employee::with('detail')->lockForUpdate()->find($id);

            if (!$employee) {
                return null;
            }

            // Update employee
            $employee->update($employeeData);

            // Update employee detail
            $employee->detail->update($detailData);

            // Reload with relationships
            $employee->load(['department', 'detail']);

            return $employee;
        });
    }

    /**
     * Delete an employee by ID.
     *
     * @param string $id Employee ID
     * @return bool True if deleted, false if not found
     */
    public function delete(string $id): bool
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return false;
        }

        return DB::transaction(function () use ($employee) {
            return $employee->delete();
        });
    }

    /**
     * Get department statistics for dashboard.
     *
     * @return array Department statistics
     */
    public function getDepartmentStatistics(): array
    {
        return DB::table('employees')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->join('employee_details', 'employees.id', '=', 'employee_details.employee_id')
            ->select(
                'departments.id',
                'departments.name',
                DB::raw('COUNT(employees.id) as employee_count'),
                DB::raw('AVG(employee_details.salary) as average_salary'),
                DB::raw('MIN(employee_details.salary) as min_salary'),
                DB::raw('MAX(employee_details.salary) as max_salary')
            )
            ->groupBy('departments.id', 'departments.name')
            ->get()
            ->toArray();
    }

    /**
     * Get recently joined employees.
     *
     * @param int $limit Number of employees to return
     * @return array Recent employees
     */
    public function getRecentEmployees(int $limit = 5): array
    {
        return Employee::with(['department', 'detail'])
            ->join('employee_details', 'employees.id', '=', 'employee_details.employee_id')
            ->orderBy('employee_details.joined_date', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
