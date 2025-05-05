<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\DepartmentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Apply API rate limiting to all routes
Route::middleware('throttle:api')->group(function () {
    // Department routes
    Route::apiResource('departments', DepartmentController::class);

    // Employee routes with specific rate limiting for list endpoint
    Route::get('employees', [EmployeeController::class, 'index'])
        ->middleware('throttle:employee-list');

    Route::post('employees', [EmployeeController::class, 'store']);
    Route::get('employees/{employee}', [EmployeeController::class, 'show']);
    Route::put('employees/{employee}', [EmployeeController::class, 'update']);
    Route::delete('employees/{employee}', [EmployeeController::class, 'destroy']);
});

Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});
