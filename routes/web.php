<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataLatihController;
use App\Http\Controllers\UsulanLokasiController;
use App\Http\Controllers\HasilKlasifikasiController;
use App\Http\Controllers\ConfusionMatrixController;
use App\Http\Controllers\DecisionTreeController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\QgisApiController;

// QGIS Server API (Spatial Integration REST Endpoint - Tabel 3.14)
Route::get('/api/qgis/klasifikasi-geojson', [QgisApiController::class, 'getKlasifikasiGeoJson'])->name('api.qgis.geojson');
Route::post('/api/qgis/klasifikasi-geojson', [QgisApiController::class, 'getKlasifikasiGeoJson']);

// Kecamatan BPS API Routes
Route::get('/api/kecamatan', [\App\Http\Controllers\KecamatanController::class, 'index'])->name('api.kecamatan.index');
Route::get('/api/kecamatan/{id}/kepadatan', [\App\Http\Controllers\KecamatanController::class, 'getKepadatan'])->name('api.kecamatan.kepadatan');

// Wilayah Administrasi GIS API Routes
Route::get('/api/wilayah/search', [\App\Http\Controllers\KecamatanController::class, 'searchWilayah'])->name('api.wilayah.search');
Route::get('/api/wilayah/detect', [\App\Http\Controllers\KecamatanController::class, 'detectPointWilayah'])->name('api.wilayah.detect');

// Model C4.5 Status API
Route::get('/api/model-status', [DataLatihController::class, 'getModelStatus'])->name('api.model-status');

// Guest Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');

// Authenticated Routes
Route::middleware(['session.auth'])->group(function () {
    Route::get('/', function () {
        return redirect('/dashboard');
    });

    // Dashboard Route
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Kelola Data Latih Routes (UC-03 Inisialisasi Data Latih)
    Route::get('/data-latih', [DataLatihController::class, 'index'])->name('data-latih');
    Route::post('/data-latih/seed', [DataLatihController::class, 'seed'])->name('data-latih.seed');
    Route::post('/data-latih/import', [DataLatihController::class, 'import'])->name('data-latih.import');
    Route::post('/data-latih/clear', [DataLatihController::class, 'clear'])->name('data-latih.clear');
    Route::post('/data-latih/train', [DataLatihController::class, 'train'])->name('data-latih.train');
    Route::post('/data-latih', [DataLatihController::class, 'store']);
    Route::delete('/data-latih/{id}', [DataLatihController::class, 'destroy']);

    // Decision Tree Visualization Route
    Route::get('/decision-tree', [DecisionTreeController::class, 'index'])->name('decision-tree');

    // Usulan Lokasi Routes (UC-04 & UC-05)
    Route::get('/usulan-lokasi', [UsulanLokasiController::class, 'create'])->name('usulan-lokasi');
    Route::post('/usulan-lokasi', [UsulanLokasiController::class, 'store']);

    // Hasil Klasifikasi Map Route (UC-06 Hit API QGIS & Peta)
    Route::get('/hasil-klasifikasi', [HasilKlasifikasiController::class, 'index'])->name('hasil-klasifikasi');

    // Confusion Matrix Route (UC-07 Uji Confusion Matrix & K-Fold)
    Route::get('/confusion-matrix', [ConfusionMatrixController::class, 'index'])->name('confusion-matrix');

// Settings Routes
    Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan');
    Route::post('/pengaturan', [PengaturanController::class, 'update']);
});
