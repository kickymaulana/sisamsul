<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Master\UserController;
use App\Http\Controllers\Master\RoleController;
use App\Http\Controllers\Master\DepartemenController;
use App\Http\Controllers\Master\SubDepartemenController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\FormulirController;
use App\Http\Controllers\DepartemenTerlibatController;
use App\Http\Controllers\TugasProduksiController;
use App\Http\Controllers\BelumDiterimaController;
use App\Http\Controllers\PersetujuanManagerController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\Master\SsoApplicationController;
use App\Http\Controllers\DaftarPenggunaController;
use App\Http\Controllers\NotifikasiController;



Route::get('/', [DashboardController::class, 'index'])->name('dashboard')->middleware('auth');
Route::get('testing', [DashboardController::class, 'testing'])->name('testing');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'index'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
    Route::get('auth/sso', [SsoController::class, 'redirect'])->name('sso.redirect')->block(45, 45);
    Route::get('auth/sso/callback', [SsoController::class, 'callback'])->name('sso.callback')->block(45, 45);
});

Route::post('logout', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');

Route::middleware('auth', 'role:admin')->group(function () {
    Route::get('master/sso-applications', [SsoApplicationController::class, 'index'])->name('sso-applications.index');
    Route::post('master/sso-applications/{application}/approve', [SsoApplicationController::class, 'approve'])->name('sso-applications.approve');
});

Route::middleware('auth', 'role:admin|Quality Control')->group(function () {
    // ... route lainnya
    Route::get('master/users', [UserController::class, 'index'])->name('users.index');
    Route::get('master/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('master/users/create', [UserController::class, 'store'])->name('users.store');
    Route::get('master/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('master/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('master/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('master/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('master/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('master/roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('master/roles/create', [RoleController::class, 'store'])->name('roles.store');
    Route::get('master/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('master/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('master/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

    Route::get('master/departemens', [DepartemenController::class, 'index'])->name('departemens.index');
    Route::get('master/departemens/create', [DepartemenController::class, 'create'])->name('departemens.create');
    Route::post('master/departemens/create', [DepartemenController::class, 'store'])->name('departemens.store');
    Route::get('master/departemens/{departemen}', [DepartemenController::class, 'show'])->name('departemens.show');
    Route::get('master/departemens/{departemen}/edit', [DepartemenController::class, 'edit'])->name('departemens.edit');
    Route::put('master/departemens/{departemen}', [DepartemenController::class, 'update'])->name('departemens.update');
    Route::delete('master/departemens/{departemen}', [DepartemenController::class, 'destroy'])->name('departemens.destroy');

    Route::get('master/sub-departemens', [SubDepartemenController::class, 'index'])->name('sub.departemens.index');
    Route::get('master/sub-departemens/create', [SubDepartemenController::class, 'create'])->name('sub.departemens.create');
    Route::post('master/sub-departemens/create', [SubDepartemenController::class, 'store'])->name('sub.departemens.store');
    Route::get('master/sub-departemens/{subDepartemen}', [SubDepartemenController::class, 'show'])->name('sub.departemens.show');
    Route::get('master/sub-departemens/{subDepartemen}/edit', [SubDepartemenController::class, 'edit'])->name('sub.departemens.edit');
    Route::put('master/sub-departemens/{subDepartemen}', [SubDepartemenController::class, 'update'])->name('sub.departemens.update');
    Route::delete('master/sub-departemens/{subDepartemen}', [SubDepartemenController::class, 'destroy'])->name('sub.departemens.destroy');


    Route::get('samples', [SampleController::class, 'index'])->name('samples.index');
    Route::get('samples/create', [SampleController::class, 'create'])->name('samples.create');
    Route::post('samples/create', [SampleController::class, 'store'])->name('samples.store');
    Route::get('samples/{sample}', [SampleController::class, 'show'])->name('samples.show');
    Route::get('samples/{sample}/edit', [SampleController::class, 'edit'])->name('samples.edit');
    Route::put('samples/{sample}', [SampleController::class, 'update'])->name('samples.update');
    Route::delete('samples/{sample}', [SampleController::class, 'destroy'])->name('samples.destroy');

    Route::get('formulirs', [FormulirController::class, 'index'])->name('formulirs.index');
    Route::get('formulirs/create', [FormulirController::class, 'create'])->name('formulirs.create');
    Route::post('formulirs/create', [FormulirController::class, 'store'])->name('formulirs.store');
    Route::get('formulirs/{formulir}', [FormulirController::class, 'show'])->name('formulirs.show');
    Route::get('formulirs/{formulir}/edit', [FormulirController::class, 'edit'])->name('formulirs.edit');
    Route::get('formulirs/{formulir}/preview', [FormulirController::class, 'preview'])->name('formulirs.preview');
    Route::put('formulirs/{formulir}', [FormulirController::class, 'update'])->name('formulirs.update');
    Route::delete('formulirs/{formulir}', [FormulirController::class, 'destroy'])->name('formulirs.destroy');

    Route::get('formulirs/{formulir}/departemen/create', [DepartemenTerlibatController::class, 'create'])
        ->name('formulirs.departemen.create');
    Route::post('formulirs/{formulir}/departemen', [DepartemenTerlibatController::class, 'store'])
        ->name('formulirs.departemen.store');
    Route::get('formulirs/{formulir}/departemen/{departemen_terlibat}/edit', [DepartemenTerlibatController::class, 'edit'])
        ->name('formulirs.departemen.edit');
    Route::put('formulirs/{formulir}/departemen/{departemen_terlibat}', [DepartemenTerlibatController::class, 'update'])
        ->name('formulirs.departemen.update');
    Route::delete('formulirs/{formulir}/departemen/{departemen_terlibat}', [DepartemenTerlibatController::class, 'destroy'])
        ->name('formulirs.departemen.destroy');
    Route::patch('formulirs/{formulir}/departemen/{departemen_terlibat}/paraf-qc', [DepartemenTerlibatController::class, 'parafQc'])
        ->name('formulirs.departemen.paraf-qc');

});

Route::middleware('auth')->group(function () {
    Route::get('belum-diterima', [BelumDiterimaController::class, 'index'])->name('belum.diterima.index');
    Route::get('tugas-produksi', [TugasProduksiController::class, 'index'])->name('tugas.produksi.index');
    Route::get('tugas-produksi/{departemen_terlibat}/edit', [TugasProduksiController::class, 'edit'])->name('tugas.produksi.edit');
    Route::get('tugas-produksi/{departemen_terlibat}/show', [TugasProduksiController::class, 'show'])->name('tugas.produksi.show');
    Route::put('tugas-produksi/{departemen_terlibat}', [TugasProduksiController::class, 'update'])->name('tugas.produksi.update');
    Route::patch('tugas-produksi/{departemen_terlibat}/terima', [TugasProduksiController::class, 'terima'])
    ->name('tugas.produksi.terima');
    Route::patch('formulirs/{formulir}/departemen/{departemen_terlibat}/paraf-spv', [TugasProduksiController::class, 'parafSpv'])
    ->name('formulirs.departemen.paraf-spv');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('daftar-pengguna', [DaftarPenggunaController::class, 'index'])->name('daftar.pengguna.index');
    Route::get('notifikasi/index', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::get('notifikasi/{id}/baca', [NotifikasiController::class, 'bacaDanRedirect'])->name('notifikasi.baca');
    Route::post('notifikasi/tandai-semua-dibaca', [NotifikasiController::class, 'tandai_semua_dibaca'])->name('notifikasi.tandai-semua-dibaca');
});

Route::middleware('auth', 'role:admin|Quality Control|QC Manager|Factory Manager|General Manager')->group(function () {

    Route::get('persetujuan-manager', [PersetujuanManagerController::class, 'index'])->name('persetujuan.manager.index');
    Route::get('persetujuan-manager/{formulir}', [PersetujuanManagerController::class, 'show'])->name('persetujuan.manager.show');
    Route::post('persetujuan-manager/{formulir}/paraf-pemeriksa', [PersetujuanManagerController::class, 'parafPemeriksa'])->name('persetujuan.manager.paraf_pemeriksa');
    Route::post('persetujuan-manager/{formulir}/paraf-penyetuju', [PersetujuanManagerController::class, 'parafPenyetuju'])->name('persetujuan.manager.paraf_penyetuju');

});

Route::get('pdf/formulir/{formulir}', [PdfController::class, 'pdf'])->name('pdf.formulir');
