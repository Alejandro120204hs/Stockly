/**
 * Stockly — Panel del negocio cliente: vista Caja (vanilla JS)
 * Depende de cliente/layout.js (formatCOP, normalizarTexto) ya cargado
 * antes que este.
 *
 * Módulos:
 *   1. initCountUp        -> anima los números de las stat cards
 *   2. initDiffChart      -> línea de tendencia con área degradada
 *      (verde arriba de cero, roja abajo) para sobrante/faltante
 *   3. initCierreSlideOver -> detalle de un cierre en el panel lateral
 *   4. initCajaFlow       -> abrir caja -> recibo en vivo -> cerrar caja
 *      (con conteo físico y cálculo de diferencia) -> pasa al historial
 */

document.addEventListener('DOMContentLoaded', function () {
    initCountUp();
    cargarCajaData();
    initDiffChart();
    initCierreSlideOver();
    initCajaFlow();
});

/* --------------------------------------------------------------------
 * 1. Contador animado en las stat cards
 * ------------------------------------------------------------------ */
function initCountUp() {
    var counters = document.querySelectorAll('[data-count]');
    if (counters.length === 0) {
        return;
    }

    var DURATION = 1100;

    counters.forEach(function (el) {
        var target = parseFloat(el.getAttribute('data-count'));
        var prefix = el.getAttribute('data-prefix') || '';
        var start = null;

        function frame(timestamp) {
            if (start === null) {
                start = timestamp;
            }
            var progress = Math.min(1, (timestamp - start) / DURATION);
            var eased = 1 - Math.pow(1 - progress, 5);
            var current = target * eased;

            el.textContent = prefix + formatNumber(current, 0);

            if (progress < 1) {
                window.requestAnimationFrame(frame);
            } else {
                el.textContent = prefix + formatNumber(target, 0);
            }
        }

        window.requestAnimationFrame(frame);
    });
}

/* --------------------------------------------------------------------
 * Estado compartido: historial de cierres (mutable -cerrar caja agrega
 * uno nuevo) y los montos fijos de "hoy" (ventas/gastos ya registrados
 * en otras partes del sistema, acá mock porque no hay backend).
 * ------------------------------------------------------------------ */
var cajaCierres = [];
var cajaCierresById = {};
var cajaHoy = { ventasEfectivo: 0, ventasDigital: 0, gastosEfectivo: 0 };

function cargarCajaData() {
    var cierresScript = document.getElementById('cajaCierresData');
    var hoyScript = document.getElementById('cajaHoyData');
    if (!cierresScript || !hoyScript) {
        return;
    }

    cajaCierres = JSON.parse(cierresScript.textContent);
    cajaCierres.forEach(function (c) { cajaCierresById[c.id] = c; });

    cajaHoy = JSON.parse(hoyScript.textContent);
}

/** formatCOP no está pensado para negativos (daría "$-5.000"); esto
 * formatea bien el signo: "+$5.000" / "−$5.000" / "$0". */
function formatDiferencia(diferencia) {
    if (diferencia > 0) {
        return '+' + formatCOP(diferencia);
    }
    if (diferencia < 0) {
        return '−' + formatCOP(Math.abs(diferencia));
    }
    return formatCOP(0);
}

/* --------------------------------------------------------------------
 * 2. Gráfico: línea de tendencia con área de degradado (sobrante arriba
 * de cero, faltante abajo) -una sola línea conecta los cierres; el
 * relleno pasa de verde a rojo justo en la línea de cero.
 * ------------------------------------------------------------------ */
function initDiffChart() {
    renderDiffChart();
}

var CAJA_CHART_COLOR_SAGE = '#4A7C6F';
var CAJA_CHART_COLOR_SAGE_DARK = '#3C6459';
var CAJA_CHART_COLOR_ERROR = '#B3473C';
var CAJA_CHART_COLOR_MIST = '#8C9BAB';
var CAJA_CHART_COLOR_SURFACE = '#FFFFFF';

