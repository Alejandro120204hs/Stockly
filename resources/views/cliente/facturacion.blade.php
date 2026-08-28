<x-cliente-layout title="Facturación">

    @php
        $tipoLabels = [
            'factura_individual'  => 'Individual',
            'factura_consolidada' => 'Consolidada',
            'dee_pos'             => 'DEE / POS',
        ];
        $tipoPillClass = [
            'factura_individual'  => 'doc-pill--individual',
            'factura_consolidada' => 'doc-pill--consolidada',
            'dee_pos'             => 'doc-pill--dee',
        ];
        $estadoPillClass = [
            'emitida' => 'status-pill--pagada',
            'anulada' => 'status-pill--error',
        ];
    @endphp

    {{-- ================================================================
         ENCABEZADO
         ================================================================ --}}
    <div class="cliente-page-header cliente-reveal cliente-reveal-1"
         style="display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div>
            <p class="cliente-page-header__eyebrow">Tu negocio</p>
            <h1 class="cliente-page-header__title">Facturación electrónica</h1>
            <p class="cliente-page-header__date">{{ $documentos->count() }} {{ $documentos->count() === 1 ? 'documento emitido' : 'documentos emitidos' }}</p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button type="button" class="cliente-btn-ghost" id="nuevaDeePosBtn">DEE / POS</button>
            <button type="button" class="cliente-btn-ghost" id="nuevaConsolidadaBtn">Consolidada</button>
            <button type="button" class="cliente-btn-primary" id="nuevaIndividualBtn">+ Nueva factura</button>
        </div>
    </div>

    {{-- ================================================================
         STATS
         ================================================================ --}}
    <div class="stat-grid stat-grid--facturacion cliente-reveal cliente-reveal-2">
        <div class="stat-card">
            <p class="stat-card__label">Total facturado</p>
            <p class="stat-card__value" data-count="{{ $stats['totalFacturado'] }}" data-prefix="$" data-format="money">
                ${{ number_format($stats['totalFacturado'], 0, ',', '.') }}
            </p>
        </div>
        <div class="stat-card">
            <p class="stat-card__label">Individuales</p>
            <p class="stat-card__value" data-count="{{ $stats['countIndividual'] }}">{{ $stats['countIndividual'] }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card__label">Consolidadas</p>
            <p class="stat-card__value" data-count="{{ $stats['countConsolidada'] }}">{{ $stats['countConsolidada'] }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card__label">DEE / POS</p>
            <p class="stat-card__value" data-count="{{ $stats['countDeePos'] }}">{{ $stats['countDeePos'] }}</p>
        </div>
        <div class="stat-card stat-card--alerta">
            <p class="stat-card__label">Sin facturar</p>
            <p class="stat-card__value" data-count="{{ $ventasSinFacturar->count() }}">{{ $ventasSinFacturar->count() }}</p>
            <p class="stat-card__sub">ventas pendientes</p>
        </div>
    </div>

    {{-- ================================================================
         TABLA PRINCIPAL
         ================================================================ --}}
    <div class="panel cliente-reveal cliente-reveal-3">
        <div class="cliente-toolbar">
            <div class="cliente-toolbar__search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <input type="search" id="facturacionSearch" class="cliente-input"
                       placeholder="Buscar por número, comprador o NIT..." autocomplete="off">
            </div>

            <select id="facturacionTipoFilter" class="cliente-toolbar__select">
                <option value="">Todos los tipos</option>
                <option value="factura_individual">Individual</option>
                <option value="factura_consolidada">Consolidada</option>
                <option value="dee_pos">DEE / POS</option>
            </select>

            <select id="facturacionEstadoFilter" class="cliente-toolbar__select">
                <option value="">Todos los estados</option>
                <option value="emitida">Emitida</option>
                <option value="anulada">Anulada</option>
            </select>
        </div>

        <div class="data-table-wrap">
            <table class="data-table" id="facturacionTable">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Tipo</th>
                        <th>Comprador</th>
                        <th>Total</th>
                        <th>Ventas</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($documentos as $doc)
                        <tr class="data-table__row" data-doc-id="{{ $doc['id'] }}" tabindex="0">
                            <td>
                                <div class="data-table__title">{{ $doc['numero'] }}</div>
                                @if ($doc['cufe'])
                                    <div class="data-table__meta cufe-snippet" title="{{ $doc['cufe'] }}">
                                        {{ substr($doc['cufe'], 0, 18) }}&hellip;
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="doc-pill {{ $tipoPillClass[$doc['tipo']] }}">
                                    {{ $tipoLabels[$doc['tipo']] }}
                                </span>
                            </td>
                            <td>
                                @if ($doc['comprador'])
                                    <div class="data-table__title">{{ $doc['comprador']['nombre'] }}</div>
                                    <div class="data-table__meta">
                                        {{ $doc['comprador']['tipoDoc'] }} {{ $doc['comprador']['numDoc'] }}
                                    </div>
                                @else
                                    <span class="data-table__meta">Consumidor final</span>
                                @endif
                            </td>
                            <td class="data-table__title">
                                ${{ number_format($doc['valorTotal'], 0, ',', '.') }}
                            </td>
                            <td class="data-table__meta">
                                {{ count($doc['ventasIds']) }}
                                {{ count($doc['ventasIds']) === 1 ? 'venta' : 'ventas' }}
                            </td>
                            <td>
                                <span class="status-pill {{ $estadoPillClass[$doc['estado']] }}">
                                    {{ ucfirst($doc['estado']) }}
                                </span>
                            </td>
                            <td class="data-table__meta">{{ $doc['fecha'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="data-table__empty" id="facturacionEmpty"
               @unless ($documentos->isEmpty()) hidden @endunless>
                @if ($documentos->isEmpty())
                    Todavía no hay documentos emitidos.
                @else
                    No hay documentos que coincidan con la búsqueda.
                @endif
            </p>
        </div>

        <div class="data-table__pagination" id="facturacionPagination">
            <button type="button" class="cliente-btn-ghost" id="facturacionPrevPage">← Anterior</button>
            <span class="data-table__pagination-info" id="facturacionPageInfo">Página 1 de 1</span>
            <button type="button" class="cliente-btn-ghost" id="facturacionNextPage">Siguiente →</button>
        </div>
    </div>

    {{-- Islands de datos para el JS --}}
    <script id="facturacionData" type="application/json">{!! json_encode($documentos) !!}</script>
    <script id="ventasSinFacturarData" type="application/json">{!! json_encode($ventasSinFacturar) !!}</script>
    <meta name="facturacion-store-url" content="{{ route('cliente.facturacion.store') }}">

    {{-- ================================================================
         PANEL LATERAL — detalle de documento
         ================================================================ --}}
    <div class="slide-over-overlay" id="docSlideOverOverlay"></div>

    <aside class="slide-over" id="docSlideOver" aria-hidden="true">
        <div class="slide-over__header">
            <div>
                <h2 class="slide-over__title" id="docSlideOverNumero">—</h2>
                <span class="status-pill" id="docSlideOverEstado">—</span>
            </div>
            <button type="button" class="slide-over__close" id="docSlideOverClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="slide-over__body">
            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Tipo de documento</h3>
                <div class="slide-over__field">
                    <span>Tipo</span>
                    <strong id="docSlideOverTipo">—</strong>
                </div>
                <div class="slide-over__field">
                    <span>Fecha de emisión</span>
                    <strong id="docSlideOverFecha">—</strong>
                </div>
            </section>

            <section class="slide-over__section" id="docSlideOverCompradorSection">
                <h3 class="slide-over__section-title">Comprador</h3>
                <div id="docSlideOverComprador"></div>
            </section>

            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Ventas incluidas</h3>
                <div id="docSlideOverVentas"></div>
                <div class="slide-over__field" style="margin-top:12px; padding-top:12px; border-top:1px solid var(--color-border-06);">
                    <span>Total del documento</span>
                    <strong id="docSlideOverTotal">—</strong>
                </div>
            </section>

            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Verificación DIAN</h3>
                <p class="slide-over__label" style="font-size:11px; color:var(--color-mist); margin-bottom:6px;">CUFE</p>
                <div class="cufe-block" id="docSlideOverCufe"></div>
                <div class="qr-placeholder">
                    <div class="qr-box" aria-label="Código QR de verificación DIAN"></div>
                    <p class="qr-hint">Escanea para verificar en el portal DIAN</p>
                </div>
            </section>

            <section class="slide-over__section" id="docAnularSection">
                <button type="button" class="cliente-btn-ghost cliente-btn-ghost--peligro" id="docAnularBtn"
                        style="width:100%;">
                    Anular documento
                </button>
            </section>
        </div>
    </aside>

    {{-- ================================================================
         MODAL — Nueva factura
         ================================================================ --}}
    <div class="modal-overlay" id="nuevaFacturaOverlay"></div>

    <div class="modal" id="nuevaFacturaModal" aria-hidden="true" role="dialog"
         aria-labelledby="nuevaFacturaModalTitle">
        <div class="modal__header">
            <h2 class="modal__title" id="nuevaFacturaModalTitle">Nueva factura electrónica</h2>
            <button type="button" class="slide-over__close" id="nuevaFacturaClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="modal__body">
            {{-- Tipo de factura --}}
            <div class="form-group">
                <label class="cliente-label">Tipo de documento</label>
                <div class="factura-tipo-grid">
                    <label class="factura-tipo-card is-selected" data-tipo="factura_individual">
                        <input type="radio" name="facturaTipo" value="factura_individual" checked hidden>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>
                            <path d="M14 3v5h5"/><path d="M9 13h6M9 17h4"/>
                        </svg>
                        <span class="factura-tipo-card__name">Individual</span>
                        <span class="factura-tipo-card__desc">Una venta, un documento</span>
                    </label>
                    <label class="factura-tipo-card" data-tipo="factura_consolidada">
                        <input type="radio" name="facturaTipo" value="factura_consolidada" hidden>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/>
                            <path d="M8 9h2"/>
                        </svg>
                        <span class="factura-tipo-card__name">Consolidada</span>
                        <span class="factura-tipo-card__desc">Agrupa varias ventas</span>
                    </label>
                    <label class="factura-tipo-card" data-tipo="dee_pos">
                        <input type="radio" name="facturaTipo" value="dee_pos" hidden>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <path d="M3 10h18M8 15h.01M12 15h.01M16 15h.01"/>
                        </svg>
                        <span class="factura-tipo-card__name">DEE / POS</span>
                        <span class="factura-tipo-card__desc">Consumidor final</span>
                    </label>
                </div>
            </div>

            {{-- Comprador (oculto para DEE/POS) --}}
            <div class="form-group" id="compradorSection">
                <label class="cliente-label" for="compradorNombre">Comprador</label>
                <div class="factura-comprador-grid">
                    <select id="compradorTipoDoc" class="cliente-input">
                        <option value="CC">CC - Cédula</option>
                        <option value="NIT">NIT</option>
                        <option value="CE">CE - Extranjería</option>
                        <option value="PP">PP - Pasaporte</option>
                    </select>
                    <input type="text" id="compradorNumDoc" class="cliente-input" placeholder="Número de documento">
                </div>
                <input type="text" id="compradorNombre" class="cliente-input" placeholder="Nombre o razón social" style="margin-top:8px;">
            </div>

            {{-- Ventas sin facturar (cargadas desde el servidor) --}}
            <div class="form-group">
                <label class="cliente-label">Ventas a incluir</label>
                <div class="ventas-pendientes-list" id="ventasPendientesList">
                    @forelse ($ventasSinFacturar as $venta)
                        <label class="venta-check-row">
                            <input type="checkbox" value="{{ $venta['id'] }}" class="venta-check"
                                   data-total="{{ $venta['total'] }}">
                            <div class="venta-check-row__info">
                                <span>{{ $venta['label'] }}</span>
                                <span>{{ $venta['monto'] }}</span>
                            </div>
                        </label>
                    @empty
                        <p class="data-table__empty" style="margin:0; padding:16px 0; text-align:center;">
                            No hay ventas sin facturar en este momento.
                        </p>
                    @endforelse
                </div>

                <div class="factura-total-row" id="facturaTotalRow">
                    <span>Total seleccionado</span>
                    <strong id="facturaTotalSeleccionado">$0</strong>
                </div>
            </div>
        </div>

        <div class="modal__footer">
            <button type="button" class="cliente-btn-ghost" id="nuevaFacturaCancelar">Cancelar</button>
            <button type="button" class="cliente-btn-primary" id="nuevaFacturaEmitir" disabled>Emitir a la DIAN</button>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/facturacion.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/cliente/facturacion.js') }}" defer></script>
    @endpush

</x-cliente-layout>
