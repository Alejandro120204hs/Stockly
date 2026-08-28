<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — Stockly</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:500,600,700|work-sans:400,500,600,700" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/layout.css') }}">
    @stack('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="cliente-body">

    @php
        $iniciales = $authUser
            ? strtoupper(mb_substr($authUser->nombres, 0, 1).mb_substr($authUser->apellidos, 0, 1))
            : '';
        $logoUrl = $empresa?->logoUrl();
    @endphp

    <div class="cliente-shell">

        <!-- ==========================================================
             SIDEBAR — compartido por todas las vistas del panel del
             negocio cliente (ver app/View/Components/ClienteLayout.php).
             ========================================================== -->
        <aside class="cliente-sidebar" id="clienteSidebar">
            <a href="{{ url('/cliente/dashboard') }}" class="cliente-sidebar__brand">
                <svg class="cliente-sidebar__brand-mark" viewBox="0 0 32 32" fill="none" aria-hidden="true">
                    <path d="M16 3 27 9v14L16 29 5 23V9Z" stroke="#C9B99A" stroke-width="1.6" stroke-linejoin="round"/>
                    <path d="M5 9 16 15 27 9M16 15v14" stroke="#4A7C6F" stroke-width="1.6" stroke-linejoin="round"/>
                </svg>
                Stockly
            </a>
            <span class="cliente-sidebar__badge">{{ $empresa?->nombre_negocio }}</span>

            <nav class="cliente-sidebar__nav">
                <a href="{{ url('/cliente/dashboard') }}" class="cliente-nav-item {{ request()->is('cliente/dashboard') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3.5" y="3.5" width="7.5" height="7.5" rx="1.5"/>
                        <rect x="13" y="3.5" width="7.5" height="7.5" rx="1.5"/>
                        <rect x="3.5" y="13" width="7.5" height="7.5" rx="1.5"/>
                        <rect x="13" y="13" width="7.5" height="7.5" rx="1.5"/>
                    </svg>
                    <span class="cliente-nav-item__label">Dashboard</span>
                </a>

                <a href="{{ url('/cliente/ventas') }}" class="cliente-nav-item {{ request()->is('cliente/ventas') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 7V6a6 6 0 0 1 12 0v1"/>
                        <path d="M4 7h16l-1.5 13.5a2 2 0 0 1-2 1.5H7.5a2 2 0 0 1-2-1.5L4 7Z"/>
                    </svg>
                    <span class="cliente-nav-item__label">Ventas</span>
                </a>

                 <a href="{{ url('/cliente/proveedores') }}" class="cliente-nav-item {{ request()->is('cliente/proveedores') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="10" width="18" height="10" rx="1.5"/>
                        <path d="M7 10V7a5 5 0 0 1 10 0v3"/>
                    </svg>
                    <span class="cliente-nav-item__label">Proveedores</span>
                </a>

                <a href="{{ url('/cliente/inventario') }}" class="cliente-nav-item {{ request()->is('cliente/inventario') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 8 12 3 3 8v8l9 5 9-5V8Z"/>
                        <path d="M3 8l9 5 9-5M12 13v8"/>
                    </svg>
                    <span class="cliente-nav-item__label">Inventario</span>
                </a>

                <a href="{{ url('/cliente/caja') }}" class="cliente-nav-item {{ request()->is('cliente/caja') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="2.5" y="6" width="19" height="12" rx="2.5"/>
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M6 9v.01M18 15v.01"/>
                    </svg>
                    <span class="cliente-nav-item__label">Caja</span>
                </a>

                <a href="{{ url('/cliente/gastos') }}" class="cliente-nav-item {{ request()->is('cliente/gastos') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 7a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v3"/>
                        <path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2H5a2 2 0 0 1-2-2Z"/>
                        <path d="M17 14h.01"/>
                    </svg>
                    <span class="cliente-nav-item__label">Gastos</span>
                </a>

               

                <a href="{{ url('/cliente/facturacion') }}" class="cliente-nav-item {{ request()->is('cliente/facturacion') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>
                        <path d="M14 3v5h5"/>
                        <path d="M9 13h6M9 17h6M9 9h1"/>
                    </svg>
                    <span class="cliente-nav-item__label">Facturación</span>
                </a>

                

                <a href="#" class="cliente-nav-item" data-coming-soon="La sección de Reportes está en construcción.">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 20V10M12 20V4M20 20v-7"/>
                    </svg>
                    <span class="cliente-nav-item__label">Reportes</span>
                </a>

                <a href="{{ url('/cliente/perfil') }}" class="cliente-nav-item {{ request()->is('cliente/perfil') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.04 1.56V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 9 19.35a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.65 15a1.7 1.7 0 0 0-1.56-1.04H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.65 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.65a1.7 1.7 0 0 0 1.04-1.56V3a2 2 0 1 1 4 0v.09a1.7 1.7 0 0 0 1.04 1.56 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.35 9a1.7 1.7 0 0 0 1.56 1.04H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.56 1.04Z"/>
                    </svg>
                    <span class="cliente-nav-item__label">Mi perfil</span>
                </a>
            </nav>

            <div class="cliente-sidebar__footer">
                <a href="{{ url('/cliente/perfil') }}" class="cliente-user-card">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="" class="cliente-user-card__avatar cliente-user-card__avatar--img">
                    @else
                        <span class="cliente-user-card__avatar">{{ $iniciales }}</span>
                    @endif
                    <div class="cliente-user-card__info">
                        <p class="cliente-user-card__name">{{ $authUser?->nombres }} {{ $authUser?->apellidos }}</p>
                        <p class="cliente-user-card__role">{{ $empresa?->tipo_negocio }}</p>
                    </div>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="cliente-logout">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <path d="M16 17l5-5-5-5"/>
                            <path d="M21 12H9"/>
                        </svg>
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        <div class="cliente-sidebar-overlay" id="clienteSidebarOverlay"></div>

        <div class="cliente-main">
            <!-- ======================================================
                 TOPBAR — igual, compartida por todas las vistas
                 ====================================================== -->
            <header class="cliente-topbar">
                <button type="button" class="cliente-topbar__menu-toggle" aria-label="Abrir menú">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <h1 class="cliente-topbar__title">{{ $title }}</h1>

                <div class="cliente-topbar__actions">
                    <a href="{{ url('/cliente/perfil') }}" aria-label="Mi perfil">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="" class="cliente-topbar__avatar cliente-topbar__avatar--img">
                        @else
                            <span class="cliente-topbar__avatar">{{ $iniciales }}</span>
                        @endif
                    </a>
                </div>
            </header>

            <main class="cliente-content">
                {{ $slot }}
            </main>
        </div>
    </div>

    <script src="{{ asset_v('assets/js/cliente/layout.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
