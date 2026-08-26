<x-cliente-layout title="Dashboard">

    {{-- Dashboard del negocio cliente — SOLO FRONTEND por ahora, datos
         mock. El único backend real de todo el panel cliente es cerrar
         sesión (ver el <form> del sidebar en layouts/cliente-layout). --}}

    <div class="cliente-page-header cliente-reveal cliente-reveal-1">
        <p class="cliente-page-header__eyebrow">Tu negocio</p>
        <h1 class="cliente-page-header__title">Bienvenida, Laura</h1>
        <p class="cliente-page-header__date">{{ now()->locale('es')->translatedFormat('l, d \d\e F \d\e Y') }}</p>
    </div>

    <div class="quick-actions cliente-reveal cliente-reveal-2">
        <button type="button" class="quick-action" id="nuevaVentaBtn">
            <span class="quick-action__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 7V6a6 6 0 0 1 12 0v1"/>
                    <path d="M4 7h16l-1.5 13.5a2 2 0 0 1-2 1.5H7.5a2 2 0 0 1-2-1.5L4 7Z"/>
                </svg>
            </span>
            <span class="quick-action__text">
                <span class="quick-action__label">Nueva venta</span>
                <span class="quick-action__hint">Registrar una venta</span>
            </span>
        </button>

        <button type="button" class="quick-action quick-action--sand" data-coming-soon="Registrar compra estará disponible pronto.">
            <span class="quick-action__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="20" r="1"/>
                    <circle cx="18" cy="20" r="1"/>
                    <path d="M3 4h2l2.3 11.4a2 2 0 0 0 2 1.6h7.4a2 2 0 0 0 2-1.6L21 8H6"/>
                </svg>
            </span>
            <span class="quick-action__text">
                <span class="quick-action__label">Registrar compra</span>
                <span class="quick-action__hint">Sumar stock a bodega</span>
            </span>
        </button>

        <button type="button" class="quick-action quick-action--slate" id="abrirCajaAction">
            <span class="quick-action__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2.5" y="6" width="19" height="12" rx="2.5"/>
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M6 9v.01M18 15v.01"/>
                </svg>
            </span>
            <span class="quick-action__text">
                <span class="quick-action__label">Abrir caja</span>
                <span class="quick-action__hint">Empezar el día</span>
            </span>
        </button>
    </div>

    <div class="stat-grid cliente-reveal cliente-reveal-3">
        <div class="stat-card stat-card--sage">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 7V6a6 6 0 0 1 12 0v1"/>
                    <path d="M4 7h16l-1.5 13.5a2 2 0 0 1-2 1.5H7.5a2 2 0 0 1-2-1.5L4 7Z"/>
                </svg>
            </div>
            <span class="stat-card__value" data-count="1240000" data-prefix="$">$0</span>
            <span class="stat-card__label">Ventas de hoy</span>
            <span class="stat-card__meta">18 transacciones</span>
        </div>

        <div class="stat-card stat-card--sage">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 7 13.5 15.5 8.5 10.5 2 17"/>
                    <path d="M16 7h6v6"/>
                </svg>
            </div>
            <span class="stat-card__value" data-count="410000" data-prefix="$">$0</span>
            <span class="stat-card__label">Ganancia bruta del día</span>
            <span class="stat-card__meta">Ventas − costo de productos</span>
        </div>

        <div class="stat-card stat-card--sand">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 7a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v3"/>
                    <path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2H5a2 2 0 0 1-2-2Z"/>
                    <path d="M17 14h.01"/>
                </svg>
            </div>
            <span class="stat-card__value" data-count="285000" data-prefix="$">$0</span>
            <span class="stat-card__label">Ganancia neta del día</span>
            <span class="stat-card__meta">Ganancia bruta − gastos</span>
        </div>

        <div class="stat-card stat-card--mist" id="cajaEstadoCard">
            <div class="stat-card__icon" id="cajaEstadoIcono">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2.5" y="6" width="19" height="12" rx="2.5"/>
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M6 9v.01M18 15v.01"/>
                </svg>
            </div>
            <span class="stat-card__value stat-card__value--status" id="cajaEstadoValor">Cerrada</span>
            <span class="stat-card__label">Estado de caja</span>
            <span class="stat-card__meta" id="cajaEstadoMeta">Todavía no la has abierto hoy</span>
        </div>
    </div>

    <div class="cliente-grid-2col cliente-reveal cliente-reveal-3">
        <div class="panel">
            <div class="panel__header">
                <div>
                    <h2 class="panel__title">Ventas de la semana</h2>
                    <span class="panel__subtitle">Lunes a domingo</span>
                </div>
            </div>

            <div class="bar-chart">
                <div class="bar-chart__col">
                    <div class="bar-chart__track">
                        <div class="bar-chart__fill" data-pct="53" data-value="$890.000"></div>
                    </div>
                    <span class="bar-chart__label">Lun</span>
                </div>
                <div class="bar-chart__col is-current">
                    <div class="bar-chart__track">
                        <div class="bar-chart__fill" data-pct="74" data-value="$1.240.000"></div>
                    </div>
                    <span class="bar-chart__label">Mar</span>
                </div>
                <div class="bar-chart__col">
                    <div class="bar-chart__track">
                        <div class="bar-chart__fill" data-pct="45" data-value="$760.000"></div>
                    </div>
                    <span class="bar-chart__label">Mié</span>
                </div>
                <div class="bar-chart__col">
                    <div class="bar-chart__track">
                        <div class="bar-chart__fill" data-pct="58" data-value="$980.000"></div>
                    </div>
                    <span class="bar-chart__label">Jue</span>
                </div>
                <div class="bar-chart__col">
                    <div class="bar-chart__track">
                        <div class="bar-chart__fill" data-pct="86" data-value="$1.450.000"></div>
                    </div>
                    <span class="bar-chart__label">Vie</span>
                </div>
                <div class="bar-chart__col">
                    <div class="bar-chart__track">
                        <div class="bar-chart__fill" data-pct="100" data-value="$1.680.000"></div>
                    </div>
                    <span class="bar-chart__label">Sáb</span>
                </div>
                <div class="bar-chart__col">
                    <div class="bar-chart__track">
                        <div class="bar-chart__fill" data-pct="32" data-value="$540.000"></div>
                    </div>
                    <span class="bar-chart__label">Dom</span>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <div>
                    <h2 class="panel__title">Ventas recientes</h2>
                    <span class="panel__subtitle">Hoy</span>
                </div>
            </div>

            <div class="sale-list">
                <div class="sale-row">
                    <div class="sale-row__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2.5" y="5" width="19" height="14" rx="2.5"/>
                            <path d="M2.5 10h19M6 15h4"/>
                        </svg>
                    </div>
                    <div class="sale-row__info">
                        <div class="sale-row__id">Venta #128</div>
                        <div class="sale-row__meta">2:45 p.m. · Wompi</div>
                    </div>
                    <div class="sale-row__monto">$85.000</div>
                </div>

                <div class="sale-row sale-row--efectivo">
                    <div class="sale-row__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2.5" y="6" width="19" height="12" rx="2.5"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </div>
                    <div class="sale-row__info">
                        <div class="sale-row__id">Venta #127</div>
                        <div class="sale-row__meta">2:10 p.m. · Efectivo</div>
                    </div>
                    <div class="sale-row__monto">$124.000</div>
                </div>

                <div class="sale-row sale-row--efectivo">
                    <div class="sale-row__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2.5" y="6" width="19" height="12" rx="2.5"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </div>
                    <div class="sale-row__info">
                        <div class="sale-row__id">Venta #126</div>
                        <div class="sale-row__meta">1:30 p.m. · Efectivo</div>
                    </div>
                    <div class="sale-row__monto">$45.000</div>
                </div>

                <div class="sale-row">
                    <div class="sale-row__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2.5" y="5" width="19" height="14" rx="2.5"/>
                            <path d="M2.5 10h19M6 15h4"/>
                        </svg>
                    </div>
                    <div class="sale-row__info">
                        <div class="sale-row__id">Venta #125</div>
                        <div class="sale-row__meta">12:55 p.m. · Wompi</div>
                    </div>
                    <div class="sale-row__monto">$210.000</div>
                </div>

                <div class="sale-row sale-row--efectivo">
                    <div class="sale-row__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2.5" y="6" width="19" height="12" rx="2.5"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </div>
                    <div class="sale-row__info">
                        <div class="sale-row__id">Venta #124</div>
                        <div class="sale-row__meta">11:40 a.m. · Efectivo</div>
                    </div>
                    <div class="sale-row__monto">$68.000</div>
                </div>

                <div class="sale-row">
                    <div class="sale-row__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2.5" y="5" width="19" height="14" rx="2.5"/>
                            <path d="M2.5 10h19M6 15h4"/>
                        </svg>
                    </div>
                    <div class="sale-row__info">
                        <div class="sale-row__id">Venta #123</div>
                        <div class="sale-row__meta">10:55 a.m. · Wompi</div>
                    </div>
                    <div class="sale-row__monto">$156.000</div>
                </div>

                <div class="sale-row sale-row--efectivo">
                    <div class="sale-row__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2.5" y="6" width="19" height="12" rx="2.5"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </div>
                    <div class="sale-row__info">
                        <div class="sale-row__id">Venta #122</div>
                        <div class="sale-row__meta">10:12 a.m. · Efectivo</div>
                    </div>
                    <div class="sale-row__monto">$32.000</div>
                </div>

                <div class="sale-row sale-row--efectivo">
                    <div class="sale-row__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2.5" y="6" width="19" height="12" rx="2.5"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </div>
                    <div class="sale-row__info">
                        <div class="sale-row__id">Venta #121</div>
                        <div class="sale-row__meta">9:48 a.m. · Efectivo</div>
                    </div>
                    <div class="sale-row__monto">$95.000</div>
                </div>

                <div class="sale-row">
                    <div class="sale-row__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2.5" y="5" width="19" height="14" rx="2.5"/>
                            <path d="M2.5 10h19M6 15h4"/>
                        </svg>
                    </div>
                    <div class="sale-row__info">
                        <div class="sale-row__id">Venta #120</div>
                        <div class="sale-row__meta">9:15 a.m. · Wompi</div>
                    </div>
                    <div class="sale-row__monto">$178.000</div>
                </div>

                <div class="sale-row sale-row--efectivo">
                    <div class="sale-row__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2.5" y="6" width="19" height="12" rx="2.5"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </div>
                    <div class="sale-row__info">
                        <div class="sale-row__id">Venta #119</div>
                        <div class="sale-row__meta">8:30 a.m. · Efectivo</div>
                    </div>
                    <div class="sale-row__monto">$54.000</div>
                </div>
            </div>
        </div>
    </div>

    @include('cliente.partials.nueva-venta-modal')

    @push('styles')
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/dashboard.css') }}">
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/nueva-venta-modal.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/cliente/dashboard.js') }}" defer></script>
        <script src="{{ asset_v('assets/js/cliente/nueva-venta-modal.js') }}" defer></script>
    @endpush

</x-cliente-layout>
