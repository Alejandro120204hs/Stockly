<x-admin-layout title="Módulos">

    {{-- Catálogo de módulos del Super Admin — SOLO FRONTEND por ahora,
         datos mock. Las mismas 10 empresas de muestra que usamos en
         Empresas/Pagos, para que los datos sean consistentes entre
         páginas si alguien las compara. --}}
    @php
        $empresasMuestra = [
            ['nombre' => 'Licores El Roble', 'inventario' => true, 'pasarela' => true],
            ['nombre' => 'Ferretería Central', 'inventario' => true, 'pasarela' => false],
            ['nombre' => 'Boutique Luna', 'inventario' => true, 'pasarela' => true],
            ['nombre' => 'Comestibles La 20', 'inventario' => true, 'pasarela' => false],
            ['nombre' => 'Farmacia San José', 'inventario' => true, 'pasarela' => true],
            ['nombre' => 'Ropa Urbana', 'inventario' => true, 'pasarela' => false],
            ['nombre' => 'Licorera del Norte', 'inventario' => false, 'pasarela' => false],
            ['nombre' => 'Ferretería El Tornillo', 'inventario' => true, 'pasarela' => true],
            ['nombre' => 'Comestibles Doña Rosa', 'inventario' => true, 'pasarela' => false],
            ['nombre' => 'Farmacia Vitalia', 'inventario' => true, 'pasarela' => true],
        ];

        $modulos = [
            [
                'id' => 'inventario',
                'nombre' => 'Administración e Inventario',
                'descripcion' => 'Inventario doble (vitrina/bodega), productos, categorías, compras y proveedores.',
                'precio' => 59000,
                'icono' => 'box',
                'empresas' => collect($empresasMuestra)->map(fn ($e) => ['nombre' => $e['nombre'], 'activo' => $e['inventario']])->values()->all(),
            ],
            [
                'id' => 'pasarela',
                'nombre' => 'Pasarela de Pagos',
                'descripcion' => 'Cobro con Wompi directo desde las ventas, sin depender solo de efectivo.',
                'precio' => 89000,
                'icono' => 'card',
                'empresas' => collect($empresasMuestra)->map(fn ($e) => ['nombre' => $e['nombre'], 'activo' => $e['pasarela']])->values()->all(),
            ],
        ];

        foreach ($modulos as &$modulo) {
            $modulo['activas'] = collect($modulo['empresas'])->where('activo', true)->count();
            $modulo['total'] = count($modulo['empresas']);
            $modulo['pct'] = round(($modulo['activas'] / $modulo['total']) * 100);
            $modulo['ingreso'] = $modulo['activas'] * $modulo['precio'];
        }
        unset($modulo);
    @endphp

    <div class="admin-page-header admin-reveal admin-reveal-1">
        <div>
            <p class="admin-page-header__eyebrow">Panel de Super Admin</p>
            <h1 class="admin-page-header__title">Catálogo de módulos</h1>
        </div>
        <div class="admin-page-header__date">
            {{ count($modulos) }} módulos disponibles
        </div>
    </div>

    <div class="modulo-grid admin-reveal admin-reveal-2">
        @foreach ($modulos as $modulo)
            <button type="button" class="modulo-card" data-modulo-id="{{ $modulo['id'] }}">
                <div class="modulo-card__top">
                    <div class="modulo-card__icon modulo-card__icon--{{ $modulo['icono'] }}">
                        @if ($modulo['icono'] === 'box')
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
                    <span class="modulo-card__price">${{ number_format($modulo['precio'], 0, ',', '.') }}<small>/mes</small></span>
                </div>

                <h2 class="modulo-card__name">{{ $modulo['nombre'] }}</h2>
                <p class="modulo-card__desc">{{ $modulo['descripcion'] }}</p>

                <div class="modulo-card__stat-row">
                    <span>Empresas activas</span>
                    <strong>{{ $modulo['activas'] }}/{{ $modulo['total'] }} · {{ $modulo['pct'] }}%</strong>
                </div>
                <div class="module-row {{ $modulo['id'] === 'pasarela' ? 'module-row--sand' : '' }}">
                    <div class="module-row__track">
                        <div class="module-row__fill" data-pct="{{ $modulo['pct'] }}"></div>
                    </div>
                </div>

                <div class="modulo-card__stat-row modulo-card__stat-row--revenue">
                    <span>Ingreso mensual generado</span>
                    <strong>${{ number_format($modulo['ingreso'], 0, ',', '.') }}</strong>
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
                <span class="modulo-card__price" id="moduloSlideOverPrecio">—</span>
            </div>
            <button type="button" class="slide-over__close" id="moduloSlideOverClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="slide-over__body">
            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Resumen</h3>
                <div class="slide-over__field"><span>Empresas activas</span><strong id="moduloSlideOverActivas">—</strong></div>
                <div class="slide-over__field"><span>Ingreso mensual</span><strong id="moduloSlideOverIngreso">—</strong></div>
            </section>

            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Con el módulo activo</h3>
                <div id="moduloSlideOverActivasList" class="modulo-empresa-list"></div>
            </section>

            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Sin el módulo <span class="modulo-upsell-tag">posible upsell</span></h3>
                <div id="moduloSlideOverInactivasList" class="modulo-empresa-list"></div>
            </section>
        </div>
    </aside>

</x-admin-layout>
