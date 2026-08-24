<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// La ruta genérica de Breeze ya no se usa como vista -solo redirige al
// panel correcto según el rol, para que "ya estoy logueado, llévame a
// donde sea" siempre caiga en el lugar correcto.
Route::get('/dashboard', function () {
    $rolNombre = auth()->user()->rol?->nombre;

    return $rolNombre === 'admin'
        ? redirect()->route('admin.dashboard')
        : redirect()->route('cliente.dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard-admin');
    })->name('admin.dashboard');

    Route::get('/admin/empresas', function () {
        return view('admin.empresas');
    })->name('admin.empresas');

    Route::get('/cliente/dashboard', function () {
        return view('cliente.dashboard-cliente');
    })->name('cliente.dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
