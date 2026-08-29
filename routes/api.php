<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DueController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RepairController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Middleware\EnsureShopActive;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| IMPORTANT FIX
|--------------------------------------------------------------------------
|
| Your laravel.log shows:
|
|   Target class [shop.active] does not exist.
|
| Register the alias here as well as using the middleware class directly.
| This makes old/stale route definitions that still reference "shop.active"
| resolvable after route/cache clearing.
|
*/
Route::aliasMiddleware(
    'shop.active',
    EnsureShopActive::class
);

/*
|--------------------------------------------------------------------------
| Public API
|--------------------------------------------------------------------------
*/

Route::post('/register-business', [AuthController::class, 'registerBusiness']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

/*
|--------------------------------------------------------------------------
| Authenticated shop API
|--------------------------------------------------------------------------
|
| Use EnsureShopActive::class directly so /api/dashboard does not depend on
| middleware alias lookup.
|
*/
Route::middleware([
    'auth:sanctum',
    EnsureShopActive::class,
])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/shop/profile', [ShopController::class, 'show']);
    Route::put('/shop/profile', [ShopController::class, 'update']);
    Route::put('/settings', [SettingsController::class, 'update']);

    Route::apiResource('product-categories', ProductCategoryController::class);
    Route::apiResource('products', ProductController::class);

    Route::get('/stock/history', [StockController::class, 'history']);
    Route::post('/stock/in', [StockController::class, 'stockIn']);
    Route::post('/stock/out', [StockController::class, 'stockOut']);

    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('customers', CustomerController::class);
    Route::get('/customers/{customer}/ledger', [CustomerController::class, 'ledger']);

    Route::apiResource('sales', SaleController::class)->only([
        'index',
        'store',
        'show',
    ]);
    Route::post('/sales/{sale}/return', [SaleController::class, 'returnSale']);

    Route::get('/dues', [DueController::class, 'index']);
    Route::post('/dues/collect', [DueController::class, 'collect']);
    Route::get('/payments', [PaymentController::class, 'index']);

    Route::apiResource('repairs', RepairController::class);
    Route::patch('/repairs/{repair}/status', [RepairController::class, 'updateStatus']);
    Route::post('/repairs/{repair}/payment', [RepairController::class, 'collectPayment']);

    Route::apiResource('expenses', ExpenseController::class);
    Route::apiResource('staff', StaffController::class);

    Route::get('/reports/sales', [ReportController::class, 'sales']);
    Route::get('/reports/profit', [ReportController::class, 'profit']);
    Route::get('/reports/stock', [ReportController::class, 'stock']);
    Route::get('/reports/customer-due', [ReportController::class, 'customerDue']);
    Route::get('/reports/repair', [ReportController::class, 'repair']);
    Route::get('/reports/expense', [ReportController::class, 'expense']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
});
