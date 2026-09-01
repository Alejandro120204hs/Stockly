<?php

use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Cliente\CajaController;
use App\Http\Controllers\Cliente\DashboardController;
use App\Http\Controllers\Cliente\FacturacionController;
use App\Http\Controllers\Cliente\GastoController;
use App\Http\Controllers\Cliente\InventarioController;
use App\Http\Controllers\Cliente\NominaController;
use App\Http\Controllers\Cliente\ProfileController as ClienteProfileController;
use App\Http\Controllers\Cliente\ReportesController;
use App\Http\Controllers\Cliente\ProveedorController;
use App\Http\Controllers\Cliente\VentasController;
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
    Route::get('/cliente/dashboard', [DashboardController::class, 'index'])->name('cliente.dashboard');

    Route::get('/cliente/ventas', [VentasController::class, 'index'])->name('cliente.ventas');
    Route::post('/cliente/ventas', [VentasController::class, 'store'])->name('cliente.ventas.store');
    Route::get('/cliente/ventas/{venta}/recibo', [VentasController::class, 'recibo'])->name('cliente.ventas.recibo');
    Route::post('/cliente/ventas/{venta}/anular', [VentasController::class, 'anular'])->name('cliente.ventas.anular');

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

    Route::get('/cliente/caja', [CajaController::class, 'index'])->name('cliente.caja');
    Route::post('/cliente/caja/abrir', [CajaController::class, 'abrir'])->name('cliente.caja.abrir');
    Route::post('/cliente/caja/{caja}/cerrar', [CajaController::class, 'cerrar'])->name('cliente.caja.cerrar');
    Route::post('/cliente/caja/{caja}/reabrir', [CajaController::class, 'reabrir'])->name('cliente.caja.reabrir');

    Route::get('/cliente/facturacion', [FacturacionController::class, 'index'])->name('cliente.facturacion');
    Route::post('/cliente/facturacion', [FacturacionController::class, 'store'])->name('cliente.facturacion.store');
    Route::get('/cliente/facturacion/{documento}/pdf', [FacturacionController::class, 'pdf'])->name('cliente.facturacion.pdf');
    Route::post('/cliente/facturacion/{documento}/anular', [FacturacionController::class, 'anular'])->name('cliente.facturacion.anular');

    Route::post('/cliente/facturacion/gastos', [FacturacionController::class, 'storeGasto'])->name('cliente.facturacion.gastos.store');
    Route::get('/cliente/facturacion/gastos/{documento}/pdf', [FacturacionController::class, 'pdfGasto'])->name('cliente.facturacion.gastos.pdf');
    Route::post('/cliente/facturacion/gastos/{documento}/anular', [FacturacionController::class, 'anularGasto'])->name('cliente.facturacion.gastos.anular');

    Route::get('/cliente/nomina', [NominaController::class, 'index'])->name('cliente.nomina');
    Route::post('/cliente/nomina/empleados', [NominaController::class, 'storeEmpleado'])->name('cliente.nomina.empleados.store');
    Route::put('/cliente/nomina/empleados/{empleado}', [NominaController::class, 'updateEmpleado'])->name('cliente.nomina.empleados.update');
    Route::delete('/cliente/nomina/empleados/{empleado}', [NominaController::class, 'destroyEmpleado'])->name('cliente.nomina.empleados.destroy');
    Route::post('/cliente/nomina/documentos', [NominaController::class, 'storeDocumentos'])->name('cliente.nomina.documentos.store');
    Route::get('/cliente/nomina/documentos/{documento}/pdf', [NominaController::class, 'pdfDocumento'])->name('cliente.nomina.documentos.pdf');
    Route::post('/cliente/nomina/documentos/{documento}/anular', [NominaController::class, 'anularDocumento'])->name('cliente.nomina.documentos.anular');

    Route::get('/cliente/gastos', [GastoController::class, 'index'])->name('cliente.gastos');
    Route::post('/cliente/gastos', [GastoController::class, 'store'])->name('cliente.gastos.store');

    Route::get('/cliente/reportes', [ReportesController::class, 'index'])->name('cliente.reportes');
    Route::get('/cliente/reportes/dia', [ReportesController::class, 'dia'])->name('cliente.reportes.dia');
    Route::get('/cliente/reportes/mes', [ReportesController::class, 'mes'])->name('cliente.reportes.mes');
    Route::get('/cliente/reportes/pdf', [ReportesController::class, 'pdf'])->name('cliente.reportes.pdf');

    Route::get('/cliente/perfil', [ClienteProfileController::class, 'edit'])->name('cliente.perfil');
    Route::patch('/cliente/perfil', [ClienteProfileController::class, 'updateInfo'])->name('cliente.perfil.update');
    Route::put('/cliente/perfil/password', [ClienteProfileController::class, 'updatePassword'])->name('cliente.perfil.password');
    Route::post('/cliente/perfil/logo', [ClienteProfileController::class, 'updateLogo'])->name('cliente.perfil.logo');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
