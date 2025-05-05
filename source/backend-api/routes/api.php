<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\DepartmentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Department routes
Route::apiResource('departments', DepartmentController::class);

// Employee routes
Route::apiResource('employees', EmployeeController::class);
