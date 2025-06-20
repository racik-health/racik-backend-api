<?php

use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Auth\SocialiteController;
use App\Http\Controllers\API\ContactController;
use App\Http\Controllers\API\Patient\DispenseController;
use App\Http\Controllers\API\Patient\PatientAnalysisController;
use App\Http\Controllers\API\Patient\PatientConsumptionLogController;
use App\Http\Controllers\API\Patient\PatientDashboardController;
use App\Http\Controllers\API\Patient\PatientUserProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('register', [AuthController::class, 'register'])->name('api.register');
    Route::post('login', [AuthController::class, 'login'])->name('api.login');
    Route::get('auth/google/redirect', [SocialiteController::class, 'redirectToGoogle'])->name('api.auth.google.redirect');
    Route::get('auth/google/callback', [SocialiteController::class, 'handleGoogleCallback'])->name('api.auth.google.callback');
    Route::apiResource('contact', ContactController::class)->only(['store']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('api.logout');
        Route::get('user', [AuthController::class, 'user'])->name('api.user');

        // Patient routes
        Route::prefix('patient')->name('patient.')->group(function () {
            Route::apiResource('dashboard', PatientDashboardController::class)->only('index');
            Route::apiResource('analyses', PatientAnalysisController::class)->only(['index', 'store', 'show']);
            Route::apiResource('dispense', DispenseController::class)->only('store');
            Route::apiResource('consumption-logs', PatientConsumptionLogController::class)->only('index');
            Route::patch('password', [PatientUserProfileController::class, 'updatePassword'])->name('profile.password');
            Route::apiResource('profile', PatientUserProfileController::class)->only(['show', 'update']);
        });

        // Admin routes
        Route::prefix('admin')->name('admin.')->group(function () {
        });
    });
});
