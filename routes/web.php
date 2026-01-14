<?php

use App\Http\Controllers\Ecer\EcerController;
use App\Http\Controllers\Ecer\ProdukEcerController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return inertia('Home');
})->name('home');

Route::prefix('auth')
    ->controller(\App\Http\Controllers\AuthController::class)
    ->group(function () {
        Route::get('/login-page', 'pageLogin')->name('login')->middleware('guest');
        Route::post('/login', 'login')->name('attempt')->middleware('guest');
        Route::post('/logout', 'logout')->name('logout')->middleware('auth');
    });

Route::prefix('dashboard')
    ->name('dashboard.')
    ->middleware(['auth', 'role:owner'])
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\DashboardController::class, 'index'])->name('index');

        Route::prefix('master')
            ->name('master.')
            ->group(function () {

                Route::get('/', [\App\Http\Controllers\DashboardController::class, 'master'])->name('products.index');

                Route::prefix('products')
                    ->name('products.')
                    ->controller(\App\Http\Controllers\Master\ProductController::class)
                    ->group(function () {
                        Route::post('/', 'store')->name('store');
                        Route::put('/{produk}', 'update')->name('update');
                        Route::delete('/{produk}', 'destroy')->name('destroy');
                        Route::get('/colors', 'colorsByName')->name('colors');
                    });

                Route::prefix('categories')
                    ->name('categories.')
                    ->controller(\App\Http\Controllers\Master\CategoryController::class)
                    ->group(function () {
                        Route::post('/', 'store')->name('store');
                        Route::put('/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'destroy')->name('destroy');
                    });

                Route::prefix('suppliers')
                    ->name('suppliers.')
                    ->controller(\App\Http\Controllers\Master\SupplierController::class)
                    ->group(function () {
                        Route::post('/', 'store')->name('store');
                        Route::put('/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'destroy')->name('destroy');
                    });

                Route::prefix('pelanggan')
                    ->name('pelanggan.')
                    ->controller(\App\Http\Controllers\Master\PelangganController::class)
                    ->group(function () {
                        Route::post('/', 'store')->name('store');
                        Route::put('/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'destroy')->name('destroy');
                    });

                Route::prefix('gudang')
                    ->name('gudang.')
                    ->controller(\App\Http\Controllers\Master\GudangController::class)
                    ->group(function () {
                        Route::post('/', 'store')->name('store');
                        Route::put('/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'destroy')->name('destroy');
                    });
            });
    });

// Admin Ecer Dashboard
Route::prefix('ecer')
    ->name('ecer.')
    ->middleware(['auth', 'role:admin-ecer'])
    ->group(function () {
        Route::get('/', [EcerController::class, 'index'])->name('index');
        Route::prefix('master')
            ->name('master.')
            ->group(function () {

                Route::get('/', [EcerController::class, 'master'])->name('products.index');

                Route::prefix('products')
                    ->name('products.')
                    ->controller(ProdukEcerController::class)
                    ->group(function () {
                        Route::post('/', 'store')->name('store');
                        Route::put('/{produk}', 'update')->name('update');
                        Route::delete('/{produk}', 'destroy')->name('destroy');
                        Route::get('/colors', 'colorsByName')->name('colors');
                    });

                Route::prefix('categories')
                    ->name('categories.')
                    ->controller(\App\Http\Controllers\Master\CategoryController::class)
                    ->group(function () {
                        Route::post('/', 'store')->name('store');
                        Route::put('/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'destroy')->name('destroy');
                    });

                Route::prefix('suppliers')
                    ->name('suppliers.')
                    ->controller(\App\Http\Controllers\Master\SupplierController::class)
                    ->group(function () {
                        Route::post('/', 'store')->name('store');
                        Route::put('/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'destroy')->name('destroy');
                    });

                Route::prefix('pelanggan')
                    ->name('pelanggan.')
                    ->controller(\App\Http\Controllers\Master\PelangganController::class)
                    ->group(function () {
                        Route::post('/', 'store')->name('store');
                        Route::put('/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'destroy')->name('destroy');
                    });

                Route::prefix('gudang')
                    ->name('gudang.')
                    ->controller(\App\Http\Controllers\Master\GudangController::class)
                    ->group(function () {
                        Route::post('/', 'store')->name('store');
                        Route::put('/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'destroy')->name('destroy');
                    });
            });
    });

// Admin Grosir Dashboard
Route::prefix('grosir')
    ->name('grosir.')
    ->middleware(['auth', 'role:admin-grosir'])
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Grosir\GrosirController::class, 'index'])->name('index');
        Route::prefix('master')
            ->name('master.')
            ->group(function () {

                Route::get('/', [\App\Http\Controllers\Grosir\GrosirController::class, 'master'])->name('products.index');

                Route::prefix('products')
                    ->name('products.')
                    ->controller(\App\Http\Controllers\Grosir\ProdukGrosirController::class)
                    ->group(function () {
                        Route::post('/', 'store')->name('store');
                        Route::put('/{produk}', 'update')->name('update');
                        Route::delete('/{produk}', 'destroy')->name('destroy');
                        Route::get('/colors', 'colorsByName')->name('colors');
                    });

                Route::prefix('categories')
                    ->name('categories.')
                    ->controller(\App\Http\Controllers\Grosir\CategoryGrosirController::class)
                    ->group(function () {
                        Route::post('/', 'store')->name('store');
                        Route::put('/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'destroy')->name('destroy');
                    });

                Route::prefix('suppliers')
                    ->name('suppliers.')
                    ->controller(\App\Http\Controllers\Master\SupplierController::class)
                    ->group(function () {
                        Route::post('/', 'store')->name('store');
                        Route::put('/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'destroy')->name('destroy');
                    });

                Route::prefix('pelanggan')
                    ->name('pelanggan.')
                    ->controller(\App\Http\Controllers\Master\PelangganController::class)
                    ->group(function () {
                        Route::post('/', 'store')->name('store');
                        Route::put('/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'destroy')->name('destroy');
                    });

                Route::prefix('gudang')
                    ->name('gudang.')
                    ->controller(\App\Http\Controllers\Master\GudangController::class)
                    ->group(function () {
                        Route::post('/', 'store')->name('store');
                        Route::put('/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'destroy')->name('destroy');
                    });
            });
    });
