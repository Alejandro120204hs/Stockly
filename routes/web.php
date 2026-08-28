<?php

use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Cliente\InventarioController;
use App\Http\Controllers\Cliente\ProveedorController;
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

Route::middleware(['auth', 'rol:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard-admin');
    })->name('admin.dashboard');

    Route::get('/admin/empresas', function () {
        return view('admin.empresas');
    })->name('admin.empresas');

    Route::get('/admin/pagos', function () {
        return view('admin.pagos');
    })->name('admin.pagos');

    Route::get('/admin/modulos', function () {
        return view('admin.modulos');
    })->name('admin.modulos');

    Route::get('/admin/perfil', [AdminProfileController::class, 'edit'])->name('admin.perfil');
    Route::patch('/admin/perfil', [AdminProfileController::class, 'updateInfo'])->name('admin.perfil.update');
    Route::put('/admin/perfil/password', [AdminProfileController::class, 'updatePassword'])->name('admin.perfil.password');
});

// "rol:cliente" evita que una cuenta admin (sin empresa_id) pueda entrar
// aquí -el Global Scope por sí solo no alcanza a cubrir ese caso, ver
// App\Models\Scopes\EmpresaScope.
Route::middleware(['auth', 'rol:cliente'])->group(function () {
    Route::get('/cliente/dashboard', function () {
        return view('cliente.dashboard-cliente');
    })->name('cliente.dashboard');

    Route::get('/cliente/ventas', function () {
        return view('cliente.ventas');
    })->name('cliente.ventas');

    Route::get('/cliente/inventario', [InventarioController::class, 'index'])->name('cliente.inventario');
    Route::post('/cliente/inventario/categorias', [InventarioController::class, 'storeCategoria'])->name('cliente.inventario.categorias.store');
    Route::post('/cliente/inventario/productos', [InventarioController::class, 'storeProducto'])->name('cliente.inventario.productos.store');
    Route::put('/cliente/inventario/productos/{producto}', [InventarioController::class, 'updateProducto'])->name('cliente.inventario.productos.update');
    Route::delete('/cliente/inventario/productos/{producto}', [InventarioController::class, 'destroyProducto'])->name('cliente.inventario.productos.destroy');
    Route::put('/cliente/inventario/categorias', [InventarioController::class, 'updateCategoria'])->name('cliente.inventario.categorias.update');
    Route::delete('/cliente/inventario/categorias', [InventarioController::class, 'destroyCategoria'])->name('cliente.inventario.categorias.destroy');
    Route::post('/cliente/inventario/compras', [InventarioController::class, 'storeCompra'])->name('cliente.inventario.compras.store');
    Route::post('/cliente/inventario/transferencias', [InventarioController::class, 'transferir'])->name('cliente.inventario.transferencias.store');

    Route::get('/cliente/proveedores', [ProveedorController::class, 'index'])->name('cliente.proveedores');
    Route::post('/cliente/proveedores', [ProveedorController::class, 'store'])->name('cliente.proveedores.store');
    Route::put('/cliente/proveedores/{proveedor}', [ProveedorController::class, 'update'])->name('cliente.proveedores.update');
    Route::delete('/cliente/proveedores/{proveedor}', [ProveedorController::class, 'destroy'])->name('cliente.proveedores.destroy');

    Route::get('/cliente/caja', function () {
        return view('cliente.caja');
    })->name('cliente.caja');

    Route::get('/cliente/facturacion', function () {
        return view('cliente.facturacion');
    })->name('cliente.facturacion');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
