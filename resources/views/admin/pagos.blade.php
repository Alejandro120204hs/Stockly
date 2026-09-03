<x-admin-layout title="Pagos y suscripciones">

    {{-- Historial real (App\Http\Controllers\Admin\PagoController). La
         activación manual sigue siendo cosa del panel de cada empresa
         (Admin\EmpresaController::activar(), nace ya 'activado'), pero
         ahora también llegan pagos 'pago_recibido' -el cliente los reporta
         con comprobante desde /cliente/suscripcion- que se aprueban o
         rechazan acá mismo. --}}
    @php
        $planOptions = [
            'Mensual' => 'Mensual',
            'Trimestral' => 'Trimestral',
            'Semestral' => 'Semestral',
            'Anual' => 'Anual',
        ];
    @endphp

    <div class="admin-page-header admin-reveal admin-reveal-1">
        <div>
            <p class="admin-page-header__eyebrow">Panel de Super Admin</p>
            <h1 class="admin-page-header__title">Pagos y suscripciones</h1>
        </div>
        <div class="admin-page-header__date">
            @if ($pendientes > 0)
                {{ $pendientes }} {{ $pendientes === 1 ? 'pago esperando revisión' : 'pagos esperando revisión' }}
            @else
                {{ $totalActivaciones }} {{ $totalActivaciones === 1 ? 'activación registrada' : 'activaciones registradas' }}
            @endif
        </div>
    </div>

    <div class="panel admin-reveal admin-reveal-2" style="margin-bottom: 20px;">
        <div class="pagos-resumen">
            <div class="pagos-resumen__item">
                <span class="pagos-resumen__label">Ingresos este mes</span>
                <strong class="pagos-resumen__value">${{ number_format($ingresosMes, 0, ',', '.') }}</strong>
            </div>
            <div class="pagos-resumen__item">
                <span class="pagos-resumen__label">Ingresos histórico</span>
                <strong class="pagos-resumen__value">${{ number_format($ingresosTotal, 0, ',', '.') }}</strong>
            </div>
            <div class="pagos-resumen__item">
                <span class="pagos-resumen__label">Activaciones totales</span>
                <strong class="pagos-resumen__value">{{ $totalActivaciones }}</strong>
            </div>
        </div>
    </div>

    <div class="panel admin-reveal admin-reveal-3" style="margin-bottom: 20px;">
        <div class="empresas-toolbar">
            <div class="empresas-toolbar__search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <input type="search" id="pagosSearch" placeholder="Buscar por empresa..." autocomplete="off">
            </div>

            <select id="pagosEstadoFilter" class="empresas-toolbar__select">
                <option value="">Todos los estados</option>
                <option value="pago_recibido">Pendiente</option>
                <option value="activado">Activado</option>
                <option value="rechazado">Rechazado</option>
            </select>

            <select id="pagosPlanFilter" class="empresas-toolbar__select">
                <option value="">Todos los planes</option>
                @foreach ($planOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="data-table-wrap">
            <table class="data-table" id="pagosTable">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Plan</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pagos as $pago)
                        <tr class="data-table__row" data-pago-id="{{ $pago['id'] }}" tabindex="0">
                            <td>
                                <div class="data-table__empresa">{{ $pago['empresa'] }}</div>
                            </td>
                            <td class="data-table__meta">{{ $pago['plan'] }}</td>
                            <td class="data-table__meta">{{ $pago['monto'] !== null ? '$' . number_format($pago['monto'], 0, ',', '.') : '—' }}</td>
                            <td class="data-table__meta">{{ $pago['metodo'] ?? '—' }}</td>
                            <td class="data-table__meta">{{ $pago['fechaActivacion'] ?? $pago['fechaPago'] ?? '—' }}</td>
                            <td>
                                <span class="status-pill status-pill--{{ $pago['estado'] === 'activado' ? 'activo' : ($pago['estado'] === 'rechazado' ? 'vencido' : 'por-vencer') }}">
                                    {{ $pago['estadoLabel'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>

            <p class="data-table__empty" id="pagosEmpty" {{ $pagos->isNotEmpty() ? 'hidden' : '' }}>
                {{ $pagos->isEmpty() ? 'Todavía no se ha activado ninguna suscripción.' : 'No hay pagos que coincidan con la búsqueda.' }}
            </p>
        </div>
    </div>

    <script id="pagosData" type="application/json">{!! json_encode($pagos->values()) !!}</script>

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
                <div class="slide-over__field"><span>Plan</span><strong id="pagoSlideOverPlan">—</strong></div>
                <div class="slide-over__field"><span>Monto</span><strong id="pagoSlideOverMonto">—</strong></div>
                <div class="slide-over__field"><span>Método</span><strong id="pagoSlideOverMetodo">—</strong></div>
                <div class="slide-over__field"><span>Fecha de pago</span><strong id="pagoSlideOverFechaPago">—</strong></div>
                <div class="slide-over__field" id="pagoSlideOverFechaActivacionRow"><span>Fecha de activación</span><strong id="pagoSlideOverFechaActivacion">—</strong></div>
                <div class="slide-over__field" id="pagoSlideOverActivadoPorRow"><span>Activado por</span><strong id="pagoSlideOverActivadoPor">—</strong></div>
            </section>

            <section class="slide-over__section" id="pagoSlideOverComprobanteSection" hidden>
                <h3 class="slide-over__section-title">Comprobante</h3>
                <a href="#" id="pagoSlideOverComprobanteLink" target="_blank" rel="noopener" class="slide-over__btn slide-over__btn--activar">Ver comprobante</a>
            </section>

            <section class="slide-over__section" id="pagoSlideOverVencimientoSection">
                <h3 class="slide-over__section-title">Vencimiento</h3>
                <div class="slide-over__field"><span>Antes de este pago</span><strong id="pagoSlideOverVencimientoAnterior">—</strong></div>
                <div class="slide-over__field"><span>Después de este pago</span><strong id="pagoSlideOverVencimientoNuevo">—</strong></div>
            </section>

            <section class="slide-over__section" id="pagoSlideOverRechazoSection" hidden>
                <h3 class="slide-over__section-title">Motivo del rechazo</h3>
                <p class="slide-over__motivo" id="pagoSlideOverMotivoRechazo">—</p>
            </section>

            {{-- Pendiente: acciones -aprobar activa de una, rechazar pide motivo. --}}
            <section class="slide-over__section" id="pagoSeccionAcciones" hidden>
                <h3 class="slide-over__section-title">Acción</h3>
                <div class="slide-over__actions">
                    <button type="button" class="slide-over__btn slide-over__btn--activar" id="pagoAprobarBtn">Aprobar</button>
                    <button type="button" class="slide-over__btn slide-over__btn--suspender" id="pagoRechazarBtn">Rechazar</button>
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
        </div>
    </aside>

    @push('styles')
        <link rel="stylesheet" href="{{ asset_v('assets/css/admin/pagos.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/admin/pagos.js') }}" defer></script>
    @endpush

</x-admin-layout>
