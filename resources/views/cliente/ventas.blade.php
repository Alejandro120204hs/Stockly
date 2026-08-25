<x-cliente-layout title="Ventas">

    {{-- Historial de ventas — SOLO FRONTEND, datos mock. Ojo: `ventas` no
         tiene comprador_id en el esquema real -el comprador solo se
         asocia más adelante, al facturar (documento_venta), así que no
         se muestra acá como si fuera un dato propio de la venta.
         precio_unitario_venta puede diferir del precio "de hoy" del
         catálogo en Nueva venta -es normal, venta_detalle guarda el
         precio histórico del momento en que se vendió. --}}
    @php
        $ventas = [
            ['id' => 128, 'hora' => '2:45 p.m.', 'total' => 85000, 'metodo' => 'digital', 'estadoPago' => 'pendiente', 'estadoFacturacion' => 'sin_facturar', 'ganancia' => 30000, 'lineas' => [['nombre' => 'Ron Medellín Añejo 750ml', 'cantidad' => 1, 'precio' => 62000], ['nombre' => 'Cerveza Águila Lata 330ml', 'cantidad' => 1, 'precio' => 23000]]],
            ['id' => 127, 'hora' => '2:10 p.m.', 'total' => 124000, 'metodo' => 'efectivo', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'sin_facturar', 'ganancia' => 43000, 'lineas' => [['nombre' => 'Ron Medellín Añejo 750ml', 'cantidad' => 2, 'precio' => 62000]]],
            ['id' => 126, 'hora' => '1:30 p.m.', 'total' => 45000, 'metodo' => 'efectivo', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'sin_facturar', 'ganancia' => 16000, 'lineas' => [['nombre' => 'Aguardiente Antioqueño 750ml', 'cantidad' => 1, 'precio' => 45000]]],
            ['id' => 125, 'hora' => '12:55 p.m.', 'total' => 210000, 'metodo' => 'digital', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'facturada_individual', 'ganancia' => 74000, 'lineas' => [['nombre' => 'Whisky Old Parr 750ml', 'cantidad' => 1, 'precio' => 185000], ['nombre' => 'Cerveza Águila Lata 330ml', 'cantidad' => 1, 'precio' => 25000]]],
            ['id' => 124, 'hora' => '11:40 a.m.', 'total' => 68000, 'metodo' => 'efectivo', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'sin_facturar', 'ganancia' => 24000, 'lineas' => [['nombre' => 'Ron Viejo de Caldas 750ml', 'cantidad' => 1, 'precio' => 54000], ['nombre' => 'Cerveza Águila Lata 330ml', 'cantidad' => 1, 'precio' => 14000]]],
            ['id' => 123, 'hora' => '10:55 a.m.', 'total' => 156000, 'metodo' => 'digital', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'sin_facturar', 'ganancia' => 55000, 'lineas' => [['nombre' => 'Ron Medellín Añejo 750ml', 'cantidad' => 2, 'precio' => 62000], ['nombre' => 'Aguardiente Antioqueño 750ml (promo)', 'cantidad' => 1, 'precio' => 32000]]],
            ['id' => 122, 'hora' => '10:12 a.m.', 'total' => 32000, 'metodo' => 'efectivo', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'sin_facturar', 'ganancia' => 11000, 'lineas' => [['nombre' => 'Aguardiente Antioqueño 375ml (media)', 'cantidad' => 1, 'precio' => 32000]]],
            ['id' => 121, 'hora' => '9:48 a.m.', 'total' => 95000, 'metodo' => 'efectivo', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'incluida_en_consolidado', 'ganancia' => 33000, 'lineas' => [['nombre' => 'Vino Santa Rita 750ml', 'cantidad' => 1, 'precio' => 58000], ['nombre' => 'Cerveza Águila Lata 330ml (x6)', 'cantidad' => 1, 'precio' => 37000]]],
            ['id' => 120, 'hora' => '9:15 a.m.', 'total' => 178000, 'metodo' => 'digital', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'sin_facturar', 'ganancia' => 62000, 'lineas' => [['nombre' => 'Ron Medellín Añejo 750ml', 'cantidad' => 2, 'precio' => 62000], ['nombre' => 'Ron Viejo de Caldas 750ml', 'cantidad' => 1, 'precio' => 54000]]],
            ['id' => 119, 'hora' => '8:30 a.m.', 'total' => 54000, 'metodo' => 'efectivo', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'facturada_individual', 'ganancia' => 19000, 'lineas' => [['nombre' => 'Ron Viejo de Caldas 750ml', 'cantidad' => 1, 'precio' => 54000]]],
            ['id' => 118, 'hora' => '8:15 a.m.', 'total' => 23000, 'metodo' => 'efectivo', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'sin_facturar', 'ganancia' => 8000, 'lineas' => [['nombre' => 'Cerveza Águila Lata 330ml (x6)', 'cantidad' => 1, 'precio' => 23000]]],
            ['id' => 117, 'hora' => '8:05 a.m.', 'total' => 116000, 'metodo' => 'digital', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'sin_facturar', 'ganancia' => 41000, 'lineas' => [['nombre' => 'Vino Santa Rita 750ml', 'cantidad' => 2, 'precio' => 58000]]],
            ['id' => 116, 'hora' => 'Ayer, 7:50 p.m.', 'total' => 62000, 'metodo' => 'efectivo', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'sin_facturar', 'ganancia' => 22000, 'lineas' => [['nombre' => 'Ron Medellín Añejo 750ml', 'cantidad' => 1, 'precio' => 62000]]],
            ['id' => 115, 'hora' => 'Ayer, 7:20 p.m.', 'total' => 90000, 'metodo' => 'efectivo', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'facturada_individual', 'ganancia' => 32000, 'lineas' => [['nombre' => 'Aguardiente Antioqueño 750ml', 'cantidad' => 2, 'precio' => 45000]]],
            ['id' => 114, 'hora' => 'Ayer, 6:40 p.m.', 'total' => 185000, 'metodo' => 'digital', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'sin_facturar', 'ganancia' => 65000, 'lineas' => [['nombre' => 'Whisky Old Parr 750ml', 'cantidad' => 1, 'precio' => 185000]]],
            ['id' => 113, 'hora' => 'Ayer, 6:10 p.m.', 'total' => 37000, 'metodo' => 'efectivo', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'sin_facturar', 'ganancia' => 13000, 'lineas' => [['nombre' => 'Cerveza Club Colombia 330ml (x8)', 'cantidad' => 1, 'precio' => 37000]]],
            ['id' => 112, 'hora' => 'Ayer, 5:35 p.m.', 'total' => 108000, 'metodo' => 'digital', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'incluida_en_consolidado', 'ganancia' => 38000, 'lineas' => [['nombre' => 'Ron Viejo de Caldas 750ml', 'cantidad' => 2, 'precio' => 54000]]],
            ['id' => 111, 'hora' => 'Ayer, 4:50 p.m.', 'total' => 45000, 'metodo' => 'efectivo', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'sin_facturar', 'ganancia' => 16000, 'lineas' => [['nombre' => 'Aguardiente Antioqueño 750ml', 'cantidad' => 1, 'precio' => 45000]]],
            ['id' => 110, 'hora' => 'Ayer, 4:05 p.m.', 'total' => 62000, 'metodo' => 'digital', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'facturada_individual', 'ganancia' => 22000, 'lineas' => [['nombre' => 'Ron Medellín Añejo 750ml', 'cantidad' => 1, 'precio' => 62000]]],
            ['id' => 109, 'hora' => 'Ayer, 3:20 p.m.', 'total' => 45000, 'metodo' => 'efectivo', 'estadoPago' => 'pagada', 'estadoFacturacion' => 'sin_facturar', 'ganancia' => 16000, 'lineas' => [['nombre' => 'Aguardiente Antioqueño 750ml', 'cantidad' => 1, 'precio' => 45000]]],
        ];

        $facturacionLabels = [
            'sin_facturar' => 'Sin facturar',
            'facturada_individual' => 'Facturada',
            'incluida_en_consolidado' => 'En consolidado',
        ];

        $facturacionPillClass = [
            'sin_facturar' => 'status-pill--sin-facturar',
            'facturada_individual' => 'status-pill--facturada',
            'incluida_en_consolidado' => 'status-pill--facturada',
        ];
    @endphp

    <div class="cliente-page-header cliente-reveal cliente-reveal-1" style="display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div>
            <p class="cliente-page-header__eyebrow">Tu negocio</p>
            <h1 class="cliente-page-header__title">Historial de ventas</h1>
            <p class="cliente-page-header__date">{{ count($ventas) }} ventas registradas</p>
        </div>
        <button type="button" class="cliente-btn-primary" id="nuevaVentaBtn">+ Nueva venta</button>
    </div>

    <div class="panel cliente-reveal cliente-reveal-2">
        <div class="cliente-toolbar">
            <div class="cliente-toolbar__search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <input type="search" id="ventasSearch" class="cliente-input" placeholder="Buscar por número de venta..." autocomplete="off">
            </div>

            <select id="ventasMetodoFilter" class="cliente-toolbar__select">
                <option value="">Todos los métodos</option>
                <option value="efectivo">Efectivo</option>
                <option value="digital">Digital (Wompi)</option>
            </select>

            <select id="ventasFacturacionFilter" class="cliente-toolbar__select">
                <option value="">Toda la facturación</option>
                <option value="sin_facturar">Sin facturar</option>
                <option value="facturada_individual">Facturada</option>
                <option value="incluida_en_consolidado">En consolidado</option>
            </select>
        </div>

        <div class="data-table-wrap">
            <table class="data-table" id="ventasTable">
                <thead>
                    <tr>
                        <th>Venta</th>
                        <th>Total</th>
                        <th>Método</th>
                        <th>Estado de pago</th>
                        <th>Facturación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ventas as $venta)
                        <tr class="data-table__row" data-venta-id="{{ $venta['id'] }}" tabindex="0">
                            <td>
                                <div class="data-table__title">Venta #{{ $venta['id'] }}</div>
                                <div class="data-table__meta">Hoy, {{ $venta['hora'] }}</div>
                            </td>
                            <td class="data-table__title">${{ number_format($venta['total'], 0, ',', '.') }}</td>
                            <td class="data-table__meta">{{ $venta['metodo'] === 'efectivo' ? 'Efectivo' : 'Digital (Wompi)' }}</td>
                            <td>
                                <span class="status-pill status-pill--{{ $venta['estadoPago'] }}">
                                    {{ $venta['estadoPago'] === 'pagada' ? 'Pagada' : 'Pendiente' }}
                                </span>
                            </td>
                            <td>
                                <span class="status-pill {{ $facturacionPillClass[$venta['estadoFacturacion']] }}">
                                    {{ $facturacionLabels[$venta['estadoFacturacion']] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="data-table__empty" id="ventasEmpty" hidden>No hay ventas que coincidan con la búsqueda.</p>
        </div>

        <div class="data-table__pagination" id="ventasPagination">
            <button type="button" class="cliente-btn-ghost" id="ventasPrevPage">← Anterior</button>
            <span class="data-table__pagination-info" id="ventasPageInfo">Página 1 de 1</span>
            <button type="button" class="cliente-btn-ghost" id="ventasNextPage">Siguiente →</button>
        </div>
    </div>

    <script id="ventasData" type="application/json">{!! json_encode($ventas) !!}</script>

    {{-- ==================================================================
         PANEL LATERAL — detalle de una venta
         ================================================================== --}}
    <div class="slide-over-overlay" id="ventaSlideOverOverlay"></div>

    <aside class="slide-over" id="ventaSlideOver" aria-hidden="true">
        <div class="slide-over__header">
            <div>
                <h2 class="slide-over__title" id="ventaSlideOverTitulo">—</h2>
                <span class="status-pill" id="ventaSlideOverEstadoPago">—</span>
            </div>
            <button type="button" class="slide-over__close" id="ventaSlideOverClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="slide-over__body">
            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Productos vendidos</h3>
                <div id="ventaSlideOverLineas"></div>
            </section>

            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Pago</h3>
                <div class="slide-over__field"><span>Método</span><strong id="ventaSlideOverMetodo">—</strong></div>
                <div class="slide-over__field"><span>Total</span><strong id="ventaSlideOverTotal">—</strong></div>
                <div class="slide-over__field"><span>Ganancia bruta</span><strong id="ventaSlideOverGanancia">—</strong></div>
            </section>

            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Facturación</h3>
                <div class="slide-over__field"><span>Estado</span><strong id="ventaSlideOverFacturacion">—</strong></div>
            </section>
        </div>
    </aside>

    @include('cliente.partials.nueva-venta-modal')

    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/cliente/ventas.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/css/cliente/nueva-venta-modal.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset('assets/js/cliente/ventas.js') }}" defer></script>
        <script src="{{ asset('assets/js/cliente/nueva-venta-modal.js') }}" defer></script>
    @endpush

</x-cliente-layout>
