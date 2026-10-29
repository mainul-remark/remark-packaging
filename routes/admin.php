<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CartonController;
use App\Http\Controllers\Admin\CrossCheckController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PackageStationController;
use App\Http\Controllers\Admin\Products\BrandController;
use App\Http\Controllers\Admin\Products\ProductCategoryController;
use App\Http\Controllers\Admin\Products\ProductController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;

Route::get('admin', fn () => redirect()->route('login'));
Route::prefix('admin')->group(function () {
    Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {

        //        Route::post('/logout', [DashboardController::class, 'logout']);
        Route::get('/my-profile', [UsersController::class, 'profile']);
        Route::post('/update-profile', [UsersController::class, 'updateProfile']);
        Route::get('/password-change', [UsersController::class, 'pwChange']);
        Route::post('/update-password', [UsersController::class, 'pwUpdate']);

        Route::resource('orders', OrderController::class)->only(['index', 'show']);
        Route::resource('products', ProductController::class)->only(['index', 'show']);
        Route::resource('brands', BrandController::class)->only(['index', 'show']);
        Route::resource('product-categories', ProductCategoryController::class)->only(['index', 'show']);

        Route::get('package-station', [PackageStationController::class, 'index'])->name('package-station');
        Route::get('package-station/data', [PackageStationController::class, 'data'])->name('package-station.data');
        Route::get('package-station/find', [PackageStationController::class, 'find'])->name('package-station.find');
        Route::post('package-station/cartons', [PackageStationController::class, 'store'])->name('package-station.store');
        Route::post('/cartons/label-prints', [CartonController::class, 'printLabelsFirst'])->name('cartons.label-prints');
        Route::get('/carton-cross-check', [CrossCheckController::class, 'index'])->name('carton-cross-check');
        Route::get('/carton-cross-check/lookup', [CrossCheckController::class, 'lookup'])->name('carton-cross-check.lookup');
        Route::post('/carton-cross-check/{carton}/decision', [CrossCheckController::class, 'store'])->name('carton-cross-check.store');
        Route::get('/cartons', [CartonController::class, 'index'])->name('cartons.index');
        Route::get('/cartons/{carton}', [CartonController::class, 'show'])->name('cartons.show');
        Route::post('/cartons/{carton}/actions/{action}', [CartonController::class, 'act'])->name('cartons.act');
        Route::get('/cartons/{carton}/label', [CartonController::class, 'label'])->name('cartons.label');
        Route::post('/cartons/{carton}/label-prints', [CartonController::class, 'printLabel'])->name('cartons.label-print');

        Route::middleware('route.permission')->group(function () {
            Route::resource('/roles', RoleController::class);
            Route::resource('/users', UsersController::class);
            Route::get('activity-log', [ActivityLogController::class, 'index'])
                ->name('activity-log.view');

        });

    });
});