function renderDiffChart() {
    var container = document.getElementById('cajaDiffChart');
    if (!container) {
        return;
    }

    var ultimos = cajaCierres.slice(0, 6).slice().reverse();
    if (ultimos.length === 0) {
        container.innerHTML = '';
        return;
    }

    var MAX_DIFERENCIA = 15000;
    var W = 640;
    var H = 170;
    var plotTop = 20;
    var plotBottom = 130;
    var zeroY = (plotTop + plotBottom) / 2;
    var marginX = 26;
    var stepX = ultimos.length > 1 ? (W - marginX * 2) / (ultimos.length - 1) : 0;

    var puntos = ultimos.map(function (cierre, i) {
        var x = marginX + i * stepX;
        var clamped = Math.max(-MAX_DIFERENCIA, Math.min(MAX_DIFERENCIA, cierre.diferencia));
        var y = zeroY - (clamped / MAX_DIFERENCIA) * (zeroY - plotTop);
        return { x: x, y: y, cierre: cierre };
    });

    var lineaPath = puntos.map(function (p, i) {
        return (i === 0 ? 'M' : 'L') + p.x.toFixed(1) + ',' + p.y.toFixed(1);
    }).join(' ');

    var primero = puntos[0];
    var ultimo = puntos[puntos.length - 1];
    var areaPath = 'M' + primero.x.toFixed(1) + ',' + zeroY.toFixed(1) + ' ' +
        puntos.map(function (p) { return 'L' + p.x.toFixed(1) + ',' + p.y.toFixed(1); }).join(' ') +
        ' L' + ultimo.x.toFixed(1) + ',' + zeroY.toFixed(1) + ' Z';

    var zeroOffsetPct = (((zeroY - plotTop) / (plotBottom - plotTop)) * 100).toFixed(1);

    var puntosHtml = puntos.map(function (p) {
        var d = p.cierre.diferencia;
        var color = d > 0 ? CAJA_CHART_COLOR_SAGE_DARK : (d < 0 ? CAJA_CHART_COLOR_ERROR : CAJA_CHART_COLOR_MIST);
        return '<circle cx="' + p.x.toFixed(1) + '" cy="' + p.y.toFixed(1) + '" r="4.5" fill="' + color + '" stroke="' + CAJA_CHART_COLOR_SURFACE + '" stroke-width="2"></circle>';
    }).join('');

    var valoresHtml = puntos.map(function (p) {
        var d = p.cierre.diferencia;
        var texto = d > 0 ? '+' + formatNumber(d, 0) : (d < 0 ? '−' + formatNumber(Math.abs(d), 0) : 'Exacto');
        var color = d > 0 ? CAJA_CHART_COLOR_SAGE_DARK : (d < 0 ? CAJA_CHART_COLOR_ERROR : CAJA_CHART_COLOR_MIST);
        var labelY = d >= 0 ? p.y - 12 : p.y + 20;
        return '<text x="' + p.x.toFixed(1) + '" y="' + labelY.toFixed(1) + '" text-anchor="middle" class="caja-line-chart__value" fill="' + color + '">' + texto + '</text>';
    }).join('');

    var fechasHtml = puntos.map(function (p) {
        return '<text x="' + p.x.toFixed(1) + '" y="163" text-anchor="middle" class="caja-line-chart__date">' + p.cierre.fecha.split(' ')[0] + '</text>';
    }).join('');

    container.innerHTML =
        '<svg viewBox="0 0 ' + W + ' ' + H + '" class="caja-line-chart__svg">' +
            '<defs><linearGradient id="cajaDiffGradient" x1="0" y1="' + plotTop + '" x2="0" y2="' + plotBottom + '" gradientUnits="userSpaceOnUse">' +
                '<stop offset="0%" stop-color="' + CAJA_CHART_COLOR_SAGE + '" stop-opacity="0.35"></stop>' +
                '<stop offset="' + zeroOffsetPct + '%" stop-color="' + CAJA_CHART_COLOR_SAGE + '" stop-opacity="0.04"></stop>' +
                '<stop offset="' + zeroOffsetPct + '%" stop-color="' + CAJA_CHART_COLOR_ERROR + '" stop-opacity="0.04"></stop>' +
                '<stop offset="100%" stop-color="' + CAJA_CHART_COLOR_ERROR + '" stop-opacity="0.35"></stop>' +
            '</linearGradient></defs>' +
            '<line x1="' + marginX + '" y1="' + zeroY.toFixed(1) + '" x2="' + (W - marginX) + '" y2="' + zeroY.toFixed(1) + '" class="caja-line-chart__zero"></line>' +
            '<path d="' + areaPath + '" fill="url(#cajaDiffGradient)"></path>' +
            '<path d="' + lineaPath + '" class="caja-line-chart__line"></path>' +
            puntosHtml + valoresHtml + fechasHtml +
        '</svg>';
}

