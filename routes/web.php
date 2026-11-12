<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Entry point: login page first.
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->dashboardRoute())
        : redirect()->route('login');
});

// ---- Guests only ----
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// ---- Logged-in users ----
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Chart data (both roles)
    Route::get('/dashboard/sales-chart', [DashboardController::class, 'salesChart'])->name('dashboard.sales-chart');

    // Admin only
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    });

    // ---- Stock monitoring (admin and staff; Stock Out is restricted to admin inside the request) ----
    Route::middleware('role:admin,staff')->group(function () {
        Route::get('/stock-monitoring', [StockMovementController::class, 'index'])->name('stock.index');
        Route::post('/stock-monitoring', [StockMovementController::class, 'store'])->name('stock.store');
    });

    // ---- Sales recording (admin and staff; staff only see their own sales) ----
    Route::middleware('role:admin,staff')->prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SalesController::class, 'index'])->name('index');
        Route::get('/create', [SalesController::class, 'create'])->name('create');
        Route::get('/products', [SalesController::class, 'products'])->name('products');
        Route::post('/', [SalesController::class, 'store'])->name('store');
        Route::get('/{sale}', [SalesController::class, 'show'])->whereNumber('sale')->name('show');
        Route::get('/{sale}/receipt', [SalesController::class, 'receipt'])->whereNumber('sale')->name('receipt');
    });

    // ---- Reports (admin and staff; staff cannot open the damaged/movement reports and only see their own sales) ----
    Route::middleware('role:admin,staff')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export/{format}', [ReportController::class, 'export'])->whereIn('format', ['pdf', 'excel', 'print'])->name('export');
    });

    // ---- User management and activity logs (admin only; activity logs are read-only, no edit/delete routes) ----
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])->whereNumber('user')->name('users.show');
        Route::put('/users/{user}', [UserController::class, 'update'])->whereNumber('user')->name('users.update');
        Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])->whereNumber('user')->name('users.status');
        Route::put('/users/{user}/password', [UserController::class, 'resetPassword'])->whereNumber('user')->name('users.password');

        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });

    // ---- Category management (admin only) ----
    Route::middleware('role:admin')->group(function () {
        Route::resource('categories', CategoryController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    });

    // ---- Clothing inventory ----
    // Admin: full management
    Route::middleware('role:admin')->group(function () {
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->whereNumber('product')->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->whereNumber('product')->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->whereNumber('product')->name('products.destroy');
    });
    // Admin and staff: view only
    Route::middleware('role:admin,staff')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/{product}', [ProductController::class, 'show'])->whereNumber('product')->name('products.show');
    });

    // Staff only
    Route::middleware('role:staff')->prefix('staff')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('staff.dashboard');
    });
});
