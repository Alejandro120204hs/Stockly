<x-cliente-layout title="Caja">

    {{-- Caja — datos reales (App\Http\Controllers\Cliente\CajaController).
         Una caja es una SESIÓN (abrir -> cerrar), no un día calendario -si
         el negocio cierra pasada la medianoche, esa sesión completa sigue
         siendo la misma caja. Si se cierra por error, se puede reabrir
         mientras siga siendo la más reciente ($ultimaCajaId): el mismo
         reporte sigue vivo, solo se limpia el conteo físico/diferencia. --}}
    <div class="cliente-page-header cliente-reveal cliente-reveal-1">
        <div>
            <p class="cliente-page-header__eyebrow">Tu negocio</p>
            <h1 class="cliente-page-header__title">Control de caja</h1>
            <p class="cliente-page-header__date">{{ count($cierres) }} cierres registrados</p>
        </div>
    </div>

    <!-- ==========================================================
         STAT CARDS
         ========================================================== -->
    <section class="stat-grid cliente-reveal cliente-reveal-2">
        <div class="stat-card {{ $cajaAbierta ? 'stat-card--sage' : 'stat-card--mist' }}" id="cajaEstadoCard">
            <div class="stat-card__icon" id="cajaEstadoIcono">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="2.5" y="6" width="19" height="12" rx="2.5"/>
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M6 9v.01M18 15v.01"/>
                </svg>
            </div>
            <span class="stat-card__value stat-card__value--status" id="cajaEstadoValor">{{ $cajaAbierta ? 'Abierta' : 'Cerrada' }}</span>
            <span class="stat-card__label">Estado actual</span>
            <span class="stat-card__meta" id="cajaEstadoMeta">
                @if ($cajaAbierta)
                    Base ${{ number_format($cajaAbierta['baseInicial'], 0, ',', '.') }} · {{ $cajaAbierta['horaApertura'] }}
                @else
                    Todavía no la has abierto
                @endif
            </span>
        </div>

        <div class="stat-card stat-card--sage">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 7V6a6 6 0 0 1 12 0v1"/>
                    <path d="M4 7h16l-1.5 13.5a2 2 0 0 1-2 1.5H7.5a2 2 0 0 1-2-1.5L4 7Z"/>
                </svg>
            </div>
            <span class="stat-card__value" id="cajaStatVentas" data-count="{{ $cajaAbierta ? $cajaAbierta['ventasEfectivo'] + $cajaAbierta['ventasDigital'] : 0 }}" data-prefix="$">$0</span>
            <span class="stat-card__label">Ventas de la caja actual</span>
            <span class="stat-card__meta">Efectivo + digital</span>
        </div>

        <div class="stat-card stat-card--sand">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 7a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v3"/>
                    <path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2H5a2 2 0 0 1-2-2Z"/>
                    <path d="M17 14h.01"/>
                </svg>
            </div>
            <span class="stat-card__value" id="cajaStatGastos" data-count="{{ $cajaAbierta ? $cajaAbierta['gastosEfectivo'] + $cajaAbierta['gastosDigital'] : 0 }}" data-prefix="$">$0</span>
            <span class="stat-card__label">Gastos (efectivo + digital)</span>
            <span class="stat-card__meta">Resta de lo esperado</span>
        </div>

        <div class="stat-card stat-card--mist">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 21V6l8-3 8 3v15"/>
                    <path d="M4 21h16"/>
                    <path d="M9 9h1M14 9h1M9 13h1M14 13h1M9 21v-4h6v4"/>
                </svg>
            </div>
            <span class="stat-card__value" id="cajaStatSinCuadrar" data-count="{{ $diasSinCuadrar }}">0</span>
            <span class="stat-card__label">Cierres sin cuadrar (últimos 6)</span>
            <span class="stat-card__meta">Con faltante o sobrante</span>
        </div>
    </section>

    <!-- ==========================================================
         CAJA: recibo en vivo (abierta) o llamado a abrir (cerrada)
         ========================================================== -->
    <div class="panel cliente-reveal cliente-reveal-3">
        <!-- ---------- Estado CERRADA: llamado a abrir caja ---------- -->
        <div class="caja-hero" id="cajaHeroAbrir" @if ($cajaAbierta) hidden @endif>
            <div class="caja-hero__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="2.5" y="6" width="19" height="12" rx="2.5"/>
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M6 9v.01M18 15v.01"/>
                </svg>
            </div>
            <h2 class="caja-hero__title">Todavía no has abierto caja</h2>
            <p class="caja-hero__subtitle">Define la base de efectivo con la que arrancas para empezar a registrar ventas en efectivo.</p>

            <div class="caja-hero__form">
                <label for="cajaBaseInicial" class="cliente-label">Base de efectivo inicial</label>
                <input type="text" id="cajaBaseInicial" class="cliente-input" placeholder="Ej: 150.000">
                <button type="button" class="cliente-btn-primary" id="abrirCajaBtn">Abrir caja</button>

                <button type="button" class="cliente-btn-ghost" id="reabrirCajaBtn" data-caja-id="{{ $ultimaCajaId }}" style="width:100%; margin-top:10px;" @unless ($ultimaCajaId) hidden @endunless>
                    Reabrir la última caja (la cerré por error)
                </button>
            </div>
        </div>

        <!-- ---------- Estado ABIERTA: recibo en vivo ---------- -->
        <div class="caja-abierta" id="cajaAbiertaPanel" @unless ($cajaAbierta) hidden @endunless>
            <div class="caja-abierta__header">
                <div>
                    <span class="status-pill status-pill--facturada">Caja abierta</span>
                    <p class="caja-abierta__meta">Abierta por <span id="cajaAbrioPor">{{ $cajaAbierta['abrioPor'] ?? '—' }}</span> · desde <span id="cajaHoraApertura">{{ $cajaAbierta['horaApertura'] ?? '—' }}</span></p>
                </div>
                <button type="button" class="cliente-btn-primary" id="cerrarCajaBtn">Cerrar caja</button>
            </div>

            <div class="caja-recibo">
                <div class="caja-recibo__row">
                    <span>Base inicial</span>
                    <strong id="reciboBase">{{ isset($cajaAbierta) ? '$'.number_format($cajaAbierta['baseInicial'], 0, ',', '.') : '$0' }}</strong>
                </div>
                <div class="caja-recibo__row caja-recibo__row--suma">
                    <span>+ Ventas en efectivo</span>
                    <strong id="reciboVentasEfectivo">{{ isset($cajaAbierta) ? '$'.number_format($cajaAbierta['ventasEfectivo'], 0, ',', '.') : '$0' }}</strong>
                </div>
                <div class="caja-recibo__row caja-recibo__row--resta">
                    <span>− Gastos en efectivo</span>
                    <strong id="reciboGastos">{{ isset($cajaAbierta) ? '$'.number_format($cajaAbierta['gastosEfectivo'], 0, ',', '.') : '$0' }}</strong>
                </div>
                <div class="caja-recibo__row caja-recibo__row--resta">
                    <span>− Compras pagadas en efectivo</span>
                    <strong id="reciboCompras">{{ isset($cajaAbierta) ? '$'.number_format($cajaAbierta['comprasEfectivo'], 0, ',', '.') : '$0' }}</strong>
                </div>
                <div class="caja-recibo__divider"></div>
                <div class="caja-recibo__row caja-recibo__row--total">
                    <span>= Total esperado en caja</span>
                    <strong id="reciboTotalEsperado">{{ isset($cajaAbierta) ? '$'.number_format($cajaAbierta['totalEsperado'], 0, ',', '.') : '$0' }}</strong>
                </div>
                <div class="caja-recibo__divider"></div>
                <div class="caja-recibo__row caja-recibo__row--secundario">
                    <span>Ventas digitales confirmadas</span>
                    <strong id="reciboVentasDigital">{{ isset($cajaAbierta) ? '$'.number_format($cajaAbierta['ventasDigital'], 0, ',', '.') : '$0' }}</strong>
                </div>
                <div class="caja-recibo__row caja-recibo__row--resta">
                    <span>− Gastos en digital</span>
                    <strong id="reciboGastosDigital">{{ isset($cajaAbierta) ? '$'.number_format($cajaAbierta['gastosDigital'], 0, ',', '.') : '$0' }}</strong>
                </div>
                <div class="caja-recibo__row caja-recibo__row--resta">
                    <span>− Compras pagadas en digital</span>
                    <strong id="reciboComprasDigital">{{ isset($cajaAbierta) ? '$'.number_format($cajaAbierta['comprasDigital'], 0, ',', '.') : '$0' }}</strong>
                </div>
                <div class="caja-recibo__row caja-recibo__row--total">
                    <span>= Total esperado en digital</span>
                    <strong id="reciboTotalEsperadoDigital">{{ isset($cajaAbierta) ? '$'.number_format($cajaAbierta['totalEsperadoDigital'], 0, ',', '.') : '$0' }}</strong>
                </div>
                <div class="caja-recibo__divider"></div>
                <div class="caja-recibo__row caja-recibo__row--total">
                    <span>Total general (efectivo + digital)</span>
                    <strong id="reciboTotalGeneral">{{ isset($cajaAbierta) ? '$'.number_format($cajaAbierta['totalGeneral'], 0, ',', '.') : '$0' }}</strong>
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
                        <th>Esperado efectivo</th>
                        <th>Dif. efectivo</th>
                        <th>Esperado digital</th>
                        <th>Dif. digital</th>
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
                            <td>
                                @if ($cierre['diferencia'] > 0)
                                    <span class="status-pill status-pill--sobrante">+${{ number_format($cierre['diferencia'], 0, ',', '.') }}</span>
                                @elseif ($cierre['diferencia'] < 0)
                                    <span class="status-pill status-pill--faltante">−${{ number_format(abs($cierre['diferencia']), 0, ',', '.') }}</span>
                                @else
                                    <span class="status-pill status-pill--sin-facturar">Exacto</span>
                                @endif
                            </td>
                            <td class="data-table__title">${{ number_format($cierre['totalEsperadoDigital'], 0, ',', '.') }}</td>
                            <td>
                                @if ($cierre['diferenciaDigital'] > 0)
                                    <span class="status-pill status-pill--sobrante">+${{ number_format($cierre['diferenciaDigital'], 0, ',', '.') }}</span>
                                @elseif ($cierre['diferenciaDigital'] < 0)
                                    <span class="status-pill status-pill--faltante">−${{ number_format(abs($cierre['diferenciaDigital']), 0, ',', '.') }}</span>
                                @else
                                    <span class="status-pill status-pill--sin-facturar">Exacto</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="data-table__empty" id="cajaEmpty" @unless (count($cierres) === 0) hidden @endunless>Todavía no hay cierres registrados.</p>
        </div>
    </div>

    <script id="cajaCierresData" type="application/json">{!! json_encode($cierres) !!}</script>
    <script id="cajaAbiertaData" type="application/json">{!! json_encode($cajaAbierta) !!}</script>

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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="slide-over__body">
            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Movimientos de la caja</h3>
                <div class="slide-over__field"><span>Base inicial</span><strong id="cierreSlideOverBase">—</strong></div>
                <div class="slide-over__field"><span>Ventas en efectivo</span><strong id="cierreSlideOverVentasEfectivo">—</strong></div>
                <div class="slide-over__field"><span>Gastos en efectivo</span><strong id="cierreSlideOverGastos">—</strong></div>
                <div class="slide-over__field"><span>Compras en efectivo</span><strong id="cierreSlideOverCompras">—</strong></div>
                <div class="slide-over__field"><span>Ventas digitales</span><strong id="cierreSlideOverVentasDigital">—</strong></div>
                <div class="slide-over__field"><span>Gastos digitales</span><strong id="cierreSlideOverGastosDigital">—</strong></div>
                <div class="slide-over__field"><span>Compras pagadas en digital</span><strong id="cierreSlideOverComprasDigital">—</strong></div>
            </section>

            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Cierre</h3>
                <div class="slide-over__field"><span>Total esperado (efectivo)</span><strong id="cierreSlideOverEsperado">—</strong></div>
                <div class="slide-over__field"><span>Total esperado (digital)</span><strong id="cierreSlideOverEsperadoDigital">—</strong></div>
                <div class="slide-over__field"><span>Total general</span><strong id="cierreSlideOverGeneral">—</strong></div>
                <div class="slide-over__field"><span>Conteo físico real</span><strong id="cierreSlideOverConteo">—</strong></div>
                <div class="slide-over__field"><span>Diferencia (efectivo)</span><strong id="cierreSlideOverDiferencia">—</strong></div>
                <div class="slide-over__field"><span>Conteo digital real</span><strong id="cierreSlideOverConteoDigital">—</strong></div>
                <div class="slide-over__field"><span>Diferencia (digital)</span><strong id="cierreSlideOverDiferenciaDigital">—</strong></div>
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="modal__body">
            <p class="caja-modal-esperado">Total esperado en efectivo: <strong id="cerrarModalEsperado">$0</strong></p>

            <label for="conteoFisicoInput" class="cliente-label">Conteo físico real</label>
            <input type="text" id="conteoFisicoInput" class="cliente-input" placeholder="Cuenta el efectivo en caja y escribe el total">

            <div class="caja-modal-diferencia" id="cajaModalDiferencia" hidden>
                <span id="cajaModalDiferenciaTexto">—</span>
            </div>

            <p class="caja-modal-esperado" style="margin-top:20px;">Total esperado en digital: <strong id="cerrarModalEsperadoDigital">$0</strong></p>

            <label for="conteoDigitalInput" class="cliente-label">Conteo digital real (revisa tu Nequi/Bancolombia)</label>
            <input type="text" id="conteoDigitalInput" class="cliente-input" placeholder="Cuánto entró de verdad por digital">

            <div class="caja-modal-diferencia" id="cajaModalDiferenciaDigital" hidden>
                <span id="cajaModalDiferenciaDigitalTexto">—</span>
            </div>
        </div>

        <div class="modal__footer">
            <button type="button" class="cliente-btn-primary" id="confirmarCierreBtn" disabled>Confirmar cierre</button>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/caja.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/cliente/caja.js') }}" defer></script>
    @endpush

</x-cliente-layout>