/* --------------------------------------------------------------------
 * 3. Panel lateral con el detalle de un cierre
 * ------------------------------------------------------------------ */
function initCierreSlideOver() {
    var tableBody = document.getElementById('cajaTableBody');
    var overlay = document.getElementById('cierreSlideOverOverlay');
    var slideOver = document.getElementById('cierreSlideOver');
    var closeBtn = document.getElementById('cierreSlideOverClose');
    if (!tableBody || !overlay || !slideOver) {
        return;
    }

    function abrirCierre(id) {
        var cierre = cajaCierresById[id];
        if (!cierre) {
            return;
        }

        document.getElementById('cierreSlideOverTitulo').textContent = cierre.fecha;

        var pill = document.getElementById('cierreSlideOverDiferenciaPill');
        if (cierre.diferencia > 0) {
            pill.className = 'status-pill status-pill--sobrante';
            pill.textContent = 'Sobrante';
        } else if (cierre.diferencia < 0) {
            pill.className = 'status-pill status-pill--faltante';
            pill.textContent = 'Faltante';
        } else {
            pill.className = 'status-pill status-pill--sin-facturar';
            pill.textContent = 'Exacto';
        }

        document.getElementById('cierreSlideOverBase').textContent = formatCOP(cierre.baseInicial);
        document.getElementById('cierreSlideOverVentasEfectivo').textContent = formatCOP(cierre.ventasEfectivo);
        document.getElementById('cierreSlideOverVentasDigital').textContent = formatCOP(cierre.ventasDigital);
        document.getElementById('cierreSlideOverGastos').textContent = formatCOP(cierre.gastosEfectivo);
        document.getElementById('cierreSlideOverEsperado').textContent = formatCOP(cierre.totalEsperado);
        document.getElementById('cierreSlideOverGeneral').textContent = formatCOP(cierre.totalGeneral);
        document.getElementById('cierreSlideOverConteo').textContent = formatCOP(cierre.conteoReal);
        document.getElementById('cierreSlideOverDiferencia').textContent = formatDiferencia(cierre.diferencia);
        document.getElementById('cierreSlideOverAbrioPor').textContent = cierre.abrioPor + ' · ' + cierre.horaCierre;

        slideOver.classList.add('is-open');
        slideOver.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-visible');
    }

    function cerrarSlideOver() {
        slideOver.classList.remove('is-open');
        slideOver.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-visible');
    }

    function wireRow(row) {
        var id = parseInt(row.getAttribute('data-cierre-id'), 10);
        row.addEventListener('click', function () { abrirCierre(id); });
        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                abrirCierre(id);
            }
        });
    }

    tableBody.querySelectorAll('.data-table__row').forEach(wireRow);
    window.wireCajaFilaCierre = wireRow;

    closeBtn.addEventListener('click', cerrarSlideOver);
    overlay.addEventListener('click', cerrarSlideOver);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && slideOver.classList.contains('is-open')) {
            cerrarSlideOver();
        }
    });
}

/* --------------------------------------------------------------------
 * 4. Flujo completo: abrir caja -> recibo en vivo -> cerrar caja
 * ------------------------------------------------------------------ */
