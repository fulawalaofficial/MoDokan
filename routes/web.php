<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/dashboard');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::view('/login', 'admin.login')->name('login');
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::view('/shops', 'admin.shops.index')->name('shops.index');
    Route::view('/categories', 'admin.categories.index')->name('categories.index');
    Route::view('/subscriptions', 'admin.subscriptions.index')->name('subscriptions.index');
});
