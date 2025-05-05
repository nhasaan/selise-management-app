<?php

namespace App\Http\Controllers\API;

use App\DTO\EmployeeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Http\Resources\EmployeeCollection;
use App\Http\Resources\EmployeeResource;
use App\Repositories\EmployeeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
     * The employee repository implementation.
     */
    protected EmployeeRepository $repository;

    /**
     * Create a new controller instance.
     */
    public function __construct(EmployeeRepository $repository)
    {
        $this->repository = $repository;
    }

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
        // Generate cache key from request params
        $cacheKey = 'employees_' . md5(json_encode($request->all()));

        // Try to get from cache first (5 minute TTL)
        $employees = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($request) {
            return $this->repository->getEmployees($request->all());
        });

        // Track the key for later cache management
        $this->trackCacheKey($cacheKey);

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
            // Separate main employee data from detail data
            $employee = $this->repository->create(
                [
                    'name' => $employeeData->name,
                    'email' => $employeeData->email,
                    'department_id' => $employeeData->department_id,
                ],
                [
                    'designation' => $employeeData->designation,
                    'salary' => $employeeData->salary,
                    'address' => $employeeData->address,
                    'joined_date' => $employeeData->joined_date,
                ]
            );

            // Clear all list caches as this changes results
            $this->clearEmployeeListCache();

            return response()->json(new EmployeeResource($employee), 201);
        } catch (\Exception $e) {
            Log::error('Error creating employee: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error creating employee',
                'error' => app()->isLocal() ? $e->getMessage() : 'Server error'
            ], 500);
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
        // Cache key for this employee
        $cacheKey = 'employee_' . $id;

        // Cache for 30 minutes
        $employee = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($id) {
            return $this->repository->findById($id);
        });

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        // Track the key for later cache management
        $this->trackCacheKey($cacheKey);

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
        $employeeData = EmployeeData::fromRequest($request);

        try {
            // Separate main employee data from detail data
            $employee = $this->repository->update(
                $id,
                [
                    'name' => $employeeData->name,
                    'email' => $employeeData->email,
                    'department_id' => $employeeData->department_id,
                ],
                [
                    'designation' => $employeeData->designation,
                    'salary' => $employeeData->salary,
                    'address' => $employeeData->address,
                    'joined_date' => $employeeData->joined_date,
                ]
            );

            if (!$employee) {
                return response()->json(['message' => 'Employee not found'], 404);
            }

            // Clear all caches as this changes results
            $this->clearEmployeeCache($id);

            return response()->json(new EmployeeResource($employee));
        } catch (\Exception $e) {
            Log::error('Error updating employee: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error updating employee',
                'error' => app()->isLocal() ? $e->getMessage() : 'Server error'
            ], 500);
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
        try {
            $deleted = $this->repository->delete($id);

            if (!$deleted) {
                return response()->json(['message' => 'Employee not found'], 404);
            }

            // Clear all caches as this changes results
            $this->clearEmployeeCache($id);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Error deleting employee: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error deleting employee',
                'error' => app()->isLocal() ? $e->getMessage() : 'Server error'
            ], 500);
        }
    }

    /**
     * Track a cache key for later management.
     */
    private function trackCacheKey(string $key): void
    {
        $keys = Cache::get('employee_cache_keys', []);

        // Only add if not already tracked
        if (!in_array($key, $keys)) {
            $keys[] = $key;
            Cache::put('employee_cache_keys', $keys, now()->addDay());
        }
    }

    /**
     * Clear cache for a specific employee and any list caches.
     */
    private function clearEmployeeCache(string $id): void
    {
        // Clear specific employee cache
        Cache::forget('employee_' . $id);

        // Clear all list caches too
        $this->clearEmployeeListCache();
    }

    /**
     * Clear all employee list caches.
     */
    private function clearEmployeeListCache(): void
    {
        // Find all list cache keys (those starting with 'employees_')
        $keys = Cache::get('employee_cache_keys', []);

        foreach ($keys as $key) {
            if (strpos($key, 'employees_') === 0) {
                Cache::forget($key);
            }
        }
    }
}
