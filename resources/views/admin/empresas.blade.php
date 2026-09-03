<x-admin-layout title="Empresas">

    {{-- Empresas — datos reales (App\Http\Controllers\Admin\EmpresaController).
         El pago llega por fuera del sistema (Nequi, transferencia...); el
         admin confirma que le llegó y activa el plan desde el panel
         lateral. "Módulos contratados" se quitó -la licencia es por
         empresa completa, no por módulo (las tablas modulos/empresa_modulos
         existen en el schema pero no se usan). --}}
    @php
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
            {{ count($empresas) }} {{ count($empresas) === 1 ? 'empresa registrada' : 'empresas registradas' }}
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
                        <th>Vence</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($empresas as $empresa)
                        <tr class="data-table__row" data-empresa-id="{{ $empresa['id'] }}" tabindex="0">
                            <td>
                                <div class="data-table__empresa">{{ $empresa['nombre'] }}</div>
                                <div class="data-table__meta">{{ $empresa['tipo'] ?? '—' }}</div>
                            </td>
                            <td class="data-table__meta">{{ $empresa['nit'] }}{{ $empresa['dv'] ? '-'.$empresa['dv'] : '' }}</td>
                            <td>
                                <span class="status-pill status-pill--{{ str_replace('_', '-', $empresa['estado']) }}">
                                    {{ $estadoLabels[$empresa['estado']] }}
                                </span>
                            </td>
                            <td class="data-table__meta">{{ $empresa['vencimiento'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="data-table__empty" id="empresasEmpty" @unless (count($empresas) === 0) hidden @endunless>
                @if (count($empresas) === 0)
                    Todavía no hay empresas registradas.
                @else
                    No hay empresas que coincidan con la búsqueda.
                @endif
            </p>
        </div>
    </div>

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

                <label class="admin-form-label" for="activarPlanSelect" style="margin-top:14px;">Plan que pagó</label>
                <select id="activarPlanSelect" class="admin-form-select">
                    <option value="mensual">Mensual (1 mes)</option>
                    <option value="trimestral">Trimestral (3 meses)</option>
                    <option value="semestral">Semestral (6 meses)</option>
                    <option value="anual">Anual (12 meses)</option>
                </select>

                <div class="admin-form-row" style="margin-top:10px;">
                    <div>
                        <label class="admin-form-label" for="activarMontoInput">Monto pagado (opcional)</label>
                        <input type="text" inputmode="numeric" id="activarMontoInput" class="admin-form-input" placeholder="0">
                    </div>
                    <div>
                        <label class="admin-form-label" for="activarMetodoInput">Método (opcional)</label>
                        <input type="text" id="activarMetodoInput" class="admin-form-input" placeholder="Nequi, transferencia...">
                    </div>
                </div>

                <div class="slide-over__actions" style="margin-top:14px;">
                    <button type="button" class="slide-over__btn slide-over__btn--activar" id="slideOverActivar">
                        Activar suscripción
                    </button>
                    <button type="button" class="slide-over__btn slide-over__btn--suspender" id="slideOverSuspender">
                        Suspender empresa
                    </button>
                </div>
            </section>

            {{-- Interruptores independientes -Nómina no depende de tener
                 Facturación electrónica prendida, ni al revés. Por ahora
                 solo se guardan -no ocultan ni bloquean nada del lado
                 cliente todavía (fase aparte, pendiente). --}}
            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Módulos</h3>
                <div id="slideOverModulos">
                    <div class="module-toggle-row">
                        <span class="module-toggle-row__name">Facturación electrónica</span>
                        <label class="module-toggle">
                            <input type="checkbox" id="moduloFacturacionCheck" aria-label="Facturación electrónica">
                            <span class="module-toggle__track"></span>
                        </label>
                    </div>
                </div>
            </section>
        </div>
    </aside>

    @push('styles')
        <link rel="stylesheet" href="{{ asset_v('assets/css/admin/empresas.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/admin/empresas.js') }}" defer></script>
    @endpush

</x-admin-layout>
