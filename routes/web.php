<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Dashboard\SettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Ecer\BankController;
use App\Http\Controllers\Ecer\EcerAkunController;
use App\Http\Controllers\Ecer\EcerController;
use App\Http\Controllers\Ecer\EcerSettingController;
use App\Http\Controllers\Ecer\PembayaranEcerController;
use App\Http\Controllers\Ecer\PenjualanEcerController;
use App\Http\Controllers\Ecer\ProdukEcerController;
use App\Http\Controllers\Ecer\StokEcerController;
use App\Http\Controllers\Ecer\TransaksiPenjualanEceranController;
use App\Http\Controllers\Grosir\AkunController;
use App\Http\Controllers\Grosir\CategoryGrosirController;
use App\Http\Controllers\Grosir\GrosirController;
use App\Http\Controllers\Grosir\GudangGrosirController;
use App\Http\Controllers\Grosir\MasterGrosirController;
use App\Http\Controllers\Grosir\PelangganGrosirController;
use App\Http\Controllers\Grosir\PenerimaanController;
use App\Http\Controllers\Grosir\PenjualanGrosirController;
use App\Http\Controllers\Grosir\ProdukGrosirController;
use App\Http\Controllers\Grosir\SettingController as GrosirSettingController;
use App\Http\Controllers\Grosir\StokGrosirController;
use App\Http\Controllers\Grosir\SupplierGrosirController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\GudangController;
use App\Http\Controllers\Master\PelangganController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\Setting\BankPembayaranController;
use App\Models\Penerimaan;
use Faker\Guesser\Name;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::middleware(['auth'])->group(function () {
    // Tambahkan ini: Route khusus pencarian produk
    Route::get('/api/produk-search', [PenjualanGrosirController::class, 'searchProduk'])->name('api.produk.search');
});



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

Route::post('/print-thermal', [PrintController::class, 'print'])
    ->name('print-thermal');


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
        Route::prefix('produks')
            ->name('produks.')
            ->controller(ProdukEcerController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
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

        Route::prefix('penjualan')
            ->name('penjualan.')
            ->controller(PenjualanEcerController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/print-tagihan/{id}', 'printTagihan')->name('print-tagihan');


                Route::prefix('transaksi')
                    ->name('transaksi.')
                    ->controller(TransaksiPenjualanEceranController::class)
                    ->group(function () {
                        Route::get('/', 'index')->name('index');
                        Route::get('/list', 'list')->name('list');
                        Route::post('/draft', 'storeDraft')->name('draft');
                        Route::post('/item', 'simpanItems')->name('item');
                        Route::post('/update/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'hapusPenjualan')->name('hapus');
                    });

                Route::prefix('pembayaran')
                    ->name('pembayaran.')
                    ->controller(PembayaranEcerController::class)
                    ->group(function () {
                        Route::get('/', 'index')->name('list-pembayaran');
                        Route::get('/{id}', 'preparePembayaran')->name('prepare');
                        Route::post('/{penjualan}/bayar', 'bayar')->name('bayar');
                        Route::get('/print/{id}', 'showPrint')->name('print');
                    });
            });

        Route::prefix('stok')
            ->name('stok.')
            ->controller(StokEcerController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'createTransfer')->name('create-transfer');
                Route::post('/', 'storeTransfer')->name('store-transfer');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });

        Route::prefix('settings')
            ->name('settings.')
            ->controller(EcerSettingController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'createTransfer')->name('create-transfer');
                Route::post('/', 'storeTransfer')->name('store-transfer');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');

                Route::prefix('akun')
                    ->name('akun.')
                    ->controller(EcerAkunController::class)
                    ->group(function () {
                        Route::get('/', 'index')->name('index');
                        Route::get('/create', 'create')->name('create');
                        Route::post('/', 'store')->name('store');
                        Route::put('/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'destroy')->name('destroy');
                    });
                Route::prefix('bank')
                    ->name('bank.')
                    ->controller(BankController::class)
                    ->group(function () {
                        Route::get('/', 'index')->name('index');
                        Route::post('/', 'store')->name('store');
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

                Route::get('/', [\App\Http\Controllers\Grosir\MasterGrosirController::class, 'index'])->name('products.index');
                Route::prefix('products')
                    ->name('products.')
                    ->controller(\App\Http\Controllers\Grosir\ProdukGrosirController::class)
                    ->group(function () {
                        Route::get('/', 'index')->name('index');
                        Route::post('/', 'store')->name('store');
                        Route::put('/{produk}', 'update')->name('update');
                        Route::delete('/{produk}', 'destroy')->name('destroy');
                        Route::get('/colors', 'colorsByName')->name('colors');
                    });

                Route::prefix('categories')
                    ->name('categories.')
                    ->controller(\App\Http\Controllers\Grosir\CategoryGrosirController::class)
                    ->group(function () {
                        Route::get('/', 'index')->name('index');
                        Route::post('/', 'store')->name('store');
                        Route::put('/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'destroy')->name('destroy');
                    });

                Route::prefix('suppliers')
                    ->name('suppliers.')
                    ->controller(\App\Http\Controllers\Grosir\SupplierGrosirController::class)
                    ->group(function () {
                        Route::get('/', 'index')->name('index');
                        Route::post('/', 'store')->name('store');
                        Route::put('/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'destroy')->name('destroy');
                    });

                Route::prefix('pelanggan')
                    ->name('pelanggan.')
                    ->controller(\App\Http\Controllers\Grosir\PelangganGrosirController::class)
                    ->group(function () {
                        Route::get('/', 'index')->name('index');
                        Route::post('/', 'store')->name('store');
                        Route::put('/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'destroy')->name('destroy');
                    });

                Route::prefix('gudang')
                    ->name('gudang.')
                    ->controller(\App\Http\Controllers\Grosir\GudangGrosirController::class)
                    ->group(function () {
                        Route::get('/', 'index')->name('index');
                        Route::post('/', 'store')->name('store');
                        Route::put('/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'destroy')->name('destroy');
                    });
            });
        Route::prefix('penerimaan')
            ->name('penerimaan.')
            ->controller(PenerimaanController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/store', 'store')->name('store');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::get('/update-data', 'updateData')->name('update-data');
                Route::get('/search-produk', 'searchProduk')->name('search-produk');
            });


        Route::prefix('penjualan')
            ->name('penjualan.')
            ->controller(PenjualanGrosirController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/store', 'store')->name('store');
                Route::get('/{id}/show', 'show')->name('show');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::get('/update-data', 'updateData')->name('update-data');
                Route::post('/direct-print/{id}', 'printNota')->name('direct-print');
            });
        Route::prefix('stok')
            ->name('stok.')
            ->controller(StokGrosirController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                // Route::get('/create', 'createTransfer')->name('create-transfer');
                // Route::post('/', 'storeTransfer')->name('store-transfer');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });
        Route::prefix('settings')
            ->name('settings.')
            ->controller(GrosirSettingController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'createTransfer')->name('create-transfer');
                Route::post('/', 'storeTransfer')->name('store-transfer');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');

                Route::prefix('akun')
                    ->name('akun.')
                    ->controller(AkunController::class)
                    ->group(function () {
                        Route::get('/', 'index')->name('index');
                        Route::get('/create', 'create')->name('create');
                        Route::post('/', 'store')->name('store');
                        Route::put('/{id}', 'update')->name('update');
                        Route::delete('/{id}', 'destroy')->name('destroy');
                    });
            });
    });

Route::get('/grosir/comparing', [PenerimaanController::class, 'comparing'])->name('comparing');
