<x-admin-layout title="Empresas">

    {{-- Vista de empresas del Super Admin — SOLO FRONTEND por ahora, datos
         mock. Cuando conectemos el backend, esto se llenará desde la tabla
         `empresas` real (con sus módulos vía `empresa_modulos`). --}}
    @php
        $empresas = [
            ['id' => 1, 'nombre' => 'Licores El Roble', 'tipo' => 'Licorera', 'nit' => '900123456-7', 'dv' => '7', 'tipoPersona' => 'Jurídica', 'regimen' => 'Régimen común', 'correo' => 'contacto@elroble.co', 'telefono' => '300 123 4567', 'direccion' => 'Cra 45 #12-30', 'departamento' => 'Antioquia', 'ciudad' => 'Medellín', 'estado' => 'activo', 'vencimiento' => '18 sep 2026', 'modulos' => [['nombre' => 'Administración e Inventario', 'activo' => true], ['nombre' => 'Pasarela de Pagos', 'activo' => true]]],
            ['id' => 2, 'nombre' => 'Ferretería Central', 'tipo' => 'Ferretería', 'nit' => '900456789-2', 'dv' => '2', 'tipoPersona' => 'Jurídica', 'regimen' => 'Régimen simple', 'correo' => 'ventas@ferrecentral.co', 'telefono' => '301 456 7890', 'direccion' => 'Calle 10 #8-22', 'departamento' => 'Cundinamarca', 'ciudad' => 'Bogotá', 'estado' => 'activo', 'vencimiento' => '02 oct 2026', 'modulos' => [['nombre' => 'Administración e Inventario', 'activo' => true], ['nombre' => 'Pasarela de Pagos', 'activo' => false]]],
            ['id' => 3, 'nombre' => 'Boutique Luna', 'tipo' => 'Ropa y accesorios', 'nit' => '900789123-5', 'dv' => '5', 'tipoPersona' => 'Natural', 'regimen' => 'Régimen simple', 'correo' => 'hola@boutiqueluna.co', 'telefono' => '302 789 1234', 'direccion' => 'Av. Poblado #4-50', 'departamento' => 'Antioquia', 'ciudad' => 'Medellín', 'estado' => 'por_vencer', 'vencimiento' => '27 ago 2026', 'modulos' => [['nombre' => 'Administración e Inventario', 'activo' => true], ['nombre' => 'Pasarela de Pagos', 'activo' => true]]],
            ['id' => 4, 'nombre' => 'Comestibles La 20', 'tipo' => 'Comestibles/víveres', 'nit' => '900321654-1', 'dv' => '1', 'tipoPersona' => 'Natural', 'regimen' => 'Régimen simple', 'correo' => 'la20@comestibles.co', 'telefono' => '304 321 6540', 'direccion' => 'Calle 20 #15-08', 'departamento' => 'Valle del Cauca', 'ciudad' => 'Cali', 'estado' => 'por_vencer', 'vencimiento' => '26 ago 2026', 'modulos' => [['nombre' => 'Administración e Inventario', 'activo' => true], ['nombre' => 'Pasarela de Pagos', 'activo' => false]]],
            ['id' => 5, 'nombre' => 'Farmacia San José', 'tipo' => 'Farmacia', 'nit' => '900654987-8', 'dv' => '8', 'tipoPersona' => 'Jurídica', 'regimen' => 'Régimen común', 'correo' => 'gerencia@farmasanjose.co', 'telefono' => '305 654 9870', 'direccion' => 'Cra 30 #45-12', 'departamento' => 'Cundinamarca', 'ciudad' => 'Bogotá', 'estado' => 'vencido', 'vencimiento' => '10 ago 2026', 'modulos' => [['nombre' => 'Administración e Inventario', 'activo' => true], ['nombre' => 'Pasarela de Pagos', 'activo' => true]]],
            ['id' => 6, 'nombre' => 'Ropa Urbana', 'tipo' => 'Ropa y accesorios', 'nit' => '900987321-4', 'dv' => '4', 'tipoPersona' => 'Natural', 'regimen' => 'Régimen simple', 'correo' => 'contacto@ropaurbana.co', 'telefono' => '310 987 3214', 'direccion' => 'Calle 50 #20-15', 'departamento' => 'Atlántico', 'ciudad' => 'Barranquilla', 'estado' => 'vencido', 'vencimiento' => '05 ago 2026', 'modulos' => [['nombre' => 'Administración e Inventario', 'activo' => true], ['nombre' => 'Pasarela de Pagos', 'activo' => false]]],
            ['id' => 7, 'nombre' => 'Licorera del Norte', 'tipo' => 'Licorera', 'nit' => '900147258-3', 'dv' => '3', 'tipoPersona' => 'Jurídica', 'regimen' => 'Régimen común', 'correo' => 'info@licoreranorte.co', 'telefono' => '311 147 2580', 'direccion' => 'Cra 15 #60-20', 'departamento' => 'Atlántico', 'ciudad' => 'Barranquilla', 'estado' => 'suspendido', 'vencimiento' => '15 jul 2026', 'modulos' => [['nombre' => 'Administración e Inventario', 'activo' => false], ['nombre' => 'Pasarela de Pagos', 'activo' => false]]],
            ['id' => 8, 'nombre' => 'Ferretería El Tornillo', 'tipo' => 'Ferretería', 'nit' => '900258147-9', 'dv' => '9', 'tipoPersona' => 'Natural', 'regimen' => 'Régimen simple', 'correo' => 'ventas@eltornillo.co', 'telefono' => '312 258 1470', 'direccion' => 'Calle 8 #33-40', 'departamento' => 'Santander', 'ciudad' => 'Bucaramanga', 'estado' => 'activo', 'vencimiento' => '14 nov 2026', 'modulos' => [['nombre' => 'Administración e Inventario', 'activo' => true], ['nombre' => 'Pasarela de Pagos', 'activo' => true]]],
            ['id' => 9, 'nombre' => 'Comestibles Dona Rosa', 'tipo' => 'Comestibles/víveres', 'nit' => '900369258-6', 'dv' => '6', 'tipoPersona' => 'Natural', 'regimen' => 'Régimen simple', 'correo' => 'donarosa@comestibles.co', 'telefono' => '313 369 2580', 'direccion' => 'Cra 22 #18-05', 'departamento' => 'Risaralda', 'ciudad' => 'Pereira', 'estado' => 'activo', 'vencimiento' => '30 oct 2026', 'modulos' => [['nombre' => 'Administración e Inventario', 'activo' => true], ['nombre' => 'Pasarela de Pagos', 'activo' => false]]],
            ['id' => 10, 'nombre' => 'Farmacia Vitalia', 'tipo' => 'Farmacia', 'nit' => '900741852-0', 'dv' => '0', 'tipoPersona' => 'Jurídica', 'regimen' => 'Régimen común', 'correo' => 'vitalia@farmacia.co', 'telefono' => '314 741 8520', 'direccion' => 'Av. Sexta #12-18', 'departamento' => 'Valle del Cauca', 'ciudad' => 'Cali', 'estado' => 'activo', 'vencimiento' => '22 dic 2026', 'modulos' => [['nombre' => 'Administración e Inventario', 'activo' => true], ['nombre' => 'Pasarela de Pagos', 'activo' => true]]],
        ];

        $estadoLabels = [
            'activo' => 'Activo',
            'por_vencer' => 'Por vencer',
            'vencido' => 'Vencido',
            'suspendido' => 'Suspendido',
        ];
    @endphp

    <div class="admin-page-header admin-reveal admin-reveal-1">
        <div>
            <p class="admin-page-header__eyebrow">Panel de Super Admin</p>
            <h1 class="admin-page-header__title">Empresas registradas</h1>
        </div>
        <div class="admin-page-header__date">
            Mostrando {{ count($empresas) }} de 24 empresas
        </div>
    </div>

    <div class="panel admin-reveal admin-reveal-2" style="margin-bottom: 20px;">
        <div class="empresas-toolbar">
            <div class="empresas-toolbar__search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <input type="search" id="empresasSearch" placeholder="Buscar por nombre o NIT..." autocomplete="off">
            </div>

            <select id="empresasEstadoFilter" class="empresas-toolbar__select">
                <option value="">Todos los estados</option>
                <option value="activo">Activo</option>
                <option value="por_vencer">Por vencer</option>
                <option value="vencido">Vencido</option>
                <option value="suspendido">Suspendido</option>
            </select>
        </div>

        <div class="data-table-wrap">
            <table class="data-table" id="empresasTable">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>NIT</th>
                        <th>Estado</th>
                        <th>Módulos</th>
                        <th>Vence</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($empresas as $empresa)
                        @php
                            $modulosActivos = collect($empresa['modulos'])->where('activo', true)->count();
                        @endphp
                        <tr class="data-table__row" data-empresa-id="{{ $empresa['id'] }}" tabindex="0">
                            <td>
                                <div class="data-table__empresa">{{ $empresa['nombre'] }}</div>
                                <div class="data-table__meta">{{ $empresa['tipo'] }}</div>
                            </td>
                            <td class="data-table__meta">{{ $empresa['nit'] }}</td>
                            <td>
                                <span class="status-pill status-pill--{{ str_replace('_', '-', $empresa['estado']) }}">
                                    {{ $estadoLabels[$empresa['estado']] }}
                                </span>
                            </td>
                            <td class="data-table__meta" data-modulos-cell>{{ $modulosActivos }}/{{ count($empresa['modulos']) }}</td>
                            <td class="data-table__meta">{{ $empresa['vencimiento'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="data-table__empty" id="empresasEmpty" hidden>No hay empresas que coincidan con la búsqueda.</p>
        </div>
    </div>

    {{-- Los datos completos (incluyendo módulos y datos fiscales) viven acá
         para que el JS los lea al abrir el panel lateral, sin duplicar el
         array de arriba. --}}
    <script id="empresasData" type="application/json">{!! json_encode($empresas) !!}</script>

    {{-- ==================================================================
         PANEL LATERAL (slide-over) — detalle de una empresa
         ================================================================== --}}
    <div class="slide-over-overlay" id="slideOverOverlay"></div>

    <aside class="slide-over" id="empresaSlideOver" aria-hidden="true">
        <div class="slide-over__header">
            <div>
                <h2 class="slide-over__title" id="slideOverNombre">—</h2>
                <span class="status-pill" id="slideOverEstado">—</span>
            </div>
            <button type="button" class="slide-over__close" id="slideOverClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="slide-over__body">
            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Contacto</h3>
                <div class="slide-over__field"><span>Correo</span><strong id="slideOverCorreo">—</strong></div>
                <div class="slide-over__field"><span>Teléfono</span><strong id="slideOverTelefono">—</strong></div>
                <div class="slide-over__field"><span>Dirección</span><strong id="slideOverDireccion">—</strong></div>
                <div class="slide-over__field"><span>Ubicación</span><strong id="slideOverUbicacion">—</strong></div>
            </section>

            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Datos fiscales</h3>
                <div class="slide-over__field"><span>NIT</span><strong id="slideOverNit">—</strong></div>
                <div class="slide-over__field"><span>Tipo de persona</span><strong id="slideOverTipoPersona">—</strong></div>
                <div class="slide-over__field"><span>Régimen</span><strong id="slideOverRegimen">—</strong></div>
            </section>

            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Suscripción</h3>
                <div class="slide-over__field"><span>Vence</span><strong id="slideOverVencimiento">—</strong></div>
                <div class="slide-over__actions">
                    <button type="button" class="slide-over__btn slide-over__btn--activar" id="slideOverActivar">
                        Activar suscripción
                    </button>
                    <button type="button" class="slide-over__btn slide-over__btn--suspender" id="slideOverSuspender">
                        Suspender empresa
                    </button>
                </div>
            </section>

            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Módulos contratados</h3>
                <div id="slideOverModulos"></div>
            </section>
        </div>
    </aside>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/admin/empresas.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset('assets/js/admin/empresas.js') }}" defer></script>
    @endpush

</x-admin-layout>
