<x-cliente-layout title="Ventas">

    {{-- Historial de ventas — datos reales (App\Http\Controllers\Cliente\VentasController).
         Ojo: `ventas` no tiene comprador_id en el esquema real -el
         comprador solo se asocia más adelante, al facturar
         (documento_venta), así que no se muestra acá como si fuera un
         dato propio de la venta. precio_unitario_venta puede diferir del
         precio "de hoy" del catálogo en Nueva venta -es normal,
         venta_detalle guarda el precio histórico del momento en que se
         vendió. --}}
    @php
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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

            <div class="vf-picker" id="ventasFechaPickerWrap">
                <button type="button" class="vf-picker__btn" id="ventasFechaBtn" aria-haspopup="true" aria-expanded="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="15" height="15" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <span id="ventasFechaLabel"></span>
                </button>
                <input type="hidden" id="ventasFechaFilter" value="{{ $fechaHoyTurno }}">
                <div class="vf-picker__cal" id="ventasFechaCal" hidden></div>
            </div>
            <button type="button" class="cliente-btn-ghost" id="ventasVerTodas">Ver todas</button>
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
                        <tr class="data-table__row {{ $venta['anulada'] ? 'venta-fila-anulada' : '' }}" data-venta-id="{{ $venta['id'] }}" tabindex="0">
                            <td>
                                <div class="data-table__title">Venta #{{ $venta['id'] }}</div>
                                <div class="data-table__meta">{{ $venta['hora'] }}</div>
                            </td>
                            <td class="data-table__title">${{ number_format($venta['total'], 0, ',', '.') }}</td>
                            <td class="data-table__meta">{{ $venta['metodo'] === 'efectivo' ? 'Efectivo' : 'Digital (Wompi)' }}</td>
                            <td>
                                @if ($venta['anulada'])
                                    <span class="status-pill status-pill--sin-facturar">Anulada</span>
                                @else
                                    <span class="status-pill status-pill--{{ $venta['estadoPago'] }}">
                                        {{ $venta['estadoPago'] === 'pagada' ? 'Pagada' : 'Pendiente' }}
                                    </span>
                                @endif
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

    @include('cliente.partials.venta-slide-over')

    @include('cliente.partials.nueva-venta-modal')

    @push('styles')
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/ventas.css') }}">
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/nueva-venta-modal.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/cliente/ventas.js') }}" defer></script>
        <script src="{{ asset_v('assets/js/cliente/venta-slide-over.js') }}" defer></script>
        <script src="{{ asset_v('assets/js/cliente/nueva-venta-modal.js') }}" defer></script>
    @endpush

</x-cliente-layout>
