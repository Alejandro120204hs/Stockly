<x-cliente-layout title="Gastos">

    {{-- Gastos — datos reales (App\Http\Controllers\Cliente\GastoController).
         A diferencia de una Compra, un gasto SÍ resta de la Ganancia neta
         -no vuelve en forma de nada revendible (nómina, arriendo,
         servicios, o algo de consumo propio como el almuerzo). Mismo
         patrón de método de pago que Compras: Efectivo/Digital + De
         caja/Aparte, para que "Gastos en efectivo/digital" en Caja
         reflejen la realidad. --}}
    @php
        // 'nomina' se queda en $categoriaLabels solo para poder seguir
        // mostrando/filtrando gastos ya registrados con esa categoría -pero
        // ya no se ofrece para gastos NUEVOS: pagarle a un empleado ahora
        // tiene su propio módulo (Nómina, en el sidebar), con documento
        // real para la DIAN en vez de un simple registro de gasto.
        $categoriaLabels = [
            'nomina' => 'Nómina',
            'arriendo' => 'Arriendo',
            'servicios' => 'Servicios',
            'otros' => 'Otros',
        ];
        $categoriaLabelsNuevoGasto = collect($categoriaLabels)->except('nomina');
    @endphp

    <div class="cliente-page-header cliente-reveal cliente-reveal-1" style="display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div>
            <p class="cliente-page-header__eyebrow">Tu negocio</p>
            <h1 class="cliente-page-header__title">Registro de gastos</h1>
            <p class="cliente-page-header__date">{{ $cantidadGastos }} gasto{{ $cantidadGastos === 1 ? '' : 's' }} registrado{{ $cantidadGastos === 1 ? '' : 's' }}</p>
        </div>
        <button type="button" class="cliente-btn-primary" id="registrarGastoBtn">+ Registrar gasto</button>
    </div>

    <section class="stat-grid cliente-reveal cliente-reveal-2">
        <div class="stat-card stat-card--sand">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 7a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v3"/>
                    <path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2H5a2 2 0 0 1-2-2Z"/>
                    <path d="M17 14h.01"/>
                </svg>
            </div>
            <span class="stat-card__value" id="gastoStatHoy" data-count="{{ $gastosHoy }}" data-prefix="$">$0</span>
            <span class="stat-card__label">Gastos de hoy</span>
            <span class="stat-card__meta">De caja y de aparte, todo incluido</span>
        </div>

        <div class="stat-card stat-card--sand">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 21V6l8-3 8 3v15"/>
                    <path d="M4 21h16"/>
                    <path d="M9 9h1M14 9h1M9 13h1M14 13h1M9 21v-4h6v4"/>
                </svg>
            </div>
            <span class="stat-card__value" id="gastoStatMes" data-count="{{ $gastosMes }}" data-prefix="$">$0</span>
            <span class="stat-card__label">Gastos de este mes</span>
            <span class="stat-card__meta">Nómina, arriendo, servicios y más</span>
        </div>
    </section>

    <div class="panel cliente-reveal cliente-reveal-3">
        <div class="cliente-toolbar">
            <div class="cliente-toolbar__search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <input type="search" id="gastosSearch" class="cliente-input" placeholder="Buscar por descripción o responsable..." autocomplete="off">
            </div>

            <select id="gastosCategoriaFilter" class="cliente-toolbar__select">
                <option value="">Todas las categorías</option>
                @foreach ($categoriaLabels as $valor => $label)
                    <option value="{{ $valor }}">{{ $label }}</option>
                @endforeach
            </select>

            <select id="gastosMetodoFilter" class="cliente-toolbar__select">
                <option value="">Todos los métodos</option>
                <option value="efectivo">Efectivo (caja)</option>
                <option value="efectivo_externo">Efectivo (aparte)</option>
                <option value="digital">Digital (de hoy)</option>
                <option value="digital_externo">Digital (aparte)</option>
            </select>

            <div class="vf-picker" id="gastosFechaPickerWrap">
                <button type="button" class="vf-picker__btn" id="gastosFechaBtn" aria-haspopup="true" aria-expanded="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="15" height="15" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <span id="gastosFechaLabel"></span>
                </button>
                <input type="hidden" id="gastosFechaFilter" value="{{ $fechaHoyTurno }}">
                <div class="vf-picker__cal" id="gastosFechaCal" hidden></div>
            </div>
            <button type="button" class="cliente-btn-ghost" id="gastosVerTodos">Ver todos</button>
        </div>

        <div class="data-table-wrap">
            <table class="data-table" id="gastosTable">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Categoría</th>
                        <th>Descripción</th>
                        <th>Responsable</th>
                        <th>Método</th>
                        <th>Monto</th>
                    </tr>
                </thead>
                <tbody id="gastosTableBody">
                    @foreach ($gastos as $gasto)
                        <tr class="data-table__row" data-gasto-id="{{ $gasto['id'] }}" tabindex="0">
                            <td>
                                <div class="data-table__title">{{ $gasto['fecha'] }}</div>
                                <div class="data-table__meta">{{ $gasto['hora'] }}</div>
                            </td>
                            <td><span class="status-pill status-pill--sin-facturar">{{ $categoriaLabels[$gasto['categoria']] }}</span></td>
                            <td class="data-table__meta">{{ $gasto['descripcion'] }}</td>
                            <td class="data-table__meta">{{ $gasto['responsable'] ?? '—' }}</td>
                            <td class="data-table__meta">
                                @if ($gasto['metodo'] === 'efectivo')
                                    Efectivo (caja)
                                @elseif ($gasto['metodo'] === 'efectivo_externo')
                                    Efectivo (aparte)
                                @elseif ($gasto['metodo'] === 'digital')
                                    Digital (de hoy)
                                @else
                                    Digital (aparte)
                                @endif
                            </td>
                            <td class="data-table__title">${{ number_format($gasto['monto'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="data-table__empty" id="gastosEmpty" @unless (count($gastos) === 0) hidden @endunless>No hay gastos que coincidan con la búsqueda.</p>
        </div>

        <div class="data-table__pagination" id="gastosPagination">
            <button type="button" class="cliente-btn-ghost" id="gastosPrevPage">← Anterior</button>
            <span class="data-table__pagination-info" id="gastosPageInfo">Página 1 de 1</span>
            <button type="button" class="cliente-btn-ghost" id="gastosNextPage">Siguiente →</button>
        </div>
    </div>

    <script id="gastosData" type="application/json">{!! json_encode($gastos) !!}</script>

    {{-- ==================================================================
         PANEL LATERAL — detalle de un gasto
         ================================================================== --}}
    <div class="slide-over-overlay" id="gastoSlideOverOverlay"></div>

    <aside class="slide-over" id="gastoSlideOver" aria-hidden="true">
        <div class="slide-over__header">
            <div>
                <h2 class="slide-over__title" id="gastoSlideOverTitulo">—</h2>
                <span class="status-pill status-pill--sin-facturar" id="gastoSlideOverCategoria">—</span>
            </div>
            <button type="button" class="slide-over__close" id="gastoSlideOverClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="slide-over__body">
            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Detalle del gasto</h3>
                <div class="slide-over__field"><span>Descripción</span><strong id="gastoSlideOverDescripcion">—</strong></div>
                <div class="slide-over__field"><span>Responsable</span><strong id="gastoSlideOverResponsable">—</strong></div>
                <div class="slide-over__field"><span>Método de pago</span><strong id="gastoSlideOverMetodo">—</strong></div>
                <div class="slide-over__field"><span>Monto</span><strong id="gastoSlideOverMonto">—</strong></div>
                <div class="slide-over__field"><span>Fecha</span><strong id="gastoSlideOverFecha">—</strong></div>
                <div class="slide-over__field"><span>Registrado por</span><strong id="gastoSlideOverRegistradoPor">—</strong></div>
            </section>
        </div>
    </aside>

    {{-- ==================================================================
         MODAL — Registrar gasto
         ================================================================== --}}
    <div class="modal-overlay" id="registrarGastoOverlay"></div>

    <div class="modal" id="registrarGastoModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="registrarGastoTitle" style="width:min(420px, calc(100% - 32px));">
        <div class="modal__header">
            <h2 class="modal__title" id="registrarGastoTitle">Registrar gasto</h2>
            <button type="button" class="modal__close" id="registrarGastoClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="modal__body">
            <label for="gastoCategoria" class="cliente-label">Categoría</label>
            <select id="gastoCategoria" class="cliente-input" style="margin-bottom:14px;">
                @foreach ($categoriaLabelsNuevoGasto as $valor => $label)
                    <option value="{{ $valor }}">{{ $label }}</option>
                @endforeach
            </select>

            <label for="gastoDescripcion" class="cliente-label">Descripción</label>
            <input type="text" id="gastoDescripcion" class="cliente-input" placeholder="Ej: Almuerzo del negocio, pago de luz de agosto..." style="margin-bottom:14px;">

            <label for="gastoResponsable" class="cliente-label">¿Quién lo hizo? (opcional)</label>
            <input type="text" id="gastoResponsable" class="cliente-input" placeholder="Ej: Valentina" style="margin-bottom:14px;">

            <label for="gastoMonto" class="cliente-label">Monto</label>
            <input type="text" inputmode="numeric" id="gastoMonto" class="cliente-input" placeholder="0" style="margin-bottom:14px;">

            <label class="cliente-label">Método de pago</label>
            <div class="venta-payment-toggle">
                <button type="button" class="venta-payment-btn is-active" id="gastoBtnEfectivo">Efectivo</button>
                <button type="button" class="venta-payment-btn" id="gastoBtnDigital">Digital</button>
            </div>

            <label class="cliente-label">¿Salió de la caja de hoy o de lo que el negocio ya tenía guardado?</label>
            <div class="venta-payment-toggle">
                <button type="button" class="venta-payment-btn is-active" id="gastoBtnOrigenHoy">De caja</button>
                <button type="button" class="venta-payment-btn" id="gastoBtnOrigenExterno">Fuera de caja</button>
            </div>
            <p class="compra-metodo-hint" id="gastoMetodoHint">Sacaste la plata física de la caja del negocio -se descuenta del cierre de caja de hoy.</p>
        </div>

        <div class="modal__footer">
            <button type="button" class="cliente-btn-primary" id="gastoRegistrarBtn" disabled>Registrar gasto</button>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/gastos.css') }}">
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/nueva-venta-modal.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/cliente/gastos.js') }}" defer></script>
    @endpush

</x-cliente-layout>