function initCajaFlow() {
    var heroAbrir = document.getElementById('cajaHeroAbrir');
    var panelAbierta = document.getElementById('cajaAbiertaPanel');
    var baseInicialInput = document.getElementById('cajaBaseInicial');
    var abrirBtn = document.getElementById('abrirCajaBtn');
    if (!heroAbrir || !panelAbierta || !abrirBtn) {
        return;
    }

    var estadoValorEl = document.getElementById('cajaEstadoValor');
    var estadoMetaEl = document.getElementById('cajaEstadoMeta');
    var estadoIconoEl = document.getElementById('cajaEstadoIcono');

    var baseActual = 0;
    var totalEsperadoActual = 0;

    formatearInputDinero(baseInicialInput);

    function formatHoraAhora() {
        var ahora = new Date();
        var horas = ahora.getHours();
        var minutos = ahora.getMinutes();
        var sufijo = horas >= 12 ? 'p.m.' : 'a.m.';
        var horas12 = horas % 12;
        if (horas12 === 0) {
            horas12 = 12;
        }
        var minutosTexto = minutos < 10 ? '0' + minutos : String(minutos);
        return horas12 + ':' + minutosTexto + ' ' + sufijo;
    }

    abrirBtn.addEventListener('click', function () {
        var base = valorDineroInput(baseInicialInput);
        if (!base) {
            baseInicialInput.focus();
            return;
        }

        baseActual = base;
        totalEsperadoActual = baseActual + cajaHoy.ventasEfectivo - cajaHoy.gastosEfectivo;
        var totalGeneral = totalEsperadoActual + cajaHoy.ventasDigital;

        document.getElementById('reciboBase').textContent = formatCOP(baseActual);
        document.getElementById('reciboVentasEfectivo').textContent = formatCOP(cajaHoy.ventasEfectivo);
        document.getElementById('reciboGastos').textContent = formatCOP(cajaHoy.gastosEfectivo);
        document.getElementById('reciboTotalEsperado').textContent = formatCOP(totalEsperadoActual);
        document.getElementById('reciboVentasDigital').textContent = formatCOP(cajaHoy.ventasDigital);
        document.getElementById('reciboTotalGeneral').textContent = formatCOP(totalGeneral);
        document.getElementById('cajaHoraApertura').textContent = formatHoraAhora();

        heroAbrir.hidden = true;
        panelAbierta.hidden = false;

        estadoValorEl.textContent = 'Abierta';
        estadoMetaEl.textContent = 'Base inicial: ' + formatCOP(baseActual) + ' · desde ahora';
        estadoIconoEl.closest('.stat-card').classList.remove('stat-card--mist');
        estadoIconoEl.closest('.stat-card').classList.add('stat-card--sage');
    });

    initCerrarCajaModal(function () { return { baseActual: baseActual, totalEsperadoActual: totalEsperadoActual }; }, function () {
        heroAbrir.hidden = false;
        panelAbierta.hidden = true;
        baseInicialInput.value = '';

        estadoValorEl.textContent = 'Cerrada';
        estadoMetaEl.textContent = 'Ya cerraste caja hoy';
        estadoIconoEl.closest('.stat-card').classList.remove('stat-card--sage');
        estadoIconoEl.closest('.stat-card').classList.add('stat-card--mist');
    });
}

