<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CarRentalController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TourPackageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ClickAndGo Journey — API Routes (v1)
|--------------------------------------------------------------------------
|
| Laravel Sanctum token authentication is used for all protected routes.
| Role enforcement uses the custom 'role' middleware (EnsureUserHasRole).
|
*/

Route::prefix('v1')->group(function () {

    // -----------------------------------------------------------------------
    // A. Authentication
    // -----------------------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me',      [AuthController::class, 'me']);
        });
    });

    // -----------------------------------------------------------------------
    // B. Public Catalog & Content Endpoints
    // -----------------------------------------------------------------------
    Route::get('tour-packages',  [TourPackageController::class, 'index']);
    Route::get('car-rentals',    [CarRentalController::class,   'index']);
    Route::get('destinations',   [ContentController::class,     'destinations']);
    Route::get('faqs',           [ContentController::class,     'faqs']);
    Route::get('testimonials',   [ContentController::class,     'testimonials']);

    // Public Midtrans webhook — no auth, but signature-verified inside the controller
    Route::post('payments/webhook', [PaymentController::class, 'webhook']);

    // -----------------------------------------------------------------------
    // C. Protected: Any authenticated user (Bookings & Payments)
    // -----------------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('bookings',              [BookingController::class, 'store']);
        Route::post('payments/snap-token',   [PaymentController::class, 'snapToken']);
        Route::post('payments/verify-status',[PaymentController::class, 'verifyStatus']);
    });

    // -----------------------------------------------------------------------
    // D. Protected: Admin + Super Admin (Catalog Management)
    // -----------------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->group(function () {
        Route::post('tour-packages', [TourPackageController::class, 'store']);
        Route::post('car-rentals',   [CarRentalController::class,   'store']);
        Route::get('customers/search', [CustomerController::class,  'search']);
    });

    // -----------------------------------------------------------------------
    // E. Protected: Super Admin Only (Role Management)
    // -----------------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'role:super_admin'])->group(function () {
        Route::post('admin/update-role', [AdminController::class, 'updateRole']);
    });
});
