<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\FormSubmissionController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Tenant\TenantController;
use App\Http\Controllers\Tenant\TeamController;
use App\Http\Controllers\Admin\AdminTenantController;
use App\Http\Controllers\Admin\AdminUserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Public form routes (for viewing and submitting forms)
Route::prefix('forms')->group(function () {
    Route::get('/{slug}/public', [FormController::class, 'showPublic']);
    Route::post('/{slug}/submit', [FormSubmissionController::class, 'submit']);
    Route::post('/{slug}/view', [FormController::class, 'recordView']);
});

// NMI webhook endpoint (no auth required)
Route::post('/webhooks/nmi', [WebhookController::class, 'handleNMI']);

// Billing/Subscription public routes
Route::prefix('billing')->group(function () {
    Route::get('/plans', [SubscriptionController::class, 'plans']);
    Route::get('/tokenization-key', [SubscriptionController::class, 'getTokenizationKey']);
});

// Check subdomain availability
Route::get('/subdomain/check/{subdomain}', [TenantController::class, 'checkSubdomain']);

// Authenticated routes with tenant context
Route::middleware(['auth:sanctum', 'identify.tenant'])->group(function () {

    // Current user
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user', [AuthController::class, 'updateProfile']);
    Route::post('/user/password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Tenant management
    Route::prefix('tenant')->group(function () {
        Route::get('/', [TenantController::class, 'current']);
        Route::put('/', [TenantController::class, 'update']);
        Route::put('/branding', [TenantController::class, 'updateBranding']);
        Route::get('/usage', [TenantController::class, 'usage']);

        // Custom domain
        Route::post('/domain', [TenantController::class, 'setCustomDomain']);
        Route::post('/domain/verify', [TenantController::class, 'verifyCustomDomain']);
        Route::delete('/domain', [TenantController::class, 'removeCustomDomain']);
    });

    // Team management
    Route::prefix('team')->group(function () {
        Route::get('/', [TeamController::class, 'index']);
        Route::post('/invite', [TeamController::class, 'invite']);
        Route::delete('/{user}', [TeamController::class, 'remove']);
        Route::put('/{user}/role', [TeamController::class, 'updateRole']);
    });

    // Forms
    Route::prefix('forms')->group(function () {
        Route::get('/', [FormController::class, 'index']);
        Route::post('/', [FormController::class, 'store']);
        Route::get('/{form}', [FormController::class, 'show']);
        Route::put('/{form}', [FormController::class, 'update']);
        Route::delete('/{form}', [FormController::class, 'destroy']);
        Route::post('/{form}/duplicate', [FormController::class, 'duplicate']);
        Route::post('/{form}/publish', [FormController::class, 'publish']);
        Route::post('/{form}/close', [FormController::class, 'close']);

        // Form submissions
        Route::get('/{form}/submissions', [FormSubmissionController::class, 'index']);
        Route::get('/{form}/submissions/{submission}', [FormSubmissionController::class, 'show']);
        Route::delete('/{form}/submissions/{submission}', [FormSubmissionController::class, 'destroy']);
        Route::get('/{form}/submissions/export', [FormSubmissionController::class, 'export']);

        // Form analytics
        Route::get('/{form}/analytics', [FormController::class, 'analytics']);
    });

    // Billing/Subscriptions
    Route::prefix('billing')->group(function () {
        Route::get('/current', [SubscriptionController::class, 'current']);
        Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
        Route::put('/payment-method', [SubscriptionController::class, 'updatePaymentMethod']);
        Route::put('/plan', [SubscriptionController::class, 'changePlan']);
        Route::post('/cancel', [SubscriptionController::class, 'cancel']);
        Route::post('/resume', [SubscriptionController::class, 'resume']);
        Route::get('/history', [SubscriptionController::class, 'history']);
        Route::post('/one-time', [SubscriptionController::class, 'oneTimePayment']);
    });
});

// Create tenant (authenticated but no tenant context needed)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/tenants', [TenantController::class, 'store']);
    Route::get('/tenants', [TenantController::class, 'listUserTenants']);
});

// Super admin routes
Route::prefix('admin')->middleware(['auth:sanctum', 'super.admin'])->group(function () {

    // Dashboard
    Route::get('/statistics', [AdminTenantController::class, 'statistics']);

    // Tenant management
    Route::get('/tenants', [AdminTenantController::class, 'index']);
    Route::post('/tenants', [AdminTenantController::class, 'store']);
    Route::get('/tenants/{tenant}', [AdminTenantController::class, 'show']);
    Route::put('/tenants/{tenant}', [AdminTenantController::class, 'update']);
    Route::delete('/tenants/{tenant}', [AdminTenantController::class, 'destroy']);
    Route::post('/tenants/{tenant}/suspend', [AdminTenantController::class, 'suspend']);
    Route::post('/tenants/{tenant}/activate', [AdminTenantController::class, 'activate']);
    Route::post('/tenants/{tenant}/impersonate/{user}', [AdminTenantController::class, 'impersonate']);

    // User management
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{user}', [AdminUserController::class, 'show']);
    Route::put('/users/{user}', [AdminUserController::class, 'update']);
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
});
