<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PackageStationController;

Route::get('admin', fn () => redirect()->route('login'));
Route::prefix('admin')->group(function () {
    Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {

        Route::post('/logout', [DashboardController::class, 'logout']);
        Route::get('/my-profile', [UsersController::class, 'profile']);
        Route::post('/update-profile', [UsersController::class, 'updateProfile']);
        Route::get('/password-change', [UsersController::class, 'pwChange']);
        Route::post('/update-password', [UsersController::class, 'pwUpdate']);


        Route::get('package-station', [PackageStationController::class, 'index'])->name('package-station');

        Route::middleware('route.permission')->group(function () {
            Route::resource('/roles', RoleController::class);
            Route::resource('/users', UsersController::class);
            Route::get('activity-log', [ActivityLogController::class, 'index'])
                ->name('activity-log.view');

        });

    });
});
