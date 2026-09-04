<x-cliente-layout title="Dashboard">

    {{-- Dashboard del negocio cliente — datos reales
         (App\Http\Controllers\Cliente\DashboardController). Ganancia neta
         ya consulta la tabla real de gastos, aunque hoy siempre dé "$0 en
         gastos" porque ese módulo todavía no existe -no es un dato falso,
         es que de verdad no hay nada registrado ahí todavía. Estado de
         caja sí es 100% real (App\Models\Cliente\Caja), igual que en la
         página de Caja. --}}

    <div class="cliente-page-header cliente-reveal cliente-reveal-1">
        <p class="cliente-page-header__eyebrow">Tu negocio</p>
        <h1 class="cliente-page-header__title">Bienvenida, {{ auth()->user()->nombres }}</h1>
        <p class="cliente-page-header__date">{{ now()->locale('es')->translatedFormat('l, d \d\e F \d\e Y') }}</p>
    </div>

    @if (session('status') === 'facturacion-bloqueada')
        <div class="cliente-form-banner cliente-form-banner--warning cliente-reveal cliente-reveal-1">
            Tu plan no incluye facturación electrónica. Si la necesitas, escríbenos para activarla.
        </div>
    @endif

    <div class="quick-actions cliente-reveal cliente-reveal-2">
        <button type="button" class="quick-action" id="nuevaVentaBtn">
            <span class="quick-action__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 7V6a6 6 0 0 1 12 0v1"/>
                    <path d="M4 7h16l-1.5 13.5a2 2 0 0 1-2 1.5H7.5a2 2 0 0 1-2-1.5L4 7Z"/>
                </svg>
            </span>
            <span class="quick-action__text">
                <span class="quick-action__label">Nueva venta</span>
                <span class="quick-action__hint">Registrar una venta</span>
            </span>
        </button>

        <button type="button" class="quick-action quick-action--sand" id="registrarCompraBtn">
            <span class="quick-action__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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

        <button type="button" class="quick-action quick-action--slate" id="abrirCajaAction" data-caja-abierta="{{ $cajaAbierta ? '1' : '0' }}">
            <span class="quick-action__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="2.5" y="6" width="19" height="12" rx="2.5"/>
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M6 9v.01M18 15v.01"/>
                </svg>
            </span>
            <span class="quick-action__text">
                <span class="quick-action__label">{{ $cajaAbierta ? 'Caja abierta' : 'Abrir caja' }}</span>
                <span class="quick-action__hint">{{ $cajaAbierta ? 'Ir a Caja' : 'Empezar el día' }}</span>
            </span>
        </button>
    </div>

    <div class="stat-grid cliente-reveal cliente-reveal-3">
        <div class="stat-card stat-card--sage">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 7V6a6 6 0 0 1 12 0v1"/>
                    <path d="M4 7h16l-1.5 13.5a2 2 0 0 1-2 1.5H7.5a2 2 0 0 1-2-1.5L4 7Z"/>
                </svg>
            </div>
            <span class="stat-card__value" id="ventasHoyValor" data-count="{{ $totalVentasHoy }}" data-prefix="$">$0</span>
            <span class="stat-card__label">Ventas de hoy</span>
            <span class="stat-card__meta" id="ventasHoyMeta" data-cantidad="{{ $cantidadVentasHoy }}">{{ $cantidadVentasHoy }} transacci{{ $cantidadVentasHoy === 1 ? 'ón' : 'ones' }}</span>
        </div>

        <div class="stat-card stat-card--sage">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 7 13.5 15.5 8.5 10.5 2 17"/>
                    <path d="M16 7h6v6"/>
                </svg>
            </div>
            <span class="stat-card__value" id="gananciaBrutaValor" data-count="{{ $gananciaBrutaHoy }}" data-prefix="$">$0</span>
            <span class="stat-card__label">Ganancia bruta del día</span>
            <span class="stat-card__meta">Ventas − costo de productos</span>
        </div>

        <div class="stat-card stat-card--sand">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 7a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v3"/>
                    <path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2H5a2 2 0 0 1-2-2Z"/>
                    <path d="M17 14h.01"/>
                </svg>
            </div>
            <span class="stat-card__value" id="gananciaNetaValor" data-count="{{ $gananciaNetaHoy }}" data-prefix="$">$0</span>
            <span class="stat-card__label">Ganancia neta de la caja</span>
            <span class="stat-card__meta">Ganancia bruta − gastos de caja. Ver Reportes para el total real</span>
        </div>

        <div class="stat-card {{ $cajaAbierta ? 'stat-card--sage' : 'stat-card--mist' }}" id="cajaEstadoCard">
            <div class="stat-card__icon" id="cajaEstadoIcono">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="2.5" y="6" width="19" height="12" rx="2.5"/>
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M6 9v.01M18 15v.01"/>
                </svg>
            </div>
            <span class="stat-card__value stat-card__value--status" id="cajaEstadoValor">{{ $cajaAbierta ? 'Abierta' : 'Cerrada' }}</span>
            <span class="stat-card__label">Estado de caja</span>
            <span class="stat-card__meta" id="cajaEstadoMeta">
                @if ($cajaAbierta)
                    Base ${{ number_format($cajaAbierta['baseInicial'], 0, ',', '.') }} · {{ $cajaAbierta['horaApertura'] }}
                @else
                    Todavía no la has abierto hoy
                @endif
            </span>
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
                @foreach ($ventasSemana as $dia)
                    <div class="bar-chart__col {{ $dia['esHoy'] ? 'is-current' : '' }}">
                        <div class="bar-chart__track">
                            <div class="bar-chart__fill" data-pct="{{ $dia['pct'] }}" data-value="{{ $dia['valor'] }}" data-total="{{ $dia['total'] }}" data-es-hoy="{{ $dia['esHoy'] ? '1' : '0' }}"></div>
                        </div>
                        <span class="bar-chart__label">{{ $dia['label'] }}</span>
                    </div>
                @endforeach
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
                @forelse ($ventasRecientes as $venta)
                    {{-- $venta['hora'] viene como "Hoy, 3:06 p.m." (misma
                         forma que usa Ventas) -acá alcanza con la hora
                         sola, "Hoy" ya lo dice el subtítulo del panel. --}}
                    <div class="sale-row {{ $venta['metodo'] === 'efectivo' ? 'sale-row--efectivo' : '' }}" data-venta-id="{{ $venta['id'] }}" tabindex="0">
                        <div class="sale-row__icon">
                            @if ($venta['metodo'] === 'efectivo')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="2.5" y="6" width="19" height="12" rx="2.5"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="2.5" y="5" width="19" height="14" rx="2.5"/>
                                    <path d="M2.5 10h19M6 15h4"/>
                                </svg>
                            @endif
                        </div>
                        <div class="sale-row__info">
                            <div class="sale-row__id">Venta #{{ $venta['id'] }}</div>
                            <div class="sale-row__meta">{{ \Illuminate\Support\Str::after($venta['hora'], ', ') }} · {{ $venta['metodo'] === 'efectivo' ? 'Efectivo' : 'Wompi' }}</div>
                        </div>
                        <div class="sale-row__monto">${{ number_format($venta['total'], 0, ',', '.') }}</div>
                    </div>
                @empty
                    <p class="sale-list__empty">Todavía no hay ventas registradas hoy.</p>
                @endforelse
            </div>
        </div>
    </div>

    <script id="ventasRecientesData" type="application/json">{!! json_encode($ventasRecientes) !!}</script>

    @include('cliente.partials.venta-slide-over')

    @include('cliente.partials.nueva-venta-modal')

    @include('cliente.partials.registrar-compra-modal')

    @include('cliente.partials.abrir-caja-modal')

    @push('styles')
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/dashboard.css') }}">
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/nueva-venta-modal.css') }}">
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/inventario.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/cliente/dashboard.js') }}" defer></script>
        <script src="{{ asset_v('assets/js/cliente/venta-slide-over.js') }}" defer></script>
        <script src="{{ asset_v('assets/js/cliente/nueva-venta-modal.js') }}" defer></script>
        <script src="{{ asset_v('assets/js/cliente/registrar-compra-modal.js') }}" defer></script>
        <script src="{{ asset_v('assets/js/cliente/abrir-caja-modal.js') }}" defer></script>
    @endpush

</x-cliente-layout>
