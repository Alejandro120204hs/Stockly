<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — Stockly Admin</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:500,600,700|work-sans:400,500,600,700" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset_v('assets/css/admin/layout.css') }}">
    @stack('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="admin-body" data-flash-status="{{ session('status') }}">

    <div class="admin-shell">

        <!-- ==========================================================
             SIDEBAR — el mismo para todas las vistas del panel de admin,
             no hay que repetirlo en cada página (ver uso de este
             componente con &lt;x-admin-layout&gt;).
             ========================================================== -->
        <aside class="admin-sidebar" id="adminSidebar">
            <a href="{{ url('/admin/dashboard') }}" class="admin-sidebar__brand">
                <svg class="admin-sidebar__brand-mark" viewBox="0 0 32 32" fill="none">
                    <path d="M16 3 27 9v14L16 29 5 23V9Z" stroke="#C9B99A" stroke-width="1.6" stroke-linejoin="round"/>
                    <path d="M5 9 16 15 27 9M16 15v14" stroke="#4A7C6F" stroke-width="1.6" stroke-linejoin="round"/>
                </svg>
                Stockly
            </a>
            <span class="admin-sidebar__badge">Super Admin</span>

            <nav class="admin-sidebar__nav">
                <a href="{{ url('/admin/dashboard') }}" class="admin-nav-item {{ request()->is('admin/dashboard') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3.5" y="3.5" width="7.5" height="7.5" rx="1.5"/>
                        <rect x="13" y="3.5" width="7.5" height="7.5" rx="1.5"/>
                        <rect x="3.5" y="13" width="7.5" height="7.5" rx="1.5"/>
                        <rect x="13" y="13" width="7.5" height="7.5" rx="1.5"/>
                    </svg>
                    <span class="admin-nav-item__label">Dashboard</span>
                </a>

                <a href="{{ url('/admin/empresas') }}" class="admin-nav-item {{ request()->is('admin/empresas') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 21V6l8-3 8 3v15"/>
                        <path d="M4 21h16"/>
                        <path d="M9 9h1M14 9h1M9 13h1M14 13h1M9 21v-4h6v4"/>
                    </svg>
                    <span class="admin-nav-item__label">Empresas</span>
                </a>

                <a href="{{ url('/admin/pagos') }}" class="admin-nav-item {{ request()->is('admin/pagos') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 4h14v16l-3.5-2-3.5 2-3.5-2L5 20Z"/>
                        <path d="M8.5 9h7M8.5 12.5h7M8.5 16h4"/>
                    </svg>
                    <span class="admin-nav-item__label">Pagos y suscripciones</span>
                    @if ($pendingPayments > 0)
                        <span class="admin-nav-item__badge">{{ $pendingPayments }}</span>
                    @endif
                </a>

                <a href="{{ url('/admin/modulos') }}" class="admin-nav-item {{ request()->is('admin/modulos') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3 3 8l9 5 9-5-9-5Z"/>
                        <path d="M3 12l9 5 9-5"/>
                        <path d="M3 16l9 5 9-5"/>
                    </svg>
                    <span class="admin-nav-item__label">Módulos</span>
                </a>

                <a href="{{ url('/admin/perfil') }}" class="admin-nav-item {{ request()->is('admin/perfil') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.04 1.56V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 9 19.35a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.65 15a1.7 1.7 0 0 0-1.56-1.04H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.65 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.65a1.7 1.7 0 0 0 1.04-1.56V3a2 2 0 1 1 4 0v.09a1.7 1.7 0 0 0 1.04 1.56 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.35 9a1.7 1.7 0 0 0 1.56 1.04H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.56 1.04Z"/>
                    </svg>
                    <span class="admin-nav-item__label">Mi perfil</span>
                </a>
            </nav>

            <div class="admin-sidebar__footer">
                <a href="{{ url('/admin/perfil') }}" class="admin-user-card">
                    <span class="admin-user-card__avatar">AH</span>
                    <div class="admin-user-card__info">
                        <p class="admin-user-card__name">Alejandro Hernández</p>
                        <p class="admin-user-card__role">Super Admin</p>
                    </div>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="admin-logout">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <path d="M16 17l5-5-5-5"/>
                            <path d="M21 12H9"/>
                        </svg>
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        <div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>

        <div class="admin-main">
            <!-- ======================================================
                 TOPBAR — igual, compartida por todas las vistas
                 ====================================================== -->
            <header class="admin-topbar">
                <button type="button" class="admin-topbar__menu-toggle" aria-label="Abrir menú">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <h1 class="admin-topbar__title">{{ $title }}</h1>

                <div class="admin-topbar__actions">
                    <button type="button" class="admin-icon-button" aria-label="Notificaciones">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 8a6 6 0 1 1 12 0c0 4 1.5 5.5 1.5 5.5H4.5S6 12 6 8Z"/>
                            <path d="M9.5 17a2.5 2.5 0 0 0 5 0"/>
                        </svg>
                        @if ($pendingPayments > 0)
                            <span class="admin-icon-button__dot"></span>
                        @endif
                    </button>

                    <a href="{{ url('/admin/perfil') }}" class="admin-topbar__avatar" aria-label="Mi perfil">AH</a>
                </div>
            </header>

            <main class="admin-content">
                {{ $slot }}
            </main>
        </div>
    </div>

    <script src="{{ asset_v('assets/js/admin/layout.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
