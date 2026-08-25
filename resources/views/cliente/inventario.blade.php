<x-cliente-layout title="Inventario">

    {{-- Inventario — SOLO FRONTEND, datos mock (no hay backend de
         productos/inventario todavía). Ojo con la regla de negocio: una
         compra SIEMPRE entra a bodega, nunca directo a vitrina -mover
         stock de bodega a vitrina es una acción manual aparte (acá el
         modal "Transferir"). Si la compra es con proveedor y factura
         electrónica, el QR/CUFE solo VALIDA la factura ante la DIAN -no
         trae el detalle de productos, así que las líneas siempre se
         registran a mano en cualquiera de los dos casos (proveedor o
         compra informal). --}}
    @php
        $categorias = ['Aguardientes', 'Rones', 'Cervezas', 'Whiskys', 'Vinos'];
        $unidades = ['Botella', 'Lata', 'Caja', 'Paquete', 'Unidad', 'Kilogramo', 'Litro'];

        $productos = [
            ['id' => 1, 'nombre' => 'Aguardiente Antioqueño 750ml', 'categoria' => 'Aguardientes', 'precioCosto' => 31000, 'precioVenta' => 45000, 'unidad' => 'Botella', 'stockVitrina' => 18, 'stockBodega' => 42],
            ['id' => 2, 'nombre' => 'Ron Medellín Añejo 750ml', 'categoria' => 'Rones', 'precioCosto' => 44000, 'precioVenta' => 62000, 'unidad' => 'Botella', 'stockVitrina' => 9, 'stockBodega' => 21],
            ['id' => 3, 'nombre' => 'Cerveza Águila Lata 330ml', 'categoria' => 'Cervezas', 'precioCosto' => 2300, 'precioVenta' => 3500, 'unidad' => 'Lata', 'stockVitrina' => 140, 'stockBodega' => 320],
            ['id' => 4, 'nombre' => 'Whisky Old Parr 750ml', 'categoria' => 'Whiskys', 'precioCosto' => 138000, 'precioVenta' => 185000, 'unidad' => 'Botella', 'stockVitrina' => 3, 'stockBodega' => 7],
            ['id' => 5, 'nombre' => 'Vino Santa Rita 750ml', 'categoria' => 'Vinos', 'precioCosto' => 40000, 'precioVenta' => 58000, 'unidad' => 'Botella', 'stockVitrina' => 6, 'stockBodega' => 14],
            ['id' => 6, 'nombre' => 'Cerveza Club Colombia 330ml', 'categoria' => 'Cervezas', 'precioCosto' => 2700, 'precioVenta' => 4200, 'unidad' => 'Lata', 'stockVitrina' => 95, 'stockBodega' => 210],
            ['id' => 7, 'nombre' => 'Ron Viejo de Caldas 750ml', 'categoria' => 'Rones', 'precioCosto' => 37000, 'precioVenta' => 54000, 'unidad' => 'Botella', 'stockVitrina' => 11, 'stockBodega' => 26],
        ];

        $compras = [
            ['id' => 5, 'fecha' => '24 ago 2026, 10:15 a.m.', 'tipo' => 'proveedor', 'proveedor' => 'Licorera Continental S.A.S.', 'facturaEstado' => 'validada', 'cufe' => 'CUFE-9F82-1123-AAB2', 'lineas' => [['productoId' => 1, 'nombre' => 'Aguardiente Antioqueño 750ml', 'cantidad' => 24, 'costo' => 31000], ['productoId' => 2, 'nombre' => 'Ron Medellín Añejo 750ml', 'cantidad' => 12, 'costo' => 44000]]],
            ['id' => 4, 'fecha' => '19 ago 2026, 3:40 p.m.', 'tipo' => 'informal', 'proveedor' => null, 'facturaEstado' => 'sin_factura', 'cufe' => null, 'lineas' => [['productoId' => 3, 'nombre' => 'Cerveza Águila Lata 330ml', 'cantidad' => 100, 'costo' => 2300]]],
            ['id' => 3, 'fecha' => '12 ago 2026, 9:05 a.m.', 'tipo' => 'proveedor', 'proveedor' => 'Distribuidora El Manantial', 'facturaEstado' => 'por_validar', 'cufe' => 'CUFE-4471-0056-CD31', 'lineas' => [['productoId' => 4, 'nombre' => 'Whisky Old Parr 750ml', 'cantidad' => 6, 'costo' => 138000], ['productoId' => 5, 'nombre' => 'Vino Santa Rita 750ml', 'cantidad' => 8, 'costo' => 40000]]],
            ['id' => 2, 'fecha' => '05 ago 2026, 11:50 a.m.', 'tipo' => 'informal', 'proveedor' => null, 'facturaEstado' => 'sin_factura', 'cufe' => null, 'lineas' => [['productoId' => 6, 'nombre' => 'Cerveza Club Colombia 330ml', 'cantidad' => 80, 'costo' => 2700]]],
            ['id' => 1, 'fecha' => '29 jul 2026, 4:20 p.m.', 'tipo' => 'proveedor', 'proveedor' => 'Licorera Continental S.A.S.', 'facturaEstado' => 'validada', 'cufe' => 'CUFE-2201-9987-EE10', 'lineas' => [['productoId' => 7, 'nombre' => 'Ron Viejo de Caldas 750ml', 'cantidad' => 15, 'costo' => 37000]]],
        ];

        foreach ($compras as &$compra) {
            $compra['total'] = collect($compra['lineas'])->sum(fn ($l) => $l['cantidad'] * $l['costo']);
        }
        unset($compra);

        $facturaLabels = [
            'validada' => 'Validada',
            'por_validar' => 'Por validar',
            'sin_factura' => 'Sin factura',
        ];

        $facturaPillClass = [
            'validada' => 'status-pill--facturada',
            'por_validar' => 'status-pill--pendiente',
            'sin_factura' => 'status-pill--sin-facturar',
        ];

        $valorBodega = collect($productos)->sum(fn ($p) => $p['stockBodega'] * $p['precioCosto']);
        $valorVitrina = collect($productos)->sum(fn ($p) => $p['stockVitrina'] * $p['precioCosto']);
        $comprasEsteMes = collect($compras)->filter(fn ($c) => str_contains($c['fecha'], 'ago 2026'))->count();
    @endphp

    <div class="cliente-page-header cliente-reveal cliente-reveal-1" style="display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div>
            <p class="cliente-page-header__eyebrow">Tu negocio</p>
            <h1 class="cliente-page-header__title">Vitrina y bodega</h1>
            <p class="cliente-page-header__date">{{ count($productos) }} productos en catálogo</p>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="button" class="cliente-btn-ghost" id="nuevoProductoBtn">+ Nuevo producto</button>
            <button type="button" class="cliente-btn-primary" id="registrarCompraBtn">+ Registrar compra</button>
        </div>
    </div>

    <!-- ==========================================================
         STAT CARDS
         ========================================================== -->
    <section class="stat-grid cliente-reveal cliente-reveal-2">
        <div class="stat-card stat-card--sage">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 8 12 3 3 8v8l9 5 9-5V8Z"/>
                    <path d="M3 8l9 5 9-5M12 13v8"/>
                </svg>
            </div>
            <span class="stat-card__value" id="statProductos" data-count="{{ count($productos) }}">0</span>
            <span class="stat-card__label">Productos en catálogo</span>
            <span class="stat-card__meta" id="statCategoriasMeta">{{ count($categorias) }} categorías</span>
        </div>

        <div class="stat-card stat-card--sand">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M3 9h18M9 21V9"/>
                </svg>
            </div>
            <span class="stat-card__value" id="statValorBodega" data-count="{{ $valorBodega }}" data-prefix="$">$0</span>
            <span class="stat-card__label">Valor en bodega (costo)</span>
            <span class="stat-card__meta">Inventario de reserva</span>
        </div>

        <div class="stat-card stat-card--mist">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 21V10M18 21V3M12 21v-7"/>
                </svg>
            </div>
            <span class="stat-card__value" id="statValorVitrina" data-count="{{ $valorVitrina }}" data-prefix="$">$0</span>
            <span class="stat-card__label">Valor en vitrina (costo)</span>
            <span class="stat-card__meta">Disponible para vender</span>
        </div>

        <div class="stat-card stat-card--sage">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="20" r="1"/>
                    <circle cx="18" cy="20" r="1"/>
                    <path d="M3 4h2l2.3 11.4a2 2 0 0 0 2 1.6h7.4a2 2 0 0 0 2-1.6L21 8H6"/>
                </svg>
            </div>
            <span class="stat-card__value" id="statComprasMes" data-count="{{ $comprasEsteMes }}">0</span>
            <span class="stat-card__label">Compras este mes</span>
            <span class="stat-card__meta">Agosto 2026</span>
        </div>
    </section>

    <!-- ==========================================================
         PESTAÑAS: Vitrina / Bodega / Compras
         ========================================================== -->
    <div class="panel cliente-reveal cliente-reveal-3">
        <div class="inventario-tabs">
            <button type="button" class="inventario-tab is-active" data-tab="vitrina">Vitrina</button>
            <button type="button" class="inventario-tab" data-tab="bodega">Bodega</button>
            <button type="button" class="inventario-tab" data-tab="compras">Compras</button>
        </div>

        <!-- ---------- VITRINA ---------- -->
        <div class="inventario-tab-panel" data-tab-panel="vitrina">
            <div class="cliente-toolbar">
                <div class="cliente-toolbar__search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input type="search" id="vitrinaSearch" class="cliente-input" placeholder="Buscar producto..." autocomplete="off">
                </div>
                <select id="vitrinaCategoriaFilter" class="cliente-toolbar__select">
                    <option value="">Todas las categorías</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria }}">{{ $categoria }}</option>
                    @endforeach
                </select>
            </div>

            <div class="data-table-wrap">
                <table class="data-table" id="vitrinaTable">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio venta</th>
                            <th>Stock vitrina</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($productos as $producto)
                            <tr class="data-table__row" data-producto-id="{{ $producto['id'] }}" tabindex="0">
                                <td>
                                    <div class="data-table__title">{{ $producto['nombre'] }}</div>
                                    <div class="data-table__meta">{{ $producto['unidad'] }}</div>
                                </td>
                                <td class="data-table__meta">{{ $producto['categoria'] }}</td>
                                <td class="data-table__title">${{ number_format($producto['precioVenta'], 0, ',', '.') }}</td>
                                <td class="data-table__meta">{{ $producto['stockVitrina'] }} {{ Str::lower($producto['unidad']) }}s</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p class="data-table__empty" id="vitrinaEmpty" hidden>No hay productos que coincidan con la búsqueda.</p>
            </div>
        </div>

        <!-- ---------- BODEGA ---------- -->
        <div class="inventario-tab-panel" data-tab-panel="bodega" hidden>
            <div class="cliente-toolbar">
                <div class="cliente-toolbar__search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input type="search" id="bodegaSearch" class="cliente-input" placeholder="Buscar producto..." autocomplete="off">
                </div>
                <select id="bodegaCategoriaFilter" class="cliente-toolbar__select">
                    <option value="">Todas las categorías</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria }}">{{ $categoria }}</option>
                    @endforeach
                </select>
            </div>

            <div class="data-table-wrap">
                <table class="data-table" id="bodegaTable">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio costo</th>
                            <th>Stock bodega</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($productos as $producto)
                            <tr class="data-table__row" data-producto-id="{{ $producto['id'] }}" tabindex="0">
                                <td>
                                    <div class="data-table__title">{{ $producto['nombre'] }}</div>
                                    <div class="data-table__meta">{{ $producto['unidad'] }}</div>
                                </td>
                                <td class="data-table__meta">{{ $producto['categoria'] }}</td>
                                <td class="data-table__title">${{ number_format($producto['precioCosto'], 0, ',', '.') }}</td>
                                <td class="data-table__meta">{{ $producto['stockBodega'] }} {{ Str::lower($producto['unidad']) }}s</td>
                                <td>
                                    <button type="button" class="inventario-transfer-btn" data-producto-id="{{ $producto['id'] }}">
                                        Transferir
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p class="data-table__empty" id="bodegaEmpty" hidden>No hay productos que coincidan con la búsqueda.</p>
            </div>
        </div>

        <!-- ---------- COMPRAS ---------- -->
        <div class="inventario-tab-panel" data-tab-panel="compras" hidden>
            <div class="cliente-toolbar">
                <div class="cliente-toolbar__search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input type="search" id="comprasSearch" class="cliente-input" placeholder="Buscar por proveedor..." autocomplete="off">
                </div>
                <select id="comprasEstadoFilter" class="cliente-toolbar__select">
                    <option value="">Todos los estados</option>
                    <option value="validada">Validada</option>
                    <option value="por_validar">Por validar</option>
                    <option value="sin_factura">Sin factura</option>
                </select>
            </div>

            <div class="data-table-wrap">
                <table class="data-table" id="comprasTable">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>Productos</th>
                            <th>Total</th>
                            <th>Factura</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($compras as $compra)
                            <tr class="data-table__row" data-compra-id="{{ $compra['id'] }}" tabindex="0">
                                <td class="data-table__meta">{{ $compra['fecha'] }}</td>
                                <td>
                                    <div class="data-table__title">{{ $compra['proveedor'] ?? 'Compra informal' }}</div>
                                </td>
                                <td class="data-table__meta">{{ count($compra['lineas']) }} producto{{ count($compra['lineas']) === 1 ? '' : 's' }}</td>
                                <td class="data-table__title">${{ number_format($compra['total'], 0, ',', '.') }}</td>
                                <td>
                                    <span class="status-pill {{ $facturaPillClass[$compra['facturaEstado']] }}">
                                        {{ $facturaLabels[$compra['facturaEstado']] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p class="data-table__empty" id="comprasEmpty" hidden>No hay compras que coincidan con la búsqueda.</p>
            </div>
        </div>
    </div>

    <script id="inventarioProductosData" type="application/json">{!! json_encode($productos) !!}</script>
    <script id="inventarioComprasData" type="application/json">{!! json_encode($compras) !!}</script>

    {{-- ==================================================================
         PANEL LATERAL — detalle de un producto (ambos stocks)
         ================================================================== --}}
    <div class="slide-over-overlay" id="productoSlideOverOverlay"></div>

    <aside class="slide-over" id="productoSlideOver" aria-hidden="true">
        <div class="slide-over__header">
            <div>
                <h2 class="slide-over__title" id="productoSlideOverTitulo">—</h2>
                <span class="status-pill status-pill--sin-facturar" id="productoSlideOverCategoria">—</span>
            </div>
            <button type="button" class="slide-over__close" id="productoSlideOverClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="slide-over__body">
            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Precios</h3>
                <div class="slide-over__field"><span>Precio de costo</span><strong id="productoSlideOverPrecioCosto">—</strong></div>
                <div class="slide-over__field"><span>Precio de venta</span><strong id="productoSlideOverPrecioVenta">—</strong></div>
                <div class="slide-over__field"><span>Unidad de medida</span><strong id="productoSlideOverUnidad">—</strong></div>
            </section>

            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Inventario (vitrina y bodega van por separado)</h3>
                <div class="slide-over__field"><span>Stock en vitrina</span><strong id="productoSlideOverStockVitrina">—</strong></div>
                <div class="slide-over__field"><span>Stock en bodega</span><strong id="productoSlideOverStockBodega">—</strong></div>
            </section>

            <button type="button" class="cliente-btn-ghost" id="productoSlideOverEditarBtn" style="width:100%; margin-bottom:10px;">
                Editar producto
            </button>
            <button type="button" class="cliente-btn-primary" id="productoSlideOverTransferirBtn" style="width:100%;">
                Transferir de bodega a vitrina
            </button>
        </div>
    </aside>

    {{-- ==================================================================
         PANEL LATERAL — detalle de una compra
         ================================================================== --}}
    <div class="slide-over-overlay" id="compraSlideOverOverlay"></div>

    <aside class="slide-over" id="compraSlideOver" aria-hidden="true">
        <div class="slide-over__header">
            <div>
                <h2 class="slide-over__title" id="compraSlideOverTitulo">—</h2>
                <span class="status-pill" id="compraSlideOverEstado">—</span>
            </div>
            <button type="button" class="slide-over__close" id="compraSlideOverClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="slide-over__body">
            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Productos comprados</h3>
                <div id="compraSlideOverLineas"></div>
            </section>

            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Compra</h3>
                <div class="slide-over__field"><span>Origen</span><strong id="compraSlideOverOrigen">—</strong></div>
                <div class="slide-over__field" id="compraSlideOverCufeRow"><span>CUFE</span><strong id="compraSlideOverCufe">—</strong></div>
                <div class="slide-over__field"><span>Total</span><strong id="compraSlideOverTotal">—</strong></div>
            </section>
        </div>
    </aside>

    {{-- ==================================================================
         MODAL — Nuevo producto
         ================================================================== --}}
    <div class="modal-overlay" id="nuevoProductoOverlay"></div>

    <div class="modal" id="nuevoProductoModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="nuevoProductoTitle">
        <div class="modal__header">
            <h2 class="modal__title" id="nuevoProductoTitle">Nuevo producto</h2>
            <button type="button" class="modal__close" id="nuevoProductoClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="modal__body">
            <label for="prodNombre" class="cliente-label">Nombre del producto</label>
            <input type="text" id="prodNombre" class="cliente-input" placeholder="Ej: Aguardiente Antioqueño 750ml" style="margin-bottom:14px;">

            <label for="prodCategoria" class="cliente-label">Categoría</label>
            <select id="prodCategoria" class="cliente-toolbar__select" style="width:100%; margin-bottom:14px;">
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria }}">{{ $categoria }}</option>
                @endforeach
                <option value="__nueva__">+ Agregar categoría...</option>
            </select>

            <div class="nueva-categoria-row" id="nuevaCategoriaRow" hidden>
                <input type="text" id="nuevaCategoriaInput" class="cliente-input" placeholder="Nombre de la nueva categoría" style="flex:1;">
                <button type="button" class="cliente-btn-primary" id="nuevaCategoriaConfirmar">Agregar</button>
                <button type="button" class="cliente-btn-ghost" id="nuevaCategoriaCancelar">Cancelar</button>
            </div>

            <div style="display:flex; gap:12px; margin-bottom:14px;">
                <div style="flex:1;">
                    <label for="prodPrecioCosto" class="cliente-label">Precio de costo</label>
                    <input type="number" id="prodPrecioCosto" class="cliente-input" placeholder="0" min="0">
                </div>
                <div style="flex:1;">
                    <label for="prodPrecioVenta" class="cliente-label">Precio de venta</label>
                    <input type="number" id="prodPrecioVenta" class="cliente-input" placeholder="0" min="0">
                </div>
            </div>

            <label for="prodUnidad" class="cliente-label">Unidad de medida</label>
            <select id="prodUnidad" class="cliente-toolbar__select" style="width:100%;">
                @foreach ($unidades as $unidad)
                    <option value="{{ $unidad }}">{{ $unidad }}</option>
                @endforeach
            </select>
        </div>

        <div class="modal__footer">
            <button type="button" class="cliente-btn-primary" id="prodGuardarBtn" disabled>Guardar producto</button>
        </div>
    </div>

    {{-- ==================================================================
         MODAL — Registrar compra
         ================================================================== --}}
    <div class="modal-overlay" id="registrarCompraOverlay"></div>

    <div class="modal" id="registrarCompraModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="registrarCompraTitle">
        <div class="modal__header">
            <h2 class="modal__title" id="registrarCompraTitle">Registrar compra</h2>
            <button type="button" class="modal__close" id="registrarCompraClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="modal__body">
            <div class="compra-tipo-toggle">
                <button type="button" class="compra-tipo-btn is-active" id="compraTipoProveedorBtn">Con proveedor</button>
                <button type="button" class="compra-tipo-btn" id="compraTipoInformalBtn">Compra informal</button>
            </div>

            <div id="compraProveedorFields">
                <label for="compraProveedorNombre" class="cliente-label">Nombre del proveedor</label>
                <input type="text" id="compraProveedorNombre" class="cliente-input" placeholder="Ej: Licorera Continental S.A.S." style="margin-bottom:14px;">

                <label for="compraCufeInput" class="cliente-label">CUFE o código de la factura</label>
                <div style="display:flex; gap:8px; margin-bottom:6px;">
                    <input type="text" id="compraCufeInput" class="cliente-input" placeholder="Escanea el QR o pega el CUFE" style="flex:1;">
                    <button type="button" class="cliente-btn-ghost" id="compraValidarBtn">Validar</button>
                </div>
                <p class="compra-validar-status" id="compraValidarStatus">Sin validar todavía. El QR solo confirma que la factura existe ante la DIAN -los productos se agregan abajo, a mano.</p>
            </div>

            <p class="compra-informal-hint" id="compraInformalHint" hidden>
                Compra sin factura formal -no se valida ante la DIAN, solo queda registrada internamente.
            </p>

            <div class="venta-product-search" style="margin-top:16px;">
                <label for="compraProductoSearch" class="cliente-label">Buscar producto del catálogo</label>
                <input type="text" id="compraProductoSearch" class="cliente-input" placeholder="Ej: aguardiente, ron, cerveza..." autocomplete="off">
                <div class="venta-product-results" id="compraProductoResults" hidden></div>
            </div>

            <div class="venta-lines" id="compraLines">
                <p class="venta-lines__empty" id="compraLinesEmpty">Todavía no has agregado productos.</p>
            </div>

            <div class="venta-total-row">
                <span>Total de la compra</span>
                <strong id="compraTotal">$0</strong>
            </div>
        </div>

        <div class="modal__footer">
            <button type="button" class="cliente-btn-primary" id="compraRegistrarBtn" disabled>Registrar compra</button>
        </div>
    </div>

    {{-- ==================================================================
         MODAL — Transferir de bodega a vitrina
         ================================================================== --}}
    <div class="modal-overlay" id="transferirOverlay"></div>

    <div class="modal" id="transferirModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="transferirTitle" style="width:min(400px, calc(100% - 32px));">
        <div class="modal__header">
            <h2 class="modal__title" id="transferirTitle">Transferir a vitrina</h2>
            <button type="button" class="modal__close" id="transferirClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="modal__body">
            <p class="transferir-producto-nombre" id="transferirProductoNombre">—</p>
            <p class="transferir-disponible" id="transferirDisponible">Disponible en bodega: —</p>

            <label for="transferirCantidad" class="cliente-label">Cantidad a transferir</label>
            <input type="number" id="transferirCantidad" class="cliente-input" placeholder="0" min="1">
            <p class="transferir-error" id="transferirError" hidden>No puedes transferir más de lo que hay en bodega.</p>
        </div>

        <div class="modal__footer">
            <button type="button" class="cliente-btn-primary" id="transferirConfirmarBtn">Transferir</button>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/cliente/inventario.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/css/cliente/nueva-venta-modal.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset('assets/js/cliente/inventario.js') }}" defer></script>
    @endpush

</x-cliente-layout>
