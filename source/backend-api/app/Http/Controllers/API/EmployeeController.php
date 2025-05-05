<?php

namespace App\Http\Controllers\API;

use App\DTO\EmployeeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Http\Resources\EmployeeCollection;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Info(
 *     title="Employee Management API",
 *     version="1.0.0",
 *     description="API for managing employees"
 * )
 */
class EmployeeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/employees",
     *     summary="Get a list of employees",
     *     tags={"Employees"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="department_id",
     *         in="query",
     *         description="Filter by department ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="min_salary",
     *         in="query",
     *         description="Minimum salary",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="max_salary",
     *         in="query",
     *         description="Maximum salary",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Field to sort by",
     *         required=false,
     *         @OA\Schema(type="string", enum={"name", "email", "joined_date", "salary"}, default="name")
     *     ),
     *     @OA\Parameter(
     *         name="sort_dir",
     *         in="query",
     *         description="Sort direction",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="asc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/EmployeeCollection")
     *     )
     * )
     */
    public function index(Request $request): EmployeeCollection
    {
        $query = Employee::query()
            ->with(['department', 'detail'])
            ->select('employees.*');

        // Add join for sorting and filtering on detail fields
        $query->join('employee_details', 'employees.id', '=', 'employee_details.employee_id');

        // Apply filters
        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('employees.name', 'like', $search)
                    ->orWhere('employees.email', 'like', $search)
                    ->orWhere('employee_details.designation', 'like', $search);
            });
        }

        if ($request->filled('department_id')) {
            $query->where('employees.department_id', $request->input('department_id'));
        }

        if ($request->filled('min_salary')) {
            $query->where('employee_details.salary', '>=', $request->input('min_salary'));
        }

        if ($request->filled('max_salary')) {
            $query->where('employee_details.salary', '<=', $request->input('max_salary'));
        }

        // Apply sorting
        $sortBy = $request->input('sort_by', 'name');
        $sortDir = $request->input('sort_dir', 'asc');

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
        $perPage = $request->input('per_page', 15);
        $employees = $query->paginate($perPage);

        return new EmployeeCollection($employees);
    }

    /**
     * @OA\Post(
     *     path="/api/employees",
     *     summary="Create a new employee",
     *     tags={"Employees"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/EmployeeRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Employee created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/EmployeeResource")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(EmployeeRequest $request): JsonResponse
    {
        $employeeData = EmployeeData::fromRequest($request);

        try {
            DB::beginTransaction();

            // Create employee
            $employee = Employee::create([
                'name' => $employeeData->name,
                'email' => $employeeData->email,
                'department_id' => $employeeData->department_id,
            ]);

            // Create employee detail
            $employee->detail()->create([
                'designation' => $employeeData->designation,
                'salary' => $employeeData->salary,
                'address' => $employeeData->address,
                'joined_date' => $employeeData->joined_date,
            ]);

            DB::commit();

            // Eager load relationships for the response
            $employee->load(['department', 'detail']);

            return response()->json(new EmployeeResource($employee), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating employee: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/employees/{id}",
     *     summary="Get employee by ID",
     *     tags={"Employees"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Employee ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/EmployeeResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Employee not found"
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        $employee = Employee::with(['department', 'detail'])->find($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        return response()->json(new EmployeeResource($employee));
    }

    /**
     * @OA\Put(
     *     path="/api/employees/{id}",
     *     summary="Update an employee",
     *     tags={"Employees"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Employee ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/EmployeeRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Employee updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/EmployeeResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Employee not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(EmployeeRequest $request, string $id): JsonResponse
    {
        $employee = Employee::with('detail')->find($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $employeeData = EmployeeData::fromRequest($request);

        try {
            DB::beginTransaction();

            // Update employee
            $employee->update([
                'name' => $employeeData->name,
                'email' => $employeeData->email,
                'department_id' => $employeeData->department_id,
            ]);

            // Update employee detail
            $employee->detail->update([
                'designation' => $employeeData->designation,
                'salary' => $employeeData->salary,
                'address' => $employeeData->address,
                'joined_date' => $employeeData->joined_date,
            ]);

            DB::commit();

            // Refresh the model with latest data
            $employee->load(['department', 'detail']);

            return response()->json(new EmployeeResource($employee));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating employee: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/employees/{id}",
     *     summary="Delete an employee",
     *     tags={"Employees"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Employee ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Employee deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Employee not found"
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        try {
            $employee->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error deleting employee: ' . $e->getMessage()], 500);
        }
    }
}
