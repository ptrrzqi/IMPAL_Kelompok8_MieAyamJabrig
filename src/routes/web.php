<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MataKuliahController;
use App\Http\Controllers\MateriController;
use App\Http\Controllers\PengumpulanTugasController;
use App\Http\Controllers\TugasController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - LMS Prototipe
|--------------------------------------------------------------------------
*/

// Root redirect
Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Guest Authentication Routes (FR-01 / L001)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

// Authenticated Routes (Protected by session guard)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // FR-05 Mata Kuliah (P003 - P006)
    Route::get('/matakuliah/{mataKuliah}', [MataKuliahController::class, 'show'])->name('matakuliah.show');
    Route::post('/matakuliah/{mataKuliah}/materi', [MateriController::class, 'store'])->name('materi.store');
    Route::post('/matakuliah/{mataKuliah}/tugas', [TugasController::class, 'store'])->name('tugas.store');

    Route::get('/materi/{materi}/download', [MateriController::class, 'download'])->name('materi.download');
    Route::delete('/materi/{materi}', [MateriController::class, 'destroy'])->name('materi.destroy');

    Route::get('/tugas/{tugas}', [TugasController::class, 'show'])->name('tugas.show');
    Route::get('/tugas/{tugas}/lampiran', [TugasController::class, 'lampiran'])->name('tugas.lampiran');
    Route::delete('/tugas/{tugas}', [TugasController::class, 'destroy'])->name('tugas.destroy');
    Route::post('/tugas/{tugas}/pengumpulan', [PengumpulanTugasController::class, 'store'])->name('pengumpulan.store');

    Route::get('/pengumpulan/{pengumpulan}/download', [PengumpulanTugasController::class, 'download'])->name('pengumpulan.download');
    Route::patch('/pengumpulan/{pengumpulan}/nilai', [PengumpulanTugasController::class, 'nilai'])->name('pengumpulan.nilai');
});
