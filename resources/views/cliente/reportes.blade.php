<x-cliente-layout title="Reportes">

    <div class="cliente-page-header cliente-reveal cliente-reveal-1" style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div>
            <p class="cliente-page-header__eyebrow">Tu negocio</p>
            <h1 class="cliente-page-header__title">Reportes</h1>
            <p class="cliente-page-header__date">Análisis de ingresos, gastos y ventas</p>
        </div>

        <div class="reporte-toolbar">
            <div class="reporte-tabs" role="tablist" aria-label="Período del reporte">
                {{-- Un solo selector de calendario, no dos -el toggle
                     Día/Mes decide qué elige el mismo <input>: un día
                     puntual cualquiera, o un mes puntual cualquiera (no
                     solo "Este mes", que siempre es el actual). Tener dos
                     calendarios lado a lado confundía cuál era cuál. --}}
                <label class="reporte-tab reporte-tab--dia" role="tab" id="reporteFechaTab" aria-selected="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="14" height="14">
                        <rect x="3" y="4.5" width="18" height="16" rx="2"/>
                        <path d="M8 2.5v4M16 2.5v4M3 9.5h18"/>
                    </svg>
                    <span class="reporte-fecha-toggle">
                        <button type="button" class="reporte-fecha-toggle__btn is-active" id="reporteModoDia" data-modo="dia">Día</button>
                        <button type="button" class="reporte-fecha-toggle__btn" id="reporteModoMes" data-modo="mes">Mes</button>
                    </span>
                    <input type="date" id="reporteFechaInput" class="reporte-dia-input" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" aria-label="Elegir un día">
                </label>
                <button type="button" class="reporte-tab is-active" role="tab" data-periodo="semana" aria-selected="true">Esta semana</button>
                <button type="button" class="reporte-tab" role="tab" data-periodo="mes">Este mes</button>
                <button type="button" class="reporte-tab" role="tab" data-periodo="anio">Este año</button>
            </div>
            <a href="{{ route('cliente.reportes.pdf') }}?periodo=semana" id="reportePdfBtn" class="cliente-btn-ghost reporte-pdf-btn" target="_blank">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="16" height="16">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Descargar PDF
            </a>
        </div>
    </div>

    {{-- Tarjetas resumen --}}
    <section class="stat-grid stat-grid--reportes cliente-reveal cliente-reveal-2">
        <div class="stat-card stat-card--sage">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 1 0 0 7h5a3.5 3.5 0 1 1 0 7H6"/>
                </svg>
            </div>
            <span class="stat-card__value" id="statIngresos" data-prefix="$">$0</span>
            <span class="stat-card__label">Ingresos</span>
            <span class="stat-card__meta">Total de ventas no anuladas</span>
        </div>

        <div class="stat-card stat-card--sand">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 7a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v3"/>
                    <path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2H5a2 2 0 0 1-2-2Z"/>
                    <path d="M17 14h.01"/>
                </svg>
            </div>
            <span class="stat-card__value" id="statGastos" data-prefix="$">$0</span>
            <span class="stat-card__label">Gastos</span>
            <span class="stat-card__meta">Nómina, arriendo, servicios y más</span>
        </div>

        <div class="stat-card" id="statGananciaNeta">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>
                </svg>
            </div>
            <span class="stat-card__value" id="statGanancia" data-prefix="$">$0</span>
            <span class="stat-card__label">Ganancia neta</span>
            <span class="stat-card__meta">Ganancia bruta menos todos los gastos</span>
        </div>

        <div class="stat-card">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 7V6a6 6 0 0 1 12 0v1"/>
                    <path d="M4 7h16l-1.5 13.5a2 2 0 0 1-2 1.5H7.5a2 2 0 0 1-2-1.5L4 7Z"/>
                </svg>
            </div>
            <span class="stat-card__value" id="statVentas">0</span>
            <span class="stat-card__label">Ventas</span>
            <span class="stat-card__meta">Transacciones completadas</span>
        </div>
    </section>

    {{-- Métodos de pago + Gastos por categoría --}}
    <div class="reporte-row cliente-reveal cliente-reveal-3">

        <div class="panel">
            <h2 class="reporte-section-title" style="margin-bottom:4px;">Métodos de pago</h2>
            <p class="reporte-section-sub" style="margin-bottom:20px;">Distribución de ingresos</p>

            <div class="reporte-donut-wrap">
                <svg class="reporte-donut" viewBox="0 0 64 64" aria-hidden="true">
                    <circle class="reporte-donut__bg" cx="32" cy="32" r="25" fill="none" stroke-width="10"/>
                    <circle class="reporte-donut__efectivo" id="donutEfectivo" cx="32" cy="32" r="25" fill="none" stroke-width="10" stroke-dasharray="0 157" stroke-dashoffset="39" stroke-linecap="butt"/>
                    <circle class="reporte-donut__digital" id="donutDigital" cx="32" cy="32" r="25" fill="none" stroke-width="10" stroke-dasharray="0 157" stroke-dashoffset="39" stroke-linecap="butt"/>
                </svg>
                <div class="reporte-donut-legend">
                    <div class="reporte-donut-item">
                        <span class="reporte-donut-item__dot reporte-donut-item__dot--efectivo"></span>
                        <span class="reporte-donut-item__label">Efectivo</span>
                        <strong class="reporte-donut-item__pct" id="pctEfectivo">—</strong>
                    </div>
                    <div class="reporte-donut-item">
                        <span class="reporte-donut-item__dot reporte-donut-item__dot--digital"></span>
                        <span class="reporte-donut-item__label">Digital</span>
                        <strong class="reporte-donut-item__pct" id="pctDigital">—</strong>
                    </div>
                </div>
            </div>

            <div id="reporteMetodosList" class="reporte-metodos"></div>
        </div>

        <div class="panel">
            <h2 class="reporte-section-title" style="margin-bottom:4px;">Gastos por categoría</h2>
            <p class="reporte-section-sub" style="margin-bottom:20px;">Desglose de egresos</p>
            <div id="reporteGastosCat" class="reporte-cats"></div>
        </div>

    </div>

    {{-- Top productos --}}
    <div class="panel cliente-reveal cliente-reveal-4">
        <h2 class="reporte-section-title" style="margin-bottom:4px;">Top productos más vendidos</h2>
        <p class="reporte-section-sub" style="margin-bottom:20px;">Ordenados por unidades vendidas en el período</p>
        <div id="reporteTopProductos" class="reporte-top"></div>
    </div>

    {{-- Island de datos: todos los períodos pre-calculados --}}
    <script id="reportesData" type="application/json">{!! $reportesJson !!}</script>

    @push('styles')
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/reportes.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/cliente/reportes.js') }}" defer></script>
    @endpush

</x-cliente-layout>
