<?php

use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\DoctorAvailabilityController;
use App\Http\Controllers\Api\Admin\DoctorController;
use App\Http\Controllers\Api\Admin\ServiceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Public\AvailableSlotController;
use App\Http\Controllers\Api\Public\BookingController;
use App\Http\Controllers\Api\Public\PublicCatalogController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::prefix('public')->group(function (): void {
    Route::get('/services', [PublicCatalogController::class, 'services']);
    Route::get('/doctors', [PublicCatalogController::class, 'doctors']);
    Route::get('/available-slots', AvailableSlotController::class);
    Route::post('/appointments', [BookingController::class, 'store']);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::middleware('role:admin')->prefix('admin')->group(function (): void {
        Route::get('/dashboard', DashboardController::class);

        Route::get('/services', [ServiceController::class, 'index']);
        Route::post('/services', [ServiceController::class, 'store']);
        Route::get('/services/{service}', [ServiceController::class, 'show']);
        Route::put('/services/{service}', [ServiceController::class, 'update']);
        Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

        Route::get('/doctors', [DoctorController::class, 'index']);
        Route::post('/doctors', [DoctorController::class, 'store']);
        Route::get('/doctors/{doctor}', [DoctorController::class, 'show']);
        Route::put('/doctors/{doctor}', [DoctorController::class, 'update']);
        Route::delete('/doctors/{doctor}', [DoctorController::class, 'destroy']);

        Route::get('/availability', [DoctorAvailabilityController::class, 'index']);
        Route::post('/availability', [DoctorAvailabilityController::class, 'store']);
        Route::get('/availability/{doctorAvailability}', [DoctorAvailabilityController::class, 'show']);
        Route::put('/availability/{doctorAvailability}', [DoctorAvailabilityController::class, 'update']);
        Route::delete('/availability/{doctorAvailability}', [DoctorAvailabilityController::class, 'destroy']);
    });
});
