<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TravelRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Auth routes
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Travel Request routes
    Route::apiResource('travel-requests', TravelRequestController::class);
    Route::post('/travel-requests/{id}/approve', [TravelRequestController::class, 'approve']);
    Route::post('/travel-requests/{id}/cancel', [TravelRequestController::class, 'cancel']);
});
