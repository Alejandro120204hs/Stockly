{{--
    Dashboard del Super Admin — SOLO FRONTEND por ahora.
    Todos los datos de esta vista (empresas, pagos, ingresos) son de
    ejemplo, escritos directo acá abajo. Cuando se conecte el backend,
    esto se reemplaza por datos reales que vengan de un controlador
    (Empresa::count(), PagoSuscripcion::where(...), etc.).
--}}
<x-admin-layout title="Dashboard" :pending-payments="3">

    <div class="admin-page-header admin-reveal admin-reveal-1">
        <div>
            <p class="admin-page-header__eyebrow">Panel de Super Admin</p>
            <h2 class="admin-page-header__title">Bienvenido, Alejandro</h2>
        </div>
        <p class="admin-page-header__date">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3.5" y="5" width="17" height="16" rx="2"/>
                <path d="M8 3v4M16 3v4M3.5 10h17"/>
            </svg>
            {{-- locale fijado solo acá (en vez de en config/app.php) para no
                 cambiar el idioma de toda la app sin que se pida --}}
            {{ now()->locale('es')->translatedFormat('l, j \d\e F \d\e Y') }}
        </p>
    </div>

    <!-- ==========================================================
         STAT CARDS
         ========================================================== -->
    <section class="stat-grid admin-reveal admin-reveal-2">
        <div class="stat-card stat-card--sage">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 21V6l8-3 8 3v15"/>
                    <path d="M4 21h16"/>
                    <path d="M9 9h1M14 9h1M9 13h1M14 13h1M9 21v-4h6v4"/>
                </svg>
            </div>
            <span class="stat-card__value" data-count="18">0</span>
            <span class="stat-card__label">Empresas activas</span>
        </div>

        <div class="stat-card stat-card--sand">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="8.5"/>
                    <path d="M12 7.5V12l3 2"/>
                </svg>
            </div>
            <span class="stat-card__value" data-count="3">0</span>
            <span class="stat-card__label">Por vencer (7 días)</span>
        </div>

        <div class="stat-card stat-card--error">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="8.5"/>
                    <path d="m9.5 9.5 5 5m0-5-5 5"/>
                </svg>
            </div>
            <span class="stat-card__value" data-count="2">0</span>
            <span class="stat-card__label">Vencidas</span>
        </div>

        <div class="stat-card stat-card--mist">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="8.5"/>
                    <path d="M10 9v6M14 9v6"/>
                </svg>
            </div>
            <span class="stat-card__value" data-count="1">0</span>
            <span class="stat-card__label">Suspendidas</span>
        </div>

        <div class="stat-card stat-card--sage">
            <span class="stat-card__trend">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 17l6-6 4 4 8-8"/>
                    <path d="M15 7h6v6"/>
                </svg>
                +12%
            </span>
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="8.5"/>
                    <path d="M9.5 9.8c0-1 .9-1.6 2.2-1.6s2.3.6 2.3 1.5c0 2-4.8 1.4-4.8 3.4 0 .9.9 1.5 2.3 1.5s2.4-.6 2.4-1.6M12 6.7v1.2M12 15.9v1.2"/>
                </svg>
            </div>
            <span class="stat-card__value" data-prefix="$" data-count="12450000">$0</span>
            <span class="stat-card__label">Ingresos del mes (COP)</span>
        </div>
    </section>

    <!-- ==========================================================
         PAGOS PENDIENTES + PRÓXIMAS A VENCER
         ========================================================== -->
    <section class="admin-grid-2col admin-reveal admin-reveal-3">
        <div class="panel">
            <div class="panel__header">
                <div>
                    <h3 class="panel__title">Pagos pendientes de activar</h3>
                    <span class="panel__subtitle">Wompi ya confirmó el pago; falta tu aprobación</span>
                </div>
                <a href="#" class="panel__link">
                    Ver todos
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                </a>
            </div>

            <div class="payment-queue">
                <div class="payment-row">
                    <div class="payment-row__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 4h14v16l-3.5-2-3.5 2-3.5-2L5 20Z"/>
                            <path d="M8.5 9h7M8.5 12.5h7M8.5 16h4"/>
                        </svg>
                    </div>
                    <div class="payment-row__info">
                        <p class="payment-row__empresa">Licores El Roble</p>
                        <p class="payment-row__meta">Administración e Inventario · hace 2 horas</p>
                    </div>
                    <span class="payment-row__monto">$89.000</span>
                    <div class="payment-row__action">
                        <button type="button" class="activar-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-9"/></svg>
                            <span class="activar-btn__label">Activar</span>
                        </button>
                    </div>
                </div>

                <div class="payment-row">
                    <div class="payment-row__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 4h14v16l-3.5-2-3.5 2-3.5-2L5 20Z"/>
                            <path d="M8.5 9h7M8.5 12.5h7M8.5 16h4"/>
                        </svg>
                    </div>
                    <div class="payment-row__info">
                        <p class="payment-row__empresa">Ferretería Central</p>
                        <p class="payment-row__meta">Pasarela de Pagos · hace 5 horas</p>
                    </div>
                    <span class="payment-row__monto">$59.000</span>
                    <div class="payment-row__action">
                        <button type="button" class="activar-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-9"/></svg>
                            <span class="activar-btn__label">Activar</span>
                        </button>
                    </div>
                </div>

                <div class="payment-row">
                    <div class="payment-row__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 4h14v16l-3.5-2-3.5 2-3.5-2L5 20Z"/>
                            <path d="M8.5 9h7M8.5 12.5h7M8.5 16h4"/>
                        </svg>
                    </div>
                    <div class="payment-row__info">
                        <p class="payment-row__empresa">Boutique Luna</p>
                        <p class="payment-row__meta">Administración e Inventario · ayer</p>
                    </div>
                    <span class="payment-row__monto">$89.000</span>
                    <div class="payment-row__action">
                        <button type="button" class="activar-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-9"/></svg>
                            <span class="activar-btn__label">Activar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <div>
                    <h3 class="panel__title">Próximas a vencer</h3>
                    <span class="panel__subtitle">Vale la pena contactarlas antes</span>
                </div>
            </div>

            <div class="expiring-list">
                <div class="expiring-row">
                    <span class="expiring-row__days"><strong>2</strong>días</span>
                    <div class="expiring-row__info">
                        <p class="expiring-row__empresa">Comestibles La 20</p>
                        <p class="expiring-row__meta">Vence el {{ now()->addDays(2)->format('d/m/Y') }}</p>
                    </div>
                </div>
                <div class="expiring-row">
                    <span class="expiring-row__days"><strong>4</strong>días</span>
                    <div class="expiring-row__info">
                        <p class="expiring-row__empresa">Farmacia San José</p>
                        <p class="expiring-row__meta">Vence el {{ now()->addDays(4)->format('d/m/Y') }}</p>
                    </div>
                </div>
                <div class="expiring-row">
                    <span class="expiring-row__days"><strong>6</strong>días</span>
                    <div class="expiring-row__info">
                        <p class="expiring-row__empresa">Ropa Urbana</p>
                        <p class="expiring-row__meta">Vence el {{ now()->addDays(6)->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==========================================================
         INGRESOS + DESGLOSE DE MÓDULOS
         ========================================================== -->
    <section class="admin-grid-2col admin-reveal admin-reveal-4">
        <div class="panel">
            <div class="panel__header">
                <div>
                    <h3 class="panel__title">Ingresos (últimos 6 meses)</h3>
                    <span class="panel__subtitle">Pagos de suscripción ya activados</span>
                </div>
            </div>

            <div class="bar-chart">
                <div class="bar-chart__col">
                    <div class="bar-chart__track">
                        <div class="bar-chart__fill" data-pct="66" data-value="$8.2M"></div>
                    </div>
                    <span class="bar-chart__label">Mar</span>
                </div>
                <div class="bar-chart__col">
                    <div class="bar-chart__track">
                        <div class="bar-chart__fill" data-pct="73" data-value="$9.1M"></div>
                    </div>
                    <span class="bar-chart__label">Abr</span>
                </div>
                <div class="bar-chart__col">
                    <div class="bar-chart__track">
                        <div class="bar-chart__fill" data-pct="79" data-value="$9.8M"></div>
                    </div>
                    <span class="bar-chart__label">May</span>
                </div>
                <div class="bar-chart__col">
                    <div class="bar-chart__track">
                        <div class="bar-chart__fill" data-pct="92" data-value="$11.4M"></div>
                    </div>
                    <span class="bar-chart__label">Jun</span>
                </div>
                <div class="bar-chart__col">
                    <div class="bar-chart__track">
                        <div class="bar-chart__fill" data-pct="85" data-value="$10.6M"></div>
                    </div>
                    <span class="bar-chart__label">Jul</span>
                </div>
                <div class="bar-chart__col is-current">
                    <div class="bar-chart__track">
                        <div class="bar-chart__fill" data-pct="100" data-value="$12.45M"></div>
                    </div>
                    <span class="bar-chart__label">Ago</span>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <div>
                    <h3 class="panel__title">Empresas por módulo</h3>
                    <span class="panel__subtitle">Sobre 24 empresas registradas</span>
                </div>
            </div>

            <div class="module-breakdown">
                <div class="module-row">
                    <div class="module-row__top">
                        <span class="module-row__name">Administración e Inventario</span>
                        <span class="module-row__count">19 empresas</span>
                    </div>
                    <div class="module-row__track">
                        <div class="module-row__fill" data-pct="79"></div>
                    </div>
                </div>
                <div class="module-row module-row--sand">
                    <div class="module-row__top">
                        <span class="module-row__name">Pasarela de Pagos</span>
                        <span class="module-row__count">11 empresas</span>
                    </div>
                    <div class="module-row__track">
                        <div class="module-row__fill" data-pct="46"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</x-admin-layout>
