<?php

use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Patient\PatientAnalysisController;
use App\Http\Controllers\API\Patient\PatientConsumptionLogController;
use App\Http\Controllers\API\Patient\PatientUserProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('register', 'register')->name('api.register');
        Route::post('login', 'login')->name('api.login');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('api.logout');
        Route::get('user', [AuthController::class, 'user'])->name('api.user');

        Route::prefix('patient')->name('patient.')->group(function () {
            Route::apiResource('analyses', PatientAnalysisController::class)->only(['index', 'store', 'show']);
            Route::apiResource('consumption-logs', PatientConsumptionLogController::class)->only(['index']);
            Route::patch('password', [PatientUserProfileController::class, 'updatePassword'])->name('profile.password');
            Route::apiResource('profile', PatientUserProfileController::class)->only(['show', 'update']);
        });
    });
});
