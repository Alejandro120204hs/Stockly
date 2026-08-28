<x-cliente-layout title="Inventario">

    {{-- Inventario — datos reales (App\Http\Controllers\Cliente\InventarioController).
         Ojo con la regla de negocio: una compra SIEMPRE entra a bodega,
         nunca directo a vitrina -mover stock de bodega a vitrina es una
         acción manual aparte (acá el modal "Transferir"). Si la compra es
         con proveedor y factura electrónica, el QR/CUFE solo VALIDA la
         factura ante la DIAN -no trae el detalle de productos, así que las
         líneas siempre se registran a mano en cualquiera de los dos casos
         (proveedor o compra informal). --}}
    @php
        $facturaLabels = [
            'validada' => 'Validada',
            'por_validar' => 'Por validar',
            'sin_factura' => 'Compra informal',
        ];

        $facturaPillClass = [
            'validada' => 'status-pill--facturada',
            'por_validar' => 'status-pill--pendiente',
            'sin_factura' => 'status-pill--sin-facturar',
        ];

        $valorBodega = collect($productos)->sum(fn ($p) => $p['stockBodega'] * $p['precioCosto']);
        $valorVitrina = collect($productos)->sum(fn ($p) => $p['stockVitrina'] * $p['precioCosto']);
    @endphp

    <div class="cliente-page-header cliente-reveal cliente-reveal-1" style="display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div>
            <p class="cliente-page-header__eyebrow">Tu negocio</p>
            <h1 class="cliente-page-header__title">Vitrina y bodega</h1>
            <p class="cliente-page-header__date">{{ count($productos) }} productos en catálogo</p>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="button" class="cliente-btn-ghost" id="categoriasBtn">Gestionar Categorías</button>
            <button type="button" class="cliente-btn-ghost" id="nuevoProductoBtn">+ Nuevo producto</button>
            <button type="button" class="cliente-btn-primary" id="registrarCompraBtn">+ Registrar compra</button>
        </div>
    </div>

    <!-- ==========================================================
         STAT CARDS
         ========================================================== -->
    <section class="stat-grid stat-grid--inventario cliente-reveal cliente-reveal-2">
        <div class="stat-card stat-card--sage">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M3 9h18M9 21V9"/>
                </svg>
            </div>
            <span class="stat-card__value" id="statValorBodega" data-count="{{ $valorBodega }}" data-prefix="$">$0</span>
            <span class="stat-card__label">Valor en bodega (costo)</span>
       
        </div>

        <div class="stat-card stat-card--mist">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 21V10M18 21V3M12 21v-7"/>
                </svg>
            </div>
            <span class="stat-card__value" id="statValorVitrina" data-count="{{ $valorVitrina }}" data-prefix="$">$0</span>
            <span class="stat-card__label">Valor en vitrina (costo)</span>
        
        </div>

        <div class="stat-card stat-card--sand">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
            <span class="stat-card__value" id="statValorTotal" data-count="{{ $valorBodega + $valorVitrina }}" data-prefix="$">$0</span>
            <span class="stat-card__label">Valor total (costo)</span>
            <span class="stat-card__meta">Bodega + vitrina</span>
        </div>

        <div class="stat-card stat-card--sage">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="9" cy="20" r="1"/>
                    <circle cx="18" cy="20" r="1"/>
                    <path d="M3 4h2l2.3 11.4a2 2 0 0 0 2 1.6h7.4a2 2 0 0 0 2-1.6L21 8H6"/>
                </svg>
            </div>
            <span class="stat-card__value" id="statComprasMes" data-count="{{ $comprasEsteMes }}">0</span>
            <span class="stat-card__label">Compras este mes</span>
            <span class="stat-card__meta">{{ now()->locale('es')->translatedFormat('F Y') }}</span>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
                                <td class="data-table__meta">{{ $producto['stockVitrina'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p class="data-table__empty" id="vitrinaEmpty" hidden>No hay productos que coincidan con la búsqueda.</p>
            </div>

            <div class="data-table__pagination" id="vitrinaPagination">
                <button type="button" class="cliente-btn-ghost" id="vitrinaPrevPage">← Anterior</button>
                <span class="data-table__pagination-info" id="vitrinaPageInfo">Página 1 de 1</span>
                <button type="button" class="cliente-btn-ghost" id="vitrinaNextPage">Siguiente →</button>
            </div>
        </div>

        <!-- ---------- BODEGA ---------- -->
        <div class="inventario-tab-panel" data-tab-panel="bodega" hidden>
            <div class="cliente-toolbar">
                <div class="cliente-toolbar__search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
                                <td class="data-table__meta">{{ $producto['stockBodega'] }}</td>
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

            <div class="data-table__pagination" id="bodegaPagination">
                <button type="button" class="cliente-btn-ghost" id="bodegaPrevPage">← Anterior</button>
                <span class="data-table__pagination-info" id="bodegaPageInfo">Página 1 de 1</span>
                <button type="button" class="cliente-btn-ghost" id="bodegaNextPage">Siguiente →</button>
            </div>
        </div>

        <!-- ---------- COMPRAS ---------- -->
        <div class="inventario-tab-panel" data-tab-panel="compras" hidden>
            <div class="cliente-toolbar">
                <div class="cliente-toolbar__search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input type="search" id="comprasSearch" class="cliente-input" placeholder="Buscar por proveedor..." autocomplete="off">
                </div>
                <select id="comprasEstadoFilter" class="cliente-toolbar__select">
                    <option value="">Todos los estados</option>
                    <option value="validada">Validada</option>
                    <option value="por_validar">Por validar</option>
                    <option value="sin_factura">Compra informal</option>
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
                            <th>Método</th>
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
                                <td class="data-table__meta">
                                    @if ($compra['metodo'] === 'efectivo')
                                        Efectivo (caja)
                                    @elseif ($compra['metodo'] === 'efectivo_externo')
                                        Efectivo (aparte)
                                    @elseif ($compra['metodo'] === 'digital')
                                        Digital (de hoy)
                                    @else
                                        Digital (aparte)
                                    @endif
                                </td>
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

            <div class="data-table__pagination" id="comprasPagination">
                <button type="button" class="cliente-btn-ghost" id="comprasPrevPage">← Anterior</button>
                <span class="data-table__pagination-info" id="comprasPageInfo">Página 1 de 1</span>
                <button type="button" class="cliente-btn-ghost" id="comprasNextPage">Siguiente →</button>
            </div>
        </div>
    </div>

    <script id="inventarioProductosData" type="application/json">{!! json_encode($productos) !!}</script>
    <script id="inventarioComprasData" type="application/json">{!! json_encode($compras) !!}</script>
    <script id="inventarioCategoriasData" type="application/json">{!! json_encode($categorias) !!}</script>

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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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

            <button type="button" class="cliente-btn-primary" id="productoSlideOverTransferirBtn" style="width:100%; margin-bottom:10px;">
                Transferir de bodega a vitrina
            </button>
            <button type="button" class="cliente-btn-ghost" id="productoSlideOverEditarBtn" style="width:100%; margin-bottom:10px;">
                Editar producto
            </button>
            <button type="button" class="cliente-btn-ghost cliente-btn-ghost--peligro" id="productoSlideOverEliminarBtn" style="width:100%;">
                Eliminar producto
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="slide-over__body">
            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Productos comprados</h3>
                <div id="compraSlideOverLineas"></div>
                <div class="data-table__pagination" id="compraSlideOverLineasPagination" hidden>
                    <button type="button" class="cliente-btn-ghost" id="compraSlideOverLineasPrev">← Anterior</button>
                    <span class="data-table__pagination-info" id="compraSlideOverLineasPageInfo">Página 1 de 1</span>
                    <button type="button" class="cliente-btn-ghost" id="compraSlideOverLineasNext">Siguiente →</button>
                </div>
            </section>

            <section class="slide-over__section" id="compraSlideOverInfoSection">
                <h3 class="slide-over__section-title">Compra</h3>
                <div class="slide-over__compra-info">
                    <div class="slide-over__field"><span>Origen</span><strong id="compraSlideOverOrigen">—</strong></div>
                    <div class="slide-over__field"><span>Método de pago</span><strong id="compraSlideOverMetodo">—</strong></div>
                    <div class="slide-over__field" id="compraSlideOverCufeRow"><span>CUFE</span><strong id="compraSlideOverCufe">—</strong></div>
                    <div class="slide-over__compra-info-total">
                        <span>Total de la compra</span>
                        <strong id="compraSlideOverTotal">—</strong>
                    </div>
                </div>
            </section>
        </div>
    </aside>

    {{-- ==================================================================
         MODAL — Categorías (crear, renombrar, eliminar)
         ================================================================== --}}
    <div class="modal-overlay" id="categoriasOverlay"></div>

    <div class="modal" id="categoriasModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="categoriasTitle">
        <div class="modal__header">
            <h2 class="modal__title" id="categoriasTitle">Categorías</h2>
            <button type="button" class="modal__close" id="categoriasClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="modal__body">
            <div class="nueva-categoria-row" style="margin-bottom:18px;">
                <input type="text" id="categoriaNuevaInput" class="cliente-input" placeholder="Nombre de la nueva categoría" style="flex:1;">
                <button type="button" class="cliente-btn-primary" id="categoriaNuevaConfirmar">Agregar</button>
            </div>

            <div class="categorias-lista" id="categoriasLista"></div>
            <p class="data-table__empty" id="categoriasEmpty" hidden>Todavía no tienes categorías.</p>
        </div>
    </div>

    {{-- ==================================================================
         MODAL — Nuevo producto
         ================================================================== --}}
    <div class="modal-overlay" id="nuevoProductoOverlay"></div>

    <div class="modal" id="nuevoProductoModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="nuevoProductoTitle">
        <div class="modal__header">
            <h2 class="modal__title" id="nuevoProductoTitle">Nuevo producto</h2>
            <button type="button" class="modal__close" id="nuevoProductoClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="modal__body">
            <label for="prodNombre" class="cliente-label">Nombre del producto</label>
            <input type="text" id="prodNombre" class="cliente-input" placeholder="Ej: Aguardiente Antioqueño 750ml" style="margin-bottom:14px;">

            <label for="prodCategoria" class="cliente-label">Categoría</label>
            <select id="prodCategoria" class="cliente-toolbar__select" style="width:100%; margin-bottom:14px;">
                @if (count($categorias) === 0)
                    <option value="" disabled selected>Primero crea una categoría...</option>
                @endif
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria }}">{{ $categoria }}</option>
                @endforeach
            </select>

            <div style="display:flex; gap:12px; margin-bottom:14px;">
                <div style="flex:1;">
                    <label for="prodPrecioCosto" class="cliente-label">Precio de costo</label>
                    <input type="text" id="prodPrecioCosto" class="cliente-input" placeholder="0">
                </div>
                <div style="flex:1;">
                    <label for="prodPrecioVenta" class="cliente-label">Precio de venta</label>
                    <input type="text" id="prodPrecioVenta" class="cliente-input" placeholder="0">
                </div>
            </div>

            <label for="prodUnidad" class="cliente-label">Unidad de medida</label>
            <select id="prodUnidad" class="cliente-toolbar__select" style="width:100%;">
                @foreach ($unidades as $unidad)
                    <option value="{{ $unidad }}">{{ $unidad }}</option>
                @endforeach
                <option value="__nueva__">+ Agregar otra...</option>
            </select>

            <div class="nueva-categoria-row" id="nuevaUnidadRow" hidden style="margin-top:10px;">
                <input type="text" id="nuevaUnidadInput" class="cliente-input" placeholder="Ej: Media, Docena, Metro..." style="flex:1;">
                <button type="button" class="cliente-btn-primary" id="nuevaUnidadConfirmar">Agregar</button>
                <button type="button" class="cliente-btn-ghost" id="nuevaUnidadCancelar">Cancelar</button>
            </div>
        </div>

        <div class="modal__footer">
            <button type="button" class="cliente-btn-primary" id="prodGuardarBtn" disabled>Guardar producto</button>
        </div>
    </div>

    @include('cliente.partials.registrar-compra-modal', ['productosCompra' => $productos])

    {{-- ==================================================================
         MODAL — Transferir de bodega a vitrina
         ================================================================== --}}
    <div class="modal-overlay" id="transferirOverlay"></div>

    <div class="modal" id="transferirModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="transferirTitle" style="width:min(400px, calc(100% - 32px));">
        <div class="modal__header">
            <h2 class="modal__title" id="transferirTitle">Transferir a vitrina</h2>
            <button type="button" class="modal__close" id="transferirClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/inventario.css') }}">
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/nueva-venta-modal.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/cliente/inventario.js') }}" defer></script>
        <script src="{{ asset_v('assets/js/cliente/registrar-compra-modal.js') }}" defer></script>
    @endpush

</x-cliente-layout>
