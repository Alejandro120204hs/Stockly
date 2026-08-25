<x-cliente-layout title="Caja">

    {{-- Caja — SOLO FRONTEND, datos mock. Sigue la regla de negocio real:
         apertura define una base de efectivo (`caja_apertura`); el cierre
         (`caja_cierre`) calcula base + ventas efectivo + ventas digitales
         confirmadas − gastos en efectivo = total esperado, se compara
         contra el conteo físico real, y se guarda la diferencia
         (faltante/sobrante). Los montos de "hoy" (ventas/gastos) son fijos
         acá porque no hay backend todavía -en producción vendrían de
         ventas/gastos reales del día. --}}
    @php
        $ventasEfectivoHoy = 420000;
        $ventasDigitalHoy = 680000;
        $gastosEfectivoHoy = 45000;

        $cierres = [
            ['id' => 6, 'fecha' => '24 ago 2026', 'abrioPor' => 'Laura Ramírez', 'baseInicial' => 150000, 'ventasEfectivo' => 395000, 'ventasDigital' => 610000, 'gastosEfectivo' => 65000, 'totalEsperado' => 480000, 'conteoReal' => 480000, 'horaCierre' => '8:05 p.m.'],
            ['id' => 5, 'fecha' => '23 ago 2026', 'abrioPor' => 'Laura Ramírez', 'baseInicial' => 150000, 'ventasEfectivo' => 402000, 'ventasDigital' => 590000, 'gastosEfectivo' => 42000, 'totalEsperado' => 510000, 'conteoReal' => 505000, 'horaCierre' => '7:52 p.m.'],
            ['id' => 4, 'fecha' => '22 ago 2026', 'abrioPor' => 'Laura Ramírez', 'baseInicial' => 150000, 'ventasEfectivo' => 358000, 'ventasDigital' => 720000, 'gastosEfectivo' => 48000, 'totalEsperado' => 460000, 'conteoReal' => 465000, 'horaCierre' => '8:10 p.m.'],
            ['id' => 3, 'fecha' => '21 ago 2026', 'abrioPor' => 'Laura Ramírez', 'baseInicial' => 150000, 'ventasEfectivo' => 390000, 'ventasDigital' => 655000, 'gastosEfectivo' => 45000, 'totalEsperado' => 495000, 'conteoReal' => 495000, 'horaCierre' => '7:48 p.m.'],
            ['id' => 2, 'fecha' => '20 ago 2026', 'abrioPor' => 'Laura Ramírez', 'baseInicial' => 150000, 'ventasEfectivo' => 425000, 'ventasDigital' => 580000, 'gastosEfectivo' => 45000, 'totalEsperado' => 530000, 'conteoReal' => 520000, 'horaCierre' => '8:20 p.m.'],
            ['id' => 1, 'fecha' => '19 ago 2026', 'abrioPor' => 'Laura Ramírez', 'baseInicial' => 150000, 'ventasEfectivo' => 368000, 'ventasDigital' => 602000, 'gastosEfectivo' => 48000, 'totalEsperado' => 470000, 'conteoReal' => 478000, 'horaCierre' => '7:55 p.m.'],
        ];

        foreach ($cierres as &$cierre) {
            $cierre['totalGeneral'] = $cierre['totalEsperado'] + $cierre['ventasDigital'];
            $cierre['diferencia'] = $cierre['conteoReal'] - $cierre['totalEsperado'];
        }
        unset($cierre);

        $diasSinCuadrar = collect($cierres)->where('diferencia', '!=', 0)->count();
    @endphp

    <div class="cliente-page-header cliente-reveal cliente-reveal-1">
        <div>
            <p class="cliente-page-header__eyebrow">Tu negocio</p>
            <h1 class="cliente-page-header__title">Caja</h1>
            <p class="cliente-page-header__date">{{ count($cierres) }} cierres registrados</p>
        </div>
    </div>

    <!-- ==========================================================
         STAT CARDS
         ========================================================== -->
    <section class="stat-grid cliente-reveal cliente-reveal-2">
        <div class="stat-card stat-card--mist" id="cajaEstadoCard">
            <div class="stat-card__icon" id="cajaEstadoIcono">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2.5" y="6" width="19" height="12" rx="2.5"/>
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M6 9v.01M18 15v.01"/>
                </svg>
            </div>
            <span class="stat-card__value stat-card__value--status" id="cajaEstadoValor">Cerrada</span>
            <span class="stat-card__label">Estado de hoy</span>
            <span class="stat-card__meta" id="cajaEstadoMeta">Todavía no la has abierto hoy</span>
        </div>

        <div class="stat-card stat-card--sage">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 7V6a6 6 0 0 1 12 0v1"/>
                    <path d="M4 7h16l-1.5 13.5a2 2 0 0 1-2 1.5H7.5a2 2 0 0 1-2-1.5L4 7Z"/>
                </svg>
            </div>
            <span class="stat-card__value" data-count="{{ $ventasEfectivoHoy + $ventasDigitalHoy }}" data-prefix="$">$0</span>
            <span class="stat-card__label">Ventas de hoy</span>
            <span class="stat-card__meta">Efectivo + digital</span>
        </div>

        <div class="stat-card stat-card--sand">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 7a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v3"/>
                    <path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2H5a2 2 0 0 1-2-2Z"/>
                    <path d="M17 14h.01"/>
                </svg>
            </div>
            <span class="stat-card__value" data-count="{{ $gastosEfectivoHoy }}" data-prefix="$">$0</span>
            <span class="stat-card__label">Gastos en efectivo de hoy</span>
            <span class="stat-card__meta">Resta del efectivo esperado</span>
        </div>

        <div class="stat-card stat-card--mist">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 21V6l8-3 8 3v15"/>
                    <path d="M4 21h16"/>
                    <path d="M9 9h1M14 9h1M9 13h1M14 13h1M9 21v-4h6v4"/>
                </svg>
            </div>
            <span class="stat-card__value" data-count="{{ $diasSinCuadrar }}">0</span>
            <span class="stat-card__label">Cierres sin cuadrar (últimos 6)</span>
            <span class="stat-card__meta">Con faltante o sobrante</span>
        </div>
    </section>

    <!-- ==========================================================
         CAJA: recibo en vivo (abierta) o llamado a abrir (cerrada)
         ========================================================== -->
    <div class="panel cliente-reveal cliente-reveal-3">
        <!-- ---------- Estado CERRADA: llamado a abrir caja ---------- -->
        <div class="caja-hero" id="cajaHeroAbrir">
            <div class="caja-hero__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2.5" y="6" width="19" height="12" rx="2.5"/>
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M6 9v.01M18 15v.01"/>
                </svg>
            </div>
            <h2 class="caja-hero__title">Todavía no has abierto caja hoy</h2>
            <p class="caja-hero__subtitle">Define la base de efectivo con la que arrancas el día para empezar a registrar ventas.</p>

            <div class="caja-hero__form">
                <label for="cajaBaseInicial" class="cliente-label">Base de efectivo inicial</label>
                <input type="number" id="cajaBaseInicial" class="cliente-input" placeholder="Ej: 150000" min="0">
                <button type="button" class="cliente-btn-primary" id="abrirCajaBtn">Abrir caja</button>
            </div>
        </div>

        <!-- ---------- Estado ABIERTA: recibo en vivo ---------- -->
        <div class="caja-abierta" id="cajaAbiertaPanel" hidden>
            <div class="caja-abierta__header">
                <div>
                    <span class="status-pill status-pill--facturada">Caja abierta</span>
                    <p class="caja-abierta__meta">Abierta por Laura Ramírez · desde <span id="cajaHoraApertura">—</span></p>
                </div>
                <button type="button" class="cliente-btn-primary" id="cerrarCajaBtn">Cerrar caja</button>
            </div>

            <div class="caja-recibo">
                <div class="caja-recibo__row">
                    <span>Base inicial</span>
                    <strong id="reciboBase">$0</strong>
                </div>
                <div class="caja-recibo__row caja-recibo__row--suma">
                    <span>+ Ventas en efectivo (hoy)</span>
                    <strong id="reciboVentasEfectivo">$0</strong>
                </div>
                <div class="caja-recibo__row caja-recibo__row--resta">
                    <span>− Gastos en efectivo (hoy)</span>
                    <strong id="reciboGastos">$0</strong>
                </div>
                <div class="caja-recibo__divider"></div>
                <div class="caja-recibo__row caja-recibo__row--total">
                    <span>= Total esperado en caja</span>
                    <strong id="reciboTotalEsperado">$0</strong>
                </div>
                <div class="caja-recibo__row caja-recibo__row--secundario">
                    <span>+ Ventas digitales confirmadas (hoy)</span>
                    <strong id="reciboVentasDigital">$0</strong>
                </div>
                <div class="caja-recibo__row caja-recibo__row--secundario">
                    <span>Total general del día</span>
                    <strong id="reciboTotalGeneral">$0</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================================
         GRÁFICO: diferencia de los últimos cierres
         ========================================================== -->
    <div class="panel cliente-reveal cliente-reveal-4">
        <div class="panel__header">
            <div>
                <h2 class="panel__title">Diferencia en los últimos cierres</h2>
                <span class="panel__subtitle">Sobrante arriba de la línea, faltante abajo</span>
            </div>
        </div>

        <div class="caja-diff-chart" id="cajaDiffChart"></div>
    </div>

    <!-- ==========================================================
         HISTORIAL DE CIERRES
         ========================================================== -->
    <div class="panel cliente-reveal cliente-reveal-5">
        <div class="panel__header">
            <h2 class="panel__title">Historial de cierres</h2>
        </div>

        <div class="data-table-wrap">
            <table class="data-table" id="cajaTable">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Base inicial</th>
                        <th>Total esperado</th>
                        <th>Conteo real</th>
                        <th>Diferencia</th>
                    </tr>
                </thead>
                <tbody id="cajaTableBody">
                    @foreach ($cierres as $cierre)
                        <tr class="data-table__row" data-cierre-id="{{ $cierre['id'] }}" tabindex="0">
                            <td>
                                <div class="data-table__title">{{ $cierre['fecha'] }}</div>
                                <div class="data-table__meta">Cerrada {{ $cierre['horaCierre'] }}</div>
                            </td>
                            <td class="data-table__meta">${{ number_format($cierre['baseInicial'], 0, ',', '.') }}</td>
                            <td class="data-table__title">${{ number_format($cierre['totalEsperado'], 0, ',', '.') }}</td>
                            <td class="data-table__meta">${{ number_format($cierre['conteoReal'], 0, ',', '.') }}</td>
                            <td>
                                @if ($cierre['diferencia'] > 0)
                                    <span class="status-pill status-pill--sobrante">+${{ number_format($cierre['diferencia'], 0, ',', '.') }}</span>
                                @elseif ($cierre['diferencia'] < 0)
                                    <span class="status-pill status-pill--faltante">−${{ number_format(abs($cierre['diferencia']), 0, ',', '.') }}</span>
                                @else
                                    <span class="status-pill status-pill--sin-facturar">Exacto</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script id="cajaCierresData" type="application/json">{!! json_encode($cierres) !!}</script>
    <script id="cajaHoyData" type="application/json">{!! json_encode(['ventasEfectivo' => $ventasEfectivoHoy, 'ventasDigital' => $ventasDigitalHoy, 'gastosEfectivo' => $gastosEfectivoHoy]) !!}</script>

    {{-- ==================================================================
         PANEL LATERAL — detalle de un cierre
         ================================================================== --}}
    <div class="slide-over-overlay" id="cierreSlideOverOverlay"></div>

    <aside class="slide-over" id="cierreSlideOver" aria-hidden="true">
        <div class="slide-over__header">
            <div>
                <h2 class="slide-over__title" id="cierreSlideOverTitulo">—</h2>
                <span class="status-pill" id="cierreSlideOverDiferenciaPill">—</span>
            </div>
            <button type="button" class="slide-over__close" id="cierreSlideOverClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="slide-over__body">
            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Movimientos del día</h3>
                <div class="slide-over__field"><span>Base inicial</span><strong id="cierreSlideOverBase">—</strong></div>
                <div class="slide-over__field"><span>Ventas en efectivo</span><strong id="cierreSlideOverVentasEfectivo">—</strong></div>
                <div class="slide-over__field"><span>Ventas digitales</span><strong id="cierreSlideOverVentasDigital">—</strong></div>
                <div class="slide-over__field"><span>Gastos en efectivo</span><strong id="cierreSlideOverGastos">—</strong></div>
            </section>

            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Cierre</h3>
                <div class="slide-over__field"><span>Total esperado (efectivo)</span><strong id="cierreSlideOverEsperado">—</strong></div>
                <div class="slide-over__field"><span>Total general del día</span><strong id="cierreSlideOverGeneral">—</strong></div>
                <div class="slide-over__field"><span>Conteo físico real</span><strong id="cierreSlideOverConteo">—</strong></div>
                <div class="slide-over__field"><span>Diferencia</span><strong id="cierreSlideOverDiferencia">—</strong></div>
                <div class="slide-over__field"><span>Cerrada por</span><strong id="cierreSlideOverAbrioPor">—</strong></div>
            </section>
        </div>
    </aside>

    {{-- ==================================================================
         MODAL — Cerrar caja
         ================================================================== --}}
    <div class="modal-overlay" id="cerrarCajaOverlay"></div>

    <div class="modal" id="cerrarCajaModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="cerrarCajaTitle" style="width:min(420px, calc(100% - 32px));">
        <div class="modal__header">
            <h2 class="modal__title" id="cerrarCajaTitle">Cerrar caja</h2>
            <button type="button" class="modal__close" id="cerrarCajaClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="modal__body">
            <p class="caja-modal-esperado">Total esperado en efectivo: <strong id="cerrarModalEsperado">$0</strong></p>

            <label for="conteoFisicoInput" class="cliente-label">Conteo físico real</label>
            <input type="number" id="conteoFisicoInput" class="cliente-input" placeholder="Cuenta el efectivo en caja y escribe el total" min="0">

            <div class="caja-modal-diferencia" id="cajaModalDiferencia" hidden>
                <span id="cajaModalDiferenciaTexto">—</span>
            </div>
        </div>

        <div class="modal__footer">
            <button type="button" class="cliente-btn-primary" id="confirmarCierreBtn" disabled>Confirmar cierre</button>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/cliente/caja.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset('assets/js/cliente/caja.js') }}" defer></script>
    @endpush

</x-cliente-layout>
