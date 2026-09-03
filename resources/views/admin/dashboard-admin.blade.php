{{--
    Dashboard del Super Admin — datos reales
    (App\Http\Controllers\Admin\DashboardController). "Pagos pendientes de
    activar" del mock ya no aplica -acá no hay pasarela que confirme un
    pago aparte del admin; el pago se confirma y se activa en el mismo
    paso (ver Admin\EmpresaController::activar()), así que esa sección
    pasó a ser "Actividad reciente" con el historial real de activaciones.
--}}
<x-admin-layout title="Dashboard">

    <div class="admin-page-header admin-reveal admin-reveal-1">
        <div>
            <p class="admin-page-header__eyebrow">Panel de Super Admin</p>
            <h2 class="admin-page-header__title">Bienvenido, {{ auth()->user()->nombres }}</h2>
        </div>
        <p class="admin-page-header__date">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3.5" y="5" width="17" height="16" rx="2"/>
                <path d="M8 3v4M16 3v4M3.5 10h17"/>
            </svg>
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
            <span class="stat-card__value" data-count="{{ $empresasActivas }}">0</span>
            <span class="stat-card__label">Empresas activas</span>
        </div>

        <div class="stat-card stat-card--sand">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="8.5"/>
                    <path d="M12 7.5V12l3 2"/>
                </svg>
            </div>
            <span class="stat-card__value" data-count="{{ $porVencer }}">0</span>
            <span class="stat-card__label">Por vencer (7 días)</span>
        </div>

        <div class="stat-card stat-card--error">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="8.5"/>
                    <path d="m9.5 9.5 5 5m0-5-5 5"/>
                </svg>
            </div>
            <span class="stat-card__value" data-count="{{ $vencidas }}">0</span>
            <span class="stat-card__label">Vencidas</span>
        </div>

        <div class="stat-card stat-card--mist">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="8.5"/>
                    <path d="M10 9v6M14 9v6"/>
                </svg>
            </div>
            <span class="stat-card__value" data-count="{{ $suspendidas }}">0</span>
            <span class="stat-card__label">Suspendidas</span>
        </div>

        <div class="stat-card stat-card--sage">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="8.5"/>
                    <path d="M9.5 9.8c0-1 .9-1.6 2.2-1.6s2.3.6 2.3 1.5c0 2-4.8 1.4-4.8 3.4 0 .9.9 1.5 2.3 1.5s2.4-.6 2.4-1.6M12 6.7v1.2M12 15.9v1.2"/>
                </svg>
            </div>
            <span class="stat-card__value" data-count="{{ $ingresosMes }}" data-prefix="$">$0</span>
            <span class="stat-card__label">Ingresos del mes (COP)</span>
        </div>
    </section>

    <!-- ==========================================================
         ACTIVIDAD RECIENTE + PRÓXIMAS A VENCER
         ========================================================== -->
    <section class="admin-grid-2col admin-reveal admin-reveal-3">
        <div class="panel">
            <div class="panel__header">
                <div>
                    <h3 class="panel__title">Actividad reciente</h3>
                    <span class="panel__subtitle">Últimas licencias activadas</span>
                </div>
                <a href="{{ route('admin.empresas') }}" class="panel__link">
                    Ver empresas
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                </a>
            </div>

            <div class="payment-queue">
                @forelse ($activacionesRecientes as $activacion)
                    <div class="payment-row">
                        <div class="payment-row__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 4h14v16l-3.5-2-3.5 2-3.5-2L5 20Z"/>
                                <path d="M8.5 9h7M8.5 12.5h7M8.5 16h4"/>
                            </svg>
                        </div>
                        <div class="payment-row__info">
                            <p class="payment-row__empresa">{{ $activacion['empresa'] }}</p>
                            <p class="payment-row__meta">Plan {{ $activacion['plan'] }} · {{ $activacion['hace'] }}</p>
                        </div>
                        <span class="payment-row__monto">
                            {{ $activacion['monto'] !== null ? '$'.number_format($activacion['monto'], 0, ',', '.') : '—' }}
                        </span>
                    </div>
                @empty
                    <p class="data-table__empty" style="margin:0; padding:20px 0; text-align:center;">
                        Todavía no se ha activado ninguna licencia.
                    </p>
                @endforelse
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
                @forelse ($proximasAVencer as $empresa)
                    <div class="expiring-row">
                        <span class="expiring-row__days"><strong>{{ $empresa['dias'] }}</strong>días</span>
                        <div class="expiring-row__info">
                            <p class="expiring-row__empresa">{{ $empresa['nombre'] }}</p>
                            <p class="expiring-row__meta">Vence el {{ $empresa['fecha'] }}</p>
                        </div>
                    </div>
                @empty
                    <p class="data-table__empty" style="margin:0; padding:20px 0; text-align:center;">
                        Ninguna empresa está por vencer en los próximos 7 días.
                    </p>
                @endforelse
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
                @foreach ($ingresosUltimos6Meses as $mes)
                    <div class="bar-chart__col {{ $mes['esActual'] ? 'is-current' : '' }}">
                        <div class="bar-chart__track">
                            <div class="bar-chart__fill" data-pct="{{ $mes['pct'] }}" data-value="${{ number_format($mes['total'], 0, ',', '.') }}"></div>
                        </div>
                        <span class="bar-chart__label">{{ $mes['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <div>
                    <h3 class="panel__title">Empresas por módulo</h3>
                    <span class="panel__subtitle">Sobre {{ $totalEmpresas }} {{ $totalEmpresas === 1 ? 'empresa registrada' : 'empresas registradas' }}</span>
                </div>
            </div>

            <div class="module-breakdown">
                <div class="module-row">
                    <div class="module-row__top">
                        <span class="module-row__name">Facturación electrónica</span>
                        <span class="module-row__count">{{ $countFacturacion }} {{ $countFacturacion === 1 ? 'empresa' : 'empresas' }}</span>
                    </div>
                    <div class="module-row__track">
                        <div class="module-row__fill" style="width: {{ $totalEmpresas > 0 ? round(($countFacturacion / $totalEmpresas) * 100) : 0 }}%"></div>
                    </div>
                </div>
                <div class="module-row module-row--sand">
                    <div class="module-row__top">
                        <span class="module-row__name">Solo Administración</span>
                        <span class="module-row__count">{{ $countAdministracion }} {{ $countAdministracion === 1 ? 'empresa' : 'empresas' }}</span>
                    </div>
                    <div class="module-row__track">
                        <div class="module-row__fill" style="width: {{ $totalEmpresas > 0 ? round(($countAdministracion / $totalEmpresas) * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('styles')
        <link rel="stylesheet" href="{{ asset_v('assets/css/admin/dashboard.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/admin/dashboard.js') }}" defer></script>
    @endpush

</x-admin-layout>
