<x-cliente-layout title="Nómina">

    {{-- Nómina Electrónica -deliberadamente SIN cálculo legal (nada de
         salud, pensión, SMMLV ni retención). El dueño decide cuánto le
         paga a cada empleado; esto solo deja constancia de ese pago con
         estructura de Nómina Electrónica (CUNE simulado, misma honestidad
         que el resto de Facturación sobre que Factus aún no está
         conectado de verdad). Ver App\Http\Controllers\Cliente\NominaController. --}}

    <div class="cliente-page-header cliente-reveal cliente-reveal-1"
         style="display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div>
            <p class="cliente-page-header__eyebrow">Tu negocio</p>
            <h1 class="cliente-page-header__title">Empleados y pagos</h1>
            <p class="cliente-page-header__date">
                {{ $stats['empleadosActivos'] }} {{ $stats['empleadosActivos'] === 1 ? 'empleado activo' : 'empleados activos' }}
                · {{ $stats['documentosCount'] }}
                {{ $tieneFacturacion
                    ? ($stats['documentosCount'] === 1 ? 'documento emitido' : 'documentos emitidos')
                    : ($stats['documentosCount'] === 1 ? 'pago registrado' : 'pagos registrados') }}
            </p>
        </div>
    </div>

    <div class="facturacion-main-tabs cliente-reveal cliente-reveal-1" role="tablist" aria-label="Sección de nómina">
        <button type="button" class="facturacion-main-tab is-active" id="tabBtnEmpleados" role="tab" aria-selected="true">Empleados</button>
        <button type="button" class="facturacion-main-tab" id="tabBtnDocumentos" role="tab" aria-selected="false">{{ $tieneFacturacion ? 'Nómina electrónica' : 'Pagar nómina' }}</button>
    </div>

    {{-- ================================================================
         PESTAÑA: EMPLEADOS
         ================================================================ --}}
    <div id="tabPanelEmpleados">
        <div style="display:flex; justify-content:flex-end; margin-bottom:18px;">
            <button type="button" class="cliente-btn-primary" id="nuevoEmpleadoBtn">+ Nuevo empleado</button>
        </div>

        <div class="stat-grid stat-grid--facturacion-gastos cliente-reveal cliente-reveal-2">
            <div class="stat-card">
                <p class="stat-card__label">Empleados activos</p>
                <p class="stat-card__value" data-count="{{ $stats['empleadosActivos'] }}">{{ $stats['empleadosActivos'] }}</p>
            </div>
            <div class="stat-card">
                <p class="stat-card__label">Total pagado (histórico)</p>
                <p class="stat-card__value" data-count="{{ $stats['totalPagado'] }}" data-prefix="$" data-format="money">
                    ${{ number_format($stats['totalPagado'], 0, ',', '.') }}
                </p>
            </div>
            <div class="stat-card">
                <p class="stat-card__label">{{ $tieneFacturacion ? 'Documentos emitidos' : 'Pagos registrados' }}</p>
                <p class="stat-card__value" data-count="{{ $stats['documentosCount'] }}">{{ $stats['documentosCount'] }}</p>
            </div>
        </div>

        <div class="panel cliente-reveal cliente-reveal-3">
            <div class="data-table-wrap">
                <table class="data-table" id="empleadosTable">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Documento</th>
                            <th>Cargo</th>
                            <th>Salario por día</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($empleados as $empleado)
                            <tr class="data-table__row" data-empleado-id="{{ $empleado['id'] }}" tabindex="0">
                                <td class="data-table__title">{{ $empleado['nombreCompleto'] }}</td>
                                <td class="data-table__meta">{{ $empleado['tipoDocumento'] }} {{ $empleado['numeroDocumento'] }}</td>
                                <td class="data-table__meta">{{ $empleado['cargo'] ?? '—' }}</td>
                                <td class="data-table__meta">
                                    {{ $empleado['salario'] !== null ? '$'.number_format($empleado['salario'], 0, ',', '.') : '—' }}
                                </td>
                                <td>
                                    <span class="status-pill {{ $empleado['activo'] ? 'status-pill--pagada' : 'status-pill--error' }}">
                                        {{ $empleado['activo'] ? 'Activo' : 'Retirado' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="data-table__empty" id="empleadosEmpty" @unless ($empleados->isEmpty()) hidden @endunless>
                    Todavía no tienes empleados registrados.
                </p>
            </div>

            <div class="data-table__pagination" id="empleadosPagination">
                <button type="button" class="cliente-btn-ghost" id="empleadosPrevPage">← Anterior</button>
                <span class="data-table__pagination-info" id="empleadosPageInfo">Página 1 de 1</span>
                <button type="button" class="cliente-btn-ghost" id="empleadosNextPage">Siguiente →</button>
            </div>
        </div>
    </div>

    {{-- ================================================================
         PESTAÑA: NÓMINA ELECTRÓNICA (documentos)
         ================================================================ --}}
    <div id="tabPanelDocumentos" hidden>
        <div style="display:flex; justify-content:flex-end; margin-bottom:18px;">
            <button type="button" class="cliente-btn-primary" id="pagarNominaBtn">+ Pagar nómina</button>
        </div>

        <div class="stat-grid stat-grid--facturacion-gastos">
            <div class="stat-card">
                <p class="stat-card__label">Total pagado</p>
                <p class="stat-card__value" data-count="{{ $stats['totalPagado'] }}" data-prefix="$" data-format="money">
                    ${{ number_format($stats['totalPagado'], 0, ',', '.') }}
                </p>
            </div>
            <div class="stat-card">
                <p class="stat-card__label">{{ $tieneFacturacion ? 'Documentos emitidos' : 'Pagos registrados' }}</p>
                <p class="stat-card__value" data-count="{{ $stats['documentosCount'] }}">{{ $stats['documentosCount'] }}</p>
            </div>
            <div class="stat-card">
                <p class="stat-card__label">Empleados activos</p>
                <p class="stat-card__value" data-count="{{ $stats['empleadosActivos'] }}">{{ $stats['empleadosActivos'] }}</p>
            </div>
        </div>

        <div class="panel">
            <div class="cliente-toolbar">
                <select id="nominaDocMesFilter" class="cliente-toolbar__select">
                    <option value="">Todos los meses</option>
                </select>
            </div>

            <div class="data-table-wrap">
                <table class="data-table" id="nominaDocumentosTable">
                    <thead>
                        <tr>
                            <th>{{ $tieneFacturacion ? 'Documento' : 'Comprobante' }}</th>
                            <th>Empleado</th>
                            <th>Período</th>
                            <th>Monto pagado</th>
                            <th>Estado</th>
                            <th>Fecha de pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documentos as $doc)
                            <tr class="data-table__row" data-doc-nomina-id="{{ $doc['id'] }}" data-mes-key="{{ $doc['mesKey'] }}" tabindex="0">
                                <td>
                                    <div class="data-table__title">{{ $doc['numero'] }}</div>
                                    @if ($tieneFacturacion)
                                        <div class="data-table__meta cufe-snippet" title="{{ $doc['cune'] }}">
                                            {{ substr($doc['cune'], 0, 18) }}&hellip;
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="data-table__title">{{ $doc['empleado']['nombre'] }}</div>
                                    <div class="data-table__meta">{{ $doc['empleado']['numDoc'] }}</div>
                                </td>
                                <td class="data-table__meta">{{ $doc['periodo'] }}</td>
                                <td class="data-table__title">${{ number_format($doc['montoPagado'], 0, ',', '.') }}</td>
                                <td>
                                    <span class="status-pill {{ $doc['estado'] === 'emitida' ? 'status-pill--pagada' : 'status-pill--error' }}">
                                        {{ ucfirst($doc['estado']) }}
                                    </span>
                                </td>
                                <td class="data-table__meta">{{ $doc['fechaPago'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="data-table__empty" id="nominaDocumentosEmpty" @unless ($documentos->isEmpty()) hidden @endunless>
                    {{ $tieneFacturacion ? 'Todavía no hay documentos de nómina emitidos.' : 'Todavía no has registrado ningún pago de nómina.' }}
                </p>
            </div>

            <div class="data-table__pagination" id="nominaDocPagination">
                <button type="button" class="cliente-btn-ghost" id="nominaDocPrevPage">← Anterior</button>
                <span class="data-table__pagination-info" id="nominaDocPageInfo">Página 1 de 1</span>
                <button type="button" class="cliente-btn-ghost" id="nominaDocNextPage">Siguiente →</button>
            </div>
        </div>
    </div>

    <script id="empleadosData" type="application/json">{!! json_encode($empleados) !!}</script>
    <script id="nominaDocumentosData" type="application/json">{!! json_encode($documentos) !!}</script>

    {{-- ================================================================
         PANEL LATERAL — detalle de empleado
         ================================================================ --}}
    <div class="slide-over-overlay" id="empleadoSlideOverOverlay"></div>

    <aside class="slide-over" id="empleadoSlideOver" aria-hidden="true">
        <div class="slide-over__header">
            <div>
                <h2 class="slide-over__title" id="empleadoSlideOverNombre">—</h2>
                <span class="status-pill" id="empleadoSlideOverEstado">—</span>
            </div>
            <button type="button" class="slide-over__close" id="empleadoSlideOverClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="slide-over__body">
            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Datos del empleado</h3>
                <div class="slide-over__field"><span>Documento</span><strong id="empleadoSlideOverDocumento">—</strong></div>
                <div class="slide-over__field"><span>Cargo</span><strong id="empleadoSlideOverCargo">—</strong></div>
                <div class="slide-over__field"><span>Salario por día</span><strong id="empleadoSlideOverSalario">—</strong></div>
                <div class="slide-over__field"><span>Fecha de retiro</span><strong id="empleadoSlideOverRetiro">—</strong></div>
            </section>

            <button type="button" class="cliente-btn-ghost" id="empleadoSlideOverEditarBtn" style="width:100%; margin-bottom:6px;">
                Editar empleado
            </button>
            <button type="button" class="cliente-btn-ghost cliente-btn-ghost--peligro" id="empleadoSlideOverEliminarBtn" style="width:100%;">
                Eliminar empleado
            </button>
        </div>
    </aside>

    {{-- ================================================================
         PANEL LATERAL — detalle de documento de nómina
         ================================================================ --}}
    <div class="slide-over-overlay" id="docNominaSlideOverOverlay"></div>

    <aside class="slide-over" id="docNominaSlideOver" aria-hidden="true">
        <div class="slide-over__header">
            <div>
                <h2 class="slide-over__title" id="docNominaSlideOverNumero">—</h2>
                <span class="status-pill" id="docNominaSlideOverEstado">—</span>
            </div>
            <button type="button" class="slide-over__close" id="docNominaSlideOverClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="slide-over__body">
            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Empleado</h3>
                <div class="slide-over__field"><span>Nombre</span><strong id="docNominaSlideOverEmpleado">—</strong></div>
                <div class="slide-over__field"><span>Documento</span><strong id="docNominaSlideOverDocumentoEmpleado">—</strong></div>
            </section>

            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Pago</h3>
                <div class="slide-over__field"><span>Período</span><strong id="docNominaSlideOverPeriodo">—</strong></div>
                <div class="slide-over__field"><span>Fecha de pago</span><strong id="docNominaSlideOverFechaPago">—</strong></div>
                <div class="slide-over__field"><span>Método de pago</span><strong id="docNominaSlideOverMetodo">—</strong></div>
                <div class="slide-over__field" style="margin-top:12px; padding-top:12px; border-top:1px solid var(--color-border-06);">
                    <span>Monto pagado</span>
                    <strong id="docNominaSlideOverMonto">—</strong>
                </div>
            </section>

            @if ($tieneFacturacion)
                <section class="slide-over__section">
                    <h3 class="slide-over__section-title">Verificación DIAN</h3>
                    <p class="slide-over__label" style="font-size:11px; color:var(--color-mist); margin-bottom:6px;">CUNE</p>
                    <div class="cufe-block" id="docNominaSlideOverCune"></div>
                </section>
            @endif

            <section class="slide-over__section">
                <a href="#" class="cliente-btn-primary" id="docNominaDescargarBtn" target="_blank"
                   style="width:100%; text-align:center; display:block; box-sizing:border-box; margin-bottom:10px;">
                    Descargar comprobante (PDF)
                </a>
            </section>

            <section class="slide-over__section" id="docNominaAnularSection">
                <button type="button" class="cliente-btn-ghost cliente-btn-ghost--peligro" id="docNominaAnularBtn"
                        style="width:100%;">
                    Cancelar Pago
                </button>
            </section>
        </div>
    </aside>

    {{-- ================================================================
         MODAL — Nuevo/Editar empleado
         ================================================================ --}}
    <div class="modal-overlay" id="empleadoModalOverlay"></div>

    <div class="modal" id="empleadoModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="empleadoModalTitle">
        <div class="modal__header">
            <h2 class="modal__title" id="empleadoModalTitle">Nuevo empleado</h2>
            <button type="button" class="modal__close" id="empleadoModalClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="modal__body">
            <div class="proveedor-form-row">
                <div>
                    <label for="empNombres" class="cliente-label">Nombres</label>
                    <input type="text" id="empNombres" class="cliente-input" placeholder="Juan Carlos">
                </div>
                <div>
                    <label for="empApellidos" class="cliente-label">Apellidos</label>
                    <input type="text" id="empApellidos" class="cliente-input" placeholder="Pérez Gómez">
                </div>
            </div>

            <div class="proveedor-form-row" style="margin-top:14px;">
                <div>
                    <label for="empTipoDoc" class="cliente-label">Tipo de documento</label>
                    <select id="empTipoDoc" class="cliente-toolbar__select" style="width:100%;">
                        <option value="CC">CC - Cédula</option>
                        <option value="CE">CE - Extranjería</option>
                        <option value="PP">PP - Pasaporte</option>
                    </select>
                </div>
                <div>
                    <label for="empNumDoc" class="cliente-label">Número de documento</label>
                    <input type="text" id="empNumDoc" class="cliente-input" placeholder="1020304050">
                </div>
            </div>

            <label for="empCargo" class="cliente-label" style="margin-top:14px; display:block;">Cargo</label>
            <input type="text" id="empCargo" class="cliente-input" placeholder="Vendedor" style="margin-bottom:14px;">

            <label for="empSalario" class="cliente-label">Salario por día</label>
            <input type="text" inputmode="numeric" id="empSalario" class="cliente-input" placeholder="43.333">
            <p class="slide-over__label" style="font-size:11px; color:var(--color-mist); margin-top:6px;">
                Al pagar nómina, se multiplica por los días trabajados para sugerir el monto — sigue siendo editable, tú decides cuánto pagarle finalmente.
            </p>
        </div>

        <div class="modal__footer">
            <button type="button" class="cliente-btn-primary" id="empGuardarBtn" disabled>Guardar empleado</button>
        </div>
    </div>

    {{-- ================================================================
         MODAL — Pagar nómina (varios empleados a la vez)
         ================================================================ --}}
    <div class="modal-overlay" id="pagarNominaOverlay"></div>

    <div class="modal" id="pagarNominaModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="pagarNominaModalTitle">
        <div class="modal__header">
            <h2 class="modal__title" id="pagarNominaModalTitle">Pagar nómina</h2>
            <button type="button" class="modal__close" id="pagarNominaClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="modal__body">
            @php
                $mesesNombres = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            @endphp
            <div class="proveedor-form-row">
                <div>
                    <label for="pagoPeriodoMes" class="cliente-label">Período</label>
                    <div class="pago-periodo-row">
                        <select id="pagoPeriodoMes" class="cliente-toolbar__select">
                            @foreach ($mesesNombres as $i => $mes)
                                <option value="{{ $mes }}" @selected($i + 1 === (int) now()->format('n'))>{{ $mes }}</option>
                            @endforeach
                        </select>
                        <select id="pagoPeriodoAnio" class="cliente-toolbar__select">
                            @for ($anio = now()->year - 1; $anio <= now()->year + 1; $anio++)
                                <option value="{{ $anio }}" @selected($anio === now()->year)>{{ $anio }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div>
                    <label for="pagoFecha" class="cliente-label">Fecha de pago</label>
                    <input type="date" id="pagoFecha" class="cliente-input" max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}">
                </div>
            </div>

            <label for="pagoEmpleadoSelect" class="cliente-label">Empleado</label>
            <select id="pagoEmpleadoSelect" class="cliente-toolbar__select" style="width:100%; margin-bottom:18px;">
                <option value="" disabled selected>Selecciona un empleado para agregarlo</option>
                @forelse ($empleados->where('activo', true) as $empleado)
                    <option value="{{ $empleado['id'] }}"
                            data-nombre="{{ $empleado['nombreCompleto'] }}"
                            data-doc="{{ $empleado['tipoDocumento'] }} {{ $empleado['numeroDocumento'] }}"
                            data-salario-dia="{{ $empleado['salario'] ?? 0 }}">
                        {{ $empleado['nombreCompleto'] }} — {{ $empleado['tipoDocumento'] }} {{ $empleado['numeroDocumento'] }}
                    </option>
                @empty
                    <option value="" disabled>No hay empleados activos</option>
                @endforelse
            </select>

            <div class="pago-empleados-head" id="pagoEmpleadosHead" hidden>
                <span>Empleado</span>
                <span>Días trabajados</span>
                <span>Monto a pagar</span>
            </div>
            <div class="ventas-pendientes-list" id="pagoEmpleadosList"></div>
            <p class="data-table__empty" id="pagoEmpleadosVacio" style="margin:0; padding:16px 0; text-align:center;">
                Agrega empleados desde el select de arriba para pagarles.
            </p>
            <p class="slide-over__label" style="font-size:11px; color:var(--color-mist); margin-top:8px;">
                Los días trabajados solo sugieren el monto (salario por día × días) — puedes escribir el monto directamente si prefieres.
            </p>

            <div class="factura-total-row" id="pagoTotalRow">
                <span>Total a pagar</span>
                <strong id="pagoTotalSeleccionado">$0</strong>
            </div>

            <label class="cliente-label" style="margin-top:14px;">Método de pago</label>
            <div class="venta-payment-toggle">
                <button type="button" class="venta-payment-btn is-active" id="pagoNominaBtnEfectivo">Efectivo</button>
                <button type="button" class="venta-payment-btn" id="pagoNominaBtnDigital">Digital</button>
            </div>

            <label class="cliente-label">¿Salió de la caja de hoy o de lo que el negocio ya tenía guardado?</label>
            <div class="venta-payment-toggle">
                <button type="button" class="venta-payment-btn is-active" id="pagoNominaBtnOrigenHoy">De caja</button>
                <button type="button" class="venta-payment-btn" id="pagoNominaBtnOrigenExterno">Fuera de caja</button>
            </div>
            <p class="compra-metodo-hint" id="pagoNominaMetodoHint">Sacaste la plata física de la caja del negocio -se descuenta del cierre de caja de hoy.</p>
        </div>

        <div class="modal__footer">
            <button type="button" class="cliente-btn-ghost" id="pagarNominaCancelar">Cancelar</button>
            <button type="button" class="cliente-btn-primary" id="pagarNominaEmitir" disabled>{{ $tieneFacturacion ? 'Emitir a la DIAN' : 'Registrar pago' }}</button>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/facturacion.css') }}">
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/proveedores.css') }}">
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/nomina.css') }}">
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/nueva-venta-modal.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/cliente/nomina.js') }}" defer></script>
    @endpush

</x-cliente-layout>
