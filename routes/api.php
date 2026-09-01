<?php

use App\Http\Controllers\Api\AccountProfileController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DueController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RepairController;
use App\Http\Controllers\Api\RepairPhotoController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Middleware\ShopActive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API
|--------------------------------------------------------------------------
*/

Route::post(
    '/register-business',
    [AuthController::class, 'registerBusiness']
);

Route::post(
    '/login',
    [AuthController::class, 'login']
);

Route::post(
    '/forgot-password',
    [AuthController::class, 'forgotPassword']
);

Route::post(
    '/verify-otp',
    [AuthController::class, 'verifyOtp']
);

/*
|--------------------------------------------------------------------------
| Public Profile Image
|--------------------------------------------------------------------------
*/

Route::get(
    '/profile-images/{filename}',
    [ProfileController::class, 'showPhoto']
)
    ->where(
        'filename',
        '[A-Za-z0-9._-]+'
    )
    ->name('profile.photo.public');

/*
|--------------------------------------------------------------------------
| Authenticated Shop API
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    ShopActive::class,
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Account / Authentication
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/me',
        [AuthController::class, 'me']
    );

    Route::post(
        '/logout',
        [AuthController::class, 'logout']
    );

    /*
    |--------------------------------------------------------------------------
    | Shop access diagnostic
    |--------------------------------------------------------------------------
    |
    | Safe authenticated endpoint used to verify Sanctum + ShopActive.
    | You may remove this route later after testing.
    |
    | GET /api/shop/access-check
    |
    */
    Route::get(
        '/shop/access-check',
        function (Request $request) {
            $shop = $request->attributes->get(
                'current_shop'
            );

            return response()->json([
                'success' => true,
                'message' => 'Shop access is working.',
                'user_id' => $request->user()?->id,
                'user_shop_id' =>
                    $request->user()?->shop_id,
                'shop' => $shop
                    ? [
                        'id' => $shop->id,
                        'name' => $shop->name,
                        'status' => $shop->status,
                    ]
                    : null,
            ]);
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Account Profile Edit
    |--------------------------------------------------------------------------
    */

    Route::put(
        '/profile',
        [AccountProfileController::class, 'update']
    );

    Route::patch(
        '/profile',
        [AccountProfileController::class, 'update']
    );

    /*
    |--------------------------------------------------------------------------
    | Profile Photo
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/profile/photo',
        [ProfileController::class, 'uploadPhoto']
    );

    Route::delete(
        '/profile/photo-delete',
        [ProfileController::class, 'deletePhoto']
    );

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    );

    /*
    |--------------------------------------------------------------------------
    | Shop / Settings
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/shop/profile',
        [ShopController::class, 'show']
    );

    Route::put(
        '/shop/profile',
        [ShopController::class, 'update']
    );

    Route::put(
        '/settings',
        [SettingsController::class, 'update']
    );

    /*
    |--------------------------------------------------------------------------
    | Product Categories
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'product-categories',
        ProductCategoryController::class
    );

    /*
    |--------------------------------------------------------------------------
    | Products
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'products',
        ProductController::class
    );

    /*
    |--------------------------------------------------------------------------
    | Stock
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/stock/history',
        [StockController::class, 'history']
    );

    Route::post(
        '/stock/in',
        [StockController::class, 'stockIn']
    );

    Route::post(
        '/stock/out',
        [StockController::class, 'stockOut']
    );

    /*
    |--------------------------------------------------------------------------
    | Suppliers
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'suppliers',
        SupplierController::class
    );

    /*
    |--------------------------------------------------------------------------
    | Customers
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'customers',
        CustomerController::class
    );

    Route::get(
        '/customers/{customer}/ledger',
        [CustomerController::class, 'ledger']
    );

    /*
    |--------------------------------------------------------------------------
    | Sales
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'sales',
        SaleController::class
    )->only([
        'index',
        'store',
        'show',
    ]);

    Route::post(
        '/sales/{sale}/return',
        [SaleController::class, 'returnSale']
    );

    /*
    |--------------------------------------------------------------------------
    | Dues
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dues',
        [DueController::class, 'index']
    );

    Route::post(
        '/dues/collect',
        [DueController::class, 'collect']
    );

    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/payments',
        [PaymentController::class, 'index']
    );

    /*
    |--------------------------------------------------------------------------
    | Repairs
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'repairs',
        RepairController::class
    );

    Route::patch(
        '/repairs/{repair}/status',
        [RepairController::class, 'updateStatus']
    );

    Route::post(
        '/repairs/{repair}/payment',
        [RepairController::class, 'collectPayment']
    );

    Route::post(
        '/repairs/{repair}/item-photo',
        [RepairPhotoController::class, 'store']
    );

    /*
    |--------------------------------------------------------------------------
    | Expenses
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'expenses',
        ExpenseController::class
    );

    /*
    |--------------------------------------------------------------------------
    | Staff
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'staff',
        StaffController::class
    );

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/reports/sales',
        [ReportController::class, 'sales']
    );

    Route::get(
        '/reports/profit',
        [ReportController::class, 'profit']
    );

    Route::get(
        '/reports/stock',
        [ReportController::class, 'stock']
    );

    Route::get(
        '/reports/customer-due',
        [ReportController::class, 'customerDue']
    );

    Route::get(
        '/reports/repair',
        [ReportController::class, 'repair']
    );

    Route::get(
        '/reports/expense',
        [ReportController::class, 'expense']
    );

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/notifications',
        [NotificationController::class, 'index']
    );

    Route::post(
        '/notifications/{notification}/read',
        [NotificationController::class, 'markRead']
    );
});