function initCerrarCajaModal(getEstadoActual, onCerrada) {
    var openBtn = document.getElementById('cerrarCajaBtn');
    var modal = document.getElementById('cerrarCajaModal');
    var overlay = document.getElementById('cerrarCajaOverlay');
    if (!openBtn || !modal || !overlay) {
        return;
    }

    var closeBtn = document.getElementById('cerrarCajaClose');
    var esperadoEl = document.getElementById('cerrarModalEsperado');
    var conteoInput = document.getElementById('conteoFisicoInput');
    var diferenciaBox = document.getElementById('cajaModalDiferencia');
    var diferenciaTexto = document.getElementById('cajaModalDiferenciaTexto');
    var confirmarBtn = document.getElementById('confirmarCierreBtn');

    formatearInputDinero(conteoInput);

    function actualizarDiferencia() {
        var estado = getEstadoActual();

        if (!conteoInput.value.replace(/\D/g, '')) {
            diferenciaBox.hidden = true;
            confirmarBtn.disabled = true;
            return;
        }

        var conteo = valorDineroInput(conteoInput);
        var diferencia = conteo - estado.totalEsperadoActual;
        diferenciaBox.hidden = false;
        confirmarBtn.disabled = false;

        if (diferencia === 0) {
            diferenciaBox.className = 'caja-modal-diferencia es-exacto';
            diferenciaTexto.textContent = 'Cuadra exacto con lo esperado.';
        } else if (diferencia > 0) {
            diferenciaBox.className = 'caja-modal-diferencia es-sobrante';
            diferenciaTexto.textContent = 'Sobrante de ' + formatCOP(diferencia);
        } else {
            diferenciaBox.className = 'caja-modal-diferencia es-faltante';
            diferenciaTexto.textContent = 'Faltante de ' + formatCOP(Math.abs(diferencia));
        }
    }

    conteoInput.addEventListener('input', actualizarDiferencia);

    function openModal() {
        var estado = getEstadoActual();
        esperadoEl.textContent = formatCOP(estado.totalEsperadoActual);
        conteoInput.value = '';
        diferenciaBox.hidden = true;
        confirmarBtn.disabled = true;

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-visible');
        window.setTimeout(function () { conteoInput.focus(); }, 250);
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-visible');
    }

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    function formatFechaHoraAhora() {
        var ahora = new Date();
        var horas = ahora.getHours();
        var minutos = ahora.getMinutes();
        var sufijo = horas >= 12 ? 'p.m.' : 'a.m.';
        var horas12 = horas % 12;
        if (horas12 === 0) {
            horas12 = 12;
        }
        var minutosTexto = minutos < 10 ? '0' + minutos : String(minutos);
        return horas12 + ':' + minutosTexto + ' ' + sufijo;
    }

    confirmarBtn.addEventListener('click', function () {
        if (confirmarBtn.disabled) {
            return;
        }

        var estado = getEstadoActual();
        var conteo = valorDineroInput(conteoInput);
        var diferencia = conteo - estado.totalEsperadoActual;

        var originalText = confirmarBtn.textContent;
        confirmarBtn.disabled = true;
        confirmarBtn.textContent = 'Cerrando...';

        window.setTimeout(function () {
            var nuevoId = cajaCierres.reduce(function (max, c) { return Math.max(max, c.id); }, 0) + 1;
            var cierre = {
                id: nuevoId,
                fecha: 'Hoy',
                abrioPor: 'Laura Ramírez',
                baseInicial: estado.baseActual,
                ventasEfectivo: cajaHoy.ventasEfectivo,
                ventasDigital: cajaHoy.ventasDigital,
                gastosEfectivo: cajaHoy.gastosEfectivo,
                totalEsperado: estado.totalEsperadoActual,
                totalGeneral: estado.totalEsperadoActual + cajaHoy.ventasDigital,
                conteoReal: conteo,
                diferencia: diferencia,
                horaCierre: formatFechaHoraAhora()
            };

            cajaCierres.unshift(cierre);
            cajaCierresById[cierre.id] = cierre;

            var tableBody = document.getElementById('cajaTableBody');
            if (tableBody) {
                var row = document.createElement('tr');
                row.className = 'data-table__row';
                row.setAttribute('data-cierre-id', cierre.id);
                row.tabIndex = 0;

                var pillClass = diferencia > 0 ? 'status-pill--sobrante' : (diferencia < 0 ? 'status-pill--faltante' : 'status-pill--sin-facturar');
                var pillTexto = diferencia > 0 ? '+' + formatCOP(diferencia) : (diferencia < 0 ? '−' + formatCOP(Math.abs(diferencia)) : 'Exacto');

                row.innerHTML =
                    '<td><div class="data-table__title">' + cierre.fecha + '</div><div class="data-table__meta">Cerrada ' + cierre.horaCierre + '</div></td>' +
                    '<td class="data-table__meta">' + formatCOP(cierre.baseInicial) + '</td>' +
                    '<td class="data-table__title">' + formatCOP(cierre.totalEsperado) + '</td>' +
                    '<td class="data-table__meta">' + formatCOP(cierre.conteoReal) + '</td>' +
                    '<td><span class="status-pill ' + pillClass + '">' + pillTexto + '</span></td>';

                tableBody.insertBefore(row, tableBody.firstChild);
                if (window.wireCajaFilaCierre) {
                    window.wireCajaFilaCierre(row);
                }
            }

            renderDiffChart();

            confirmarBtn.disabled = false;
            confirmarBtn.textContent = originalText;
            closeModal();
            onCerrada();
        }, 700);
    });
}
