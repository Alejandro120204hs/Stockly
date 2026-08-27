<x-admin-layout title="Pagos y suscripciones">

    {{-- Pagos de suscripción del panel Super Admin — SOLO FRONTEND por
         ahora, datos mock. El estado "rechazado" todavía no existe en la
         tabla `pagos_suscripcion` real (hoy solo tiene 'pago_recibido' y
         'activado') -cuando conectemos el backend hay que agregarlo,
         junto con un campo para el motivo del rechazo. --}}
    @php
        $pagos = [
            ['id' => 1, 'empresa' => 'Licores El Roble', 'modulo' => 'Pasarela de Pagos', 'monto' => 89000, 'metodo' => 'Wompi', 'referencia' => 'WOM-88213-A9', 'fechaPago' => '24 ago 2026, 2:10 p.m.', 'estado' => 'pendiente'],
            ['id' => 2, 'empresa' => 'Ferretería Central', 'modulo' => 'Administración e Inventario', 'monto' => 59000, 'metodo' => 'Wompi', 'referencia' => 'WOM-88190-C2', 'fechaPago' => '24 ago 2026, 9:42 a.m.', 'estado' => 'pendiente'],
            ['id' => 3, 'empresa' => 'Boutique Luna', 'modulo' => 'Pasarela de Pagos', 'monto' => 89000, 'metodo' => 'Transferencia', 'referencia' => 'TRF-40217', 'fechaPago' => '23 ago 2026, 6:05 p.m.', 'estado' => 'pendiente'],
            ['id' => 4, 'empresa' => 'Comestibles La 20', 'modulo' => 'Administración e Inventario', 'monto' => 59000, 'metodo' => 'Wompi', 'referencia' => 'WOM-87950-B7', 'fechaPago' => '20 ago 2026, 11:15 a.m.', 'estado' => 'activado', 'activadoPor' => 'Alejandro Hernández', 'fechaActivacion' => '20 ago 2026, 11:40 a.m.'],
            ['id' => 5, 'empresa' => 'Farmacia San José', 'modulo' => 'Pasarela de Pagos', 'monto' => 89000, 'metodo' => 'Wompi', 'referencia' => 'WOM-87820-D4', 'fechaPago' => '18 ago 2026, 4:30 p.m.', 'estado' => 'activado', 'activadoPor' => 'Alejandro Hernández', 'fechaActivacion' => '18 ago 2026, 5:02 p.m.'],
            ['id' => 6, 'empresa' => 'Ropa Urbana', 'modulo' => 'Administración e Inventario', 'monto' => 59000, 'metodo' => 'Transferencia', 'referencia' => 'TRF-40105', 'fechaPago' => '15 ago 2026, 8:50 a.m.', 'estado' => 'rechazado', 'motivoRechazo' => 'El comprobante de transferencia no coincidía con el monto reportado por Wompi.'],
            ['id' => 7, 'empresa' => 'Licorera del Norte', 'modulo' => 'Pasarela de Pagos', 'monto' => 89000, 'metodo' => 'Wompi', 'referencia' => 'WOM-87640-E1', 'fechaPago' => '10 ago 2026, 1:20 p.m.', 'estado' => 'rechazado', 'motivoRechazo' => 'Pago duplicado -ya existía una activación con la misma referencia.'],
            ['id' => 8, 'empresa' => 'Ferretería El Tornillo', 'modulo' => 'Pasarela de Pagos', 'monto' => 89000, 'metodo' => 'Wompi', 'referencia' => 'WOM-87510-F6', 'fechaPago' => '05 ago 2026, 3:15 p.m.', 'estado' => 'activado', 'activadoPor' => 'Alejandro Hernández', 'fechaActivacion' => '05 ago 2026, 3:44 p.m.'],
            ['id' => 9, 'empresa' => 'Comestibles Doña Rosa', 'modulo' => 'Administración e Inventario', 'monto' => 59000, 'metodo' => 'Wompi', 'referencia' => 'WOM-87400-G3', 'fechaPago' => '02 ago 2026, 10:05 a.m.', 'estado' => 'activado', 'activadoPor' => 'Alejandro Hernández', 'fechaActivacion' => '02 ago 2026, 10:20 a.m.'],
            ['id' => 10, 'empresa' => 'Farmacia Vitalia', 'modulo' => 'Pasarela de Pagos', 'monto' => 89000, 'metodo' => 'Transferencia', 'referencia' => 'TRF-39980', 'fechaPago' => '28 jul 2026, 5:40 p.m.', 'estado' => 'activado', 'activadoPor' => 'Alejandro Hernández', 'fechaActivacion' => '28 jul 2026, 6:10 p.m.'],
        ];

        $estadoLabels = [
            'pendiente' => 'Pendiente',
            'activado' => 'Activado',
            'rechazado' => 'Rechazado',
        ];

        $estadoPillClass = [
            'pendiente' => 'status-pill--por-vencer',
            'activado' => 'status-pill--activo',
            'rechazado' => 'status-pill--vencido',
        ];

        $pendientesCount = collect($pagos)->where('estado', 'pendiente')->count();
    @endphp

    <div class="admin-page-header admin-reveal admin-reveal-1">
        <div>
            <p class="admin-page-header__eyebrow">Panel de Super Admin</p>
            <h1 class="admin-page-header__title">Pagos y suscripciones</h1>
        </div>
        <div class="admin-page-header__date">
            {{ $pendientesCount }} pago{{ $pendientesCount === 1 ? '' : 's' }} esperando aprobación
        </div>
    </div>

    <div class="panel admin-reveal admin-reveal-2" style="margin-bottom: 20px;">
        <div class="empresas-toolbar">
            <div class="empresas-toolbar__search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <input type="search" id="pagosSearch" placeholder="Buscar por empresa o referencia..." autocomplete="off">
            </div>

            <select id="pagosEstadoFilter" class="empresas-toolbar__select">
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendiente</option>
                <option value="activado">Activado</option>
                <option value="rechazado">Rechazado</option>
            </select>
        </div>

        <div class="data-table-wrap">
            <table class="data-table" id="pagosTable">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Módulo</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Fecha de pago</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pagos as $pago)
                        <tr class="data-table__row" data-pago-id="{{ $pago['id'] }}" tabindex="0">
                            <td>
                                <div class="data-table__empresa">{{ $pago['empresa'] }}</div>
                                <div class="data-table__meta">{{ $pago['referencia'] }}</div>
                            </td>
                            <td class="data-table__meta">{{ $pago['modulo'] }}</td>
                            <td class="data-table__meta">${{ number_format($pago['monto'], 0, ',', '.') }}</td>
                            <td class="data-table__meta">{{ $pago['metodo'] }}</td>
                            <td class="data-table__meta">{{ $pago['fechaPago'] }}</td>
                            <td>
                                <span class="status-pill {{ $estadoPillClass[$pago['estado']] }}">
                                    {{ $estadoLabels[$pago['estado']] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="data-table__empty" id="pagosEmpty" hidden>No hay pagos que coincidan con la búsqueda.</p>
        </div>
    </div>

    <script id="pagosData" type="application/json">{!! json_encode($pagos) !!}</script>

    {{-- ==================================================================
         PANEL LATERAL — detalle de un pago
         ================================================================== --}}
    <div class="slide-over-overlay" id="pagoSlideOverOverlay"></div>

    <aside class="slide-over" id="pagoSlideOver" aria-hidden="true">
        <div class="slide-over__header">
            <div>
                <h2 class="slide-over__title" id="pagoSlideOverEmpresa">—</h2>
                <span class="status-pill" id="pagoSlideOverEstado">—</span>
            </div>
            <button type="button" class="slide-over__close" id="pagoSlideOverClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="slide-over__body">
            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Detalle del pago</h3>
                <div class="slide-over__field"><span>Módulo</span><strong id="pagoSlideOverModulo">—</strong></div>
                <div class="slide-over__field"><span>Monto</span><strong id="pagoSlideOverMonto">—</strong></div>
                <div class="slide-over__field"><span>Método</span><strong id="pagoSlideOverMetodo">—</strong></div>
                <div class="slide-over__field"><span>Referencia</span><strong id="pagoSlideOverReferencia">—</strong></div>
                <div class="slide-over__field"><span>Fecha de pago</span><strong id="pagoSlideOverFecha">—</strong></div>
            </section>

            {{-- Pendiente: acciones --}}
            <section class="slide-over__section" id="pagoSeccionAcciones">
                <h3 class="slide-over__section-title">Acción</h3>
                <div class="slide-over__actions">
                    <button type="button" class="slide-over__btn slide-over__btn--activar" id="pagoActivarBtn">
                        Activar módulo
                    </button>
                    <button type="button" class="slide-over__btn slide-over__btn--suspender" id="pagoRechazarBtn">
                        Rechazar pago
                    </button>
                </div>

                <div class="slide-over__reason" id="pagoRechazarReasonBox" hidden>
                    <label for="pagoRechazarMotivo" class="slide-over__reason-label">Motivo del rechazo</label>
                    <textarea id="pagoRechazarMotivo" class="slide-over__reason-input" rows="3" placeholder="Ej: el comprobante no coincide con el monto..."></textarea>
                    <div class="slide-over__reason-actions">
                        <button type="button" class="slide-over__btn slide-over__btn--suspender" id="pagoRechazarConfirmar">Confirmar rechazo</button>
                        <button type="button" class="slide-over__btn-text" id="pagoRechazarCancelar">Cancelar</button>
                    </div>
                </div>
            </section>

            {{-- Activado: quién y cuándo --}}
            <section class="slide-over__section" id="pagoSeccionActivado" hidden>
                <h3 class="slide-over__section-title">Activado por</h3>
                <div class="slide-over__field"><span>Usuario</span><strong id="pagoSlideOverActivadoPor">—</strong></div>
                <div class="slide-over__field"><span>Fecha</span><strong id="pagoSlideOverFechaActivacion">—</strong></div>
            </section>

            {{-- Rechazado: motivo --}}
            <section class="slide-over__section" id="pagoSeccionRechazado" hidden>
                <h3 class="slide-over__section-title">Motivo del rechazo</h3>
                <p class="slide-over__motivo" id="pagoSlideOverMotivo">—</p>
            </section>
        </div>
    </aside>

    @push('styles')
        <link rel="stylesheet" href="{{ asset_v('assets/css/admin/pagos.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/admin/pagos.js') }}" defer></script>
    @endpush

</x-admin-layout>
