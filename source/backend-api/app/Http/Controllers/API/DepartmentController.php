<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Tag(
 *     name="Departments",
 *     description="API endpoints for departments"
 * )
 */
class DepartmentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/departments",
     *     summary="Get all departments",
     *     tags={"Departments"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/DepartmentResource")
     *         )
     *     )
     * )
     */
    public function index(): ResourceCollection
    {
        $departments = Department::all();
        return DepartmentResource::collection($departments);
    }

    /**
     * @OA\Get(
     *     path="/api/departments/{id}",
     *     summary="Get department by ID",
     *     tags={"Departments"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Department ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/DepartmentResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Department not found"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $department = Department::find($id);

        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        return response()->json(new DepartmentResource($department));
    }

    /**
     * @OA\Post(
     *     path="/api/departments",
     *     summary="Create a new department",
     *     tags={"Departments"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Engineering"),
     *             @OA\Property(property="description", type="string", example="Software Development Department")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Department created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/DepartmentResource")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments',
            'description' => 'nullable|string'
        ]);

        $department = Department::create($validated);

        return response()->json(new DepartmentResource($department), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/departments/{id}",
     *     summary="Update a department",
     *     tags={"Departments"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Department ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Engineering"),
     *             @OA\Property(property="description", type="string", example="Software Development Department")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Department updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/DepartmentResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Department not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $department = Department::find($id);

        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $id,
            'description' => 'nullable|string'
        ]);

        $department->update($validated);

        return response()->json(new DepartmentResource($department));
    }

    /**
     * @OA\Delete(
     *     path="/api/departments/{id}",
     *     summary="Delete a department",
     *     tags={"Departments"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Department ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Department deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Department not found"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Cannot delete department with associated employees"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $department = Department::find($id);

        if (!$department) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        // Check if there are employees in this department
        if ($department->employees()->count() > 0) {
            return response()->json(['message' => 'Cannot delete department with associated employees'], 409);
        }

        $department->delete();

        return response()->json(null, 204);
    }
}
