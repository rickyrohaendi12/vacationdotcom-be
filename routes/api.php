<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ScheduleClassController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

// ── Public routes ──────────────────────────────────────────────────────

Route::prefix('auth')->group(function () {
    Route::post('register',         [AuthController::class, 'register']);
    Route::post('login',            [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
});

Route::get('search',                [SearchController::class, '__invoke']);
Route::get('locations',             [LocationController::class, 'search']);
Route::get('schedule-classes/{id}', [ScheduleClassController::class, 'show']);

// Webhook Midtrans — PUBLIC, tidak perlu token
Route::post('payments/webhook',     [PaymentController::class, 'webhook']);

// ── Protected routes (butuh token Sanctum) ─────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me',      [AuthController::class, 'me']);

    // Booking
    Route::get('bookings',                [BookingController::class, 'index']);
    Route::post('bookings',               [BookingController::class, 'store']);
    Route::get('bookings/{booking_code}', [BookingController::class, 'show']);

    // Payment
    Route::post('payments/token',                    [PaymentController::class, 'getToken']);
    Route::get('payments/status/{booking_code}',     [PaymentController::class, 'status']);
});
