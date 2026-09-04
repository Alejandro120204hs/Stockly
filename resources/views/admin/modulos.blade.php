<x-admin-layout title="Módulos">

    {{-- Catálogo real (App\Http\Controllers\Admin\ModuloController). No hay
         precio por módulo -el cobro es por plan (mensual/trimestral/...),
         no por módulo activo (ver Pagos y suscripciones). Solo lectura:
         prender/apagar Facturación electrónica se hace desde el panel de
         cada empresa. Los dos módulos son complementarios -toda empresa
         cae en exactamente uno. --}}

    <div class="admin-page-header admin-reveal admin-reveal-1">
        <div>
            <p class="admin-page-header__eyebrow">Panel de Super Admin</p>
            <h1 class="admin-page-header__title">Módulos</h1>
        </div>
        <div class="admin-page-header__date">
            {{ $totalEmpresas }} {{ $totalEmpresas === 1 ? 'empresa registrada' : 'empresas registradas' }}
        </div>
    </div>

    <div class="modulo-grid admin-reveal admin-reveal-2">
        @foreach ($modulos as $modulo)
            <button type="button" class="modulo-card" data-modulo-id="{{ $modulo['id'] }}">
                <div class="modulo-card__top">
                    <div class="modulo-card__icon modulo-card__icon--{{ $modulo['id'] === 'administracion' ? 'box' : 'card' }}">
                        @if ($modulo['id'] === 'administracion')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 8 12 3 3 8v8l9 5 9-5V8Z"/>
                                <path d="M3 8l9 5 9-5M12 13v8"/>
                            </svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2.5" y="5" width="19" height="14" rx="2.5"/>
                                <path d="M2.5 10h19M6 15h4"/>
                            </svg>
                        @endif
                    </div>
                </div>

                <h2 class="modulo-card__name">{{ $modulo['nombre'] }}</h2>
                <p class="modulo-card__desc">{{ $modulo['descripcion'] }}</p>

                <div class="modulo-card__stat-row">
                    <span>Empresas</span>
                    <strong>{{ $modulo['activas'] }}/{{ $modulo['total'] }} · {{ $modulo['pct'] }}%</strong>
                </div>
                <div class="module-row {{ $modulo['id'] === 'administracion_factus' ? 'module-row--sand' : '' }}">
                    <div class="module-row__track">
                        <div class="module-row__fill" data-pct="{{ $modulo['pct'] }}"></div>
                    </div>
                </div>
            </button>
        @endforeach
    </div>

    <script id="modulosData" type="application/json">{!! json_encode($modulos) !!}</script>

    {{-- ==================================================================
         PANEL LATERAL — detalle de un módulo
         ================================================================== --}}
    <div class="slide-over-overlay" id="moduloSlideOverOverlay"></div>

    <aside class="slide-over" id="moduloSlideOver" aria-hidden="true">
        <div class="slide-over__header">
            <div>
                <h2 class="slide-over__title" id="moduloSlideOverNombre">—</h2>
                <span class="slide-over__subtitle" id="moduloSlideOverResumen">—</span>
            </div>
            <button type="button" class="slide-over__close" id="moduloSlideOverClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="slide-over__body">
            {{-- Solo la lista de "con este módulo" -no tiene sentido un
                 "sin este módulo" acá: como los dos módulos se excluyen
                 entre sí, esa lista sería la misma que "con este módulo"
                 de la otra tarjeta, y para "Solo Administración" además
                 daba a entender (mal) que esas empresas no tienen
                 Administración, cuando sí la tienen -solo que además
                 tienen Factus. --}}
            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Empresas en este grupo</h3>
                <div id="moduloSlideOverActivasList" class="modulo-empresa-list"></div>
            </section>
        </div>
    </aside>

    @push('styles')
        <link rel="stylesheet" href="{{ asset_v('assets/css/admin/modulos.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/admin/modulos.js') }}" defer></script>
    @endpush

</x-admin-layout>
