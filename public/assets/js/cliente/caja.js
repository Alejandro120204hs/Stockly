/**
 * Stockly — Panel del negocio cliente: vista Caja (vanilla JS)
 * Depende de cliente/layout.js (formatCOP, formatNumber, formatearInputDinero,
 * valorDineroInput, mostrarError) ya cargado antes que este.
 *
 * Una caja es una SESIÓN (abrir -> cerrar), no un día calendario -si el
 * negocio cierra pasada la medianoche, sigue siendo la misma caja. Este
 * archivo solo refleja en pantalla lo que ya decidió el backend
 * (App\Http\Controllers\Cliente\CajaController); no calcula esperado ni
 * diferencia acá -esos números siempre vienen del servidor.
 *
 * Módulos:
 *   1. initCountUp        -> anima los números de las stat cards
 *   2. initDiffChart      -> línea de tendencia con área degradada
 *      (verde arriba de cero, roja abajo) para sobrante/faltante
 *   3. initCierreSlideOver -> detalle de un cierre en el panel lateral
 *   4. initCajaFlow       -> abrir/reabrir caja -> recibo en vivo -> cerrar
 *      caja (con conteo físico) -> pasa al historial
 */

document.addEventListener('DOMContentLoaded', function () {
    initCountUp();
    cargarCajaData();
    initDiffChart();
    initCierreSlideOver();
    initCajaFlow();
    initHistorialCierres();
});

function cajaApiRequest(method, url, data) {
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    return fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : ''
        },
        body: data !== undefined ? JSON.stringify(data) : undefined
    }).then(function (response) {
        return response.json().catch(function () { return {}; }).then(function (json) {
            if (!response.ok) {
                throw new Error(json.message || 'Ocurrió un error inesperado.');
            }
            return json;
        });
    });
}

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
            // Si algo ya actualizó este número a mano (abrir/cerrar/reabrir
            // caja), esta animación quedó obsoleta -sin este freno, un
            // frame tardío (pestaña en segundo plano throttlea rAF) puede
            // llegar después y pisar el valor real con el de la carga
            // inicial de la página.
            if (el.dataset.countupCancelado) {
                return;
            }
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
 * uno nuevo, reabrir quita el más reciente) y la caja abierta actual (o
 * null si está cerrada), ambos ya calculados por el backend.
 * ------------------------------------------------------------------ */
var cajaCierres = [];
var cajaCierresById = {};
var cajaActual = null;

function cargarCajaData() {
    var cierresScript = document.getElementById('cajaCierresData');
    var abiertaScript = document.getElementById('cajaAbiertaData');
    if (!cierresScript || !abiertaScript) {
        return;
    }

    cajaCierres = JSON.parse(cierresScript.textContent);
    cajaCierres.forEach(function (c) { cajaCierresById[c.id] = c; });

    cajaActual = JSON.parse(abiertaScript.textContent);
}

/** Pinta el status-pill de sobrante/faltante/exacto dentro de una celda de
 * la tabla de historial -reutilizado para la columna de efectivo y la de
 * digital, que llevan la misma lógica. */
function pintarPillDiferencia(celda, diferencia) {
    var pillClass = diferencia > 0 ? 'status-pill--sobrante' : (diferencia < 0 ? 'status-pill--faltante' : 'status-pill--sin-facturar');
    var pillTexto = diferencia > 0 ? '+' + formatCOP(diferencia) : (diferencia < 0 ? '−' + formatCOP(Math.abs(diferencia)) : 'Exacto');
    celda.innerHTML = '<span class="status-pill ' + pillClass + '">' + pillTexto + '</span>';
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
 * Historial de cierres: filtro por mes + paginación (6 por página).
 * Todo el historial ya llegó pre-cargado en `cajaCierres` -acá solo se
 * decide qué filas mostrar, ninguna petición nueva al servidor.
 * ------------------------------------------------------------------ */
var CAJA_HISTORIAL_PAGE_SIZE = 6;
var cajaHistorialPagina = 1;

function poblarFiltroMeses() {
    var select = document.getElementById('cajaMesFilter');
    if (!select) {
        return;
    }

    var valorActual = select.value;
    var vistos = {};

    // Reconstruye las opciones desde cero (menos la primera, "Todos los
    // meses") -así un cierre nuevo de un mes que todavía no existía en la
    // lista también aparece sin recargar la página.
    while (select.options.length > 1) {
        select.remove(1);
    }

    cajaCierres.forEach(function (c) {
        if (vistos[c.mesKey]) {
            return;
        }
        vistos[c.mesKey] = true;
        var option = document.createElement('option');
        option.value = c.mesKey;
        option.textContent = c.mesLabel;
        select.appendChild(option);
    });

    if (vistos[valorActual]) {
        select.value = valorActual;
    }
}

function initHistorialCierres() {
    var table = document.getElementById('cajaTable');
    var filtro = document.getElementById('cajaMesFilter');
    if (!table || !filtro) {
        return;
    }

    poblarFiltroMeses();

    filtro.addEventListener('change', function () {
        cajaHistorialPagina = 1;
        renderHistorialCierres();
    });

    document.getElementById('cajaPrevPage')?.addEventListener('click', function () {
        if (cajaHistorialPagina > 1) {
            cajaHistorialPagina--;
            renderHistorialCierres();
        }
    });
    document.getElementById('cajaNextPage')?.addEventListener('click', function () {
        cajaHistorialPagina++;
        renderHistorialCierres();
    });

    renderHistorialCierres();
}

function renderHistorialCierres() {
    var table = document.getElementById('cajaTable');
    var filtro = document.getElementById('cajaMesFilter');
    var emptyState = document.getElementById('cajaEmpty');
    var paginationEl = document.getElementById('cajaPagination');
    var pageInfoEl = document.getElementById('cajaPageInfo');
    var prevBtn = document.getElementById('cajaPrevPage');
    var nextBtn = document.getElementById('cajaNextPage');
    if (!table || !filtro) {
        return;
    }

    var mesElegido = filtro.value;
    var idsQueCoinciden = {};
    cajaCierres.forEach(function (c) {
        if (!mesElegido || c.mesKey === mesElegido) {
            idsQueCoinciden[c.id] = true;
        }
    });
    var totalCoincidencias = Object.keys(idsQueCoinciden).length;

    var totalPaginas = Math.max(1, Math.ceil(totalCoincidencias / CAJA_HISTORIAL_PAGE_SIZE));
    cajaHistorialPagina = Math.min(cajaHistorialPagina, totalPaginas);
    var desde = (cajaHistorialPagina - 1) * CAJA_HISTORIAL_PAGE_SIZE;
    var hasta = desde + CAJA_HISTORIAL_PAGE_SIZE;

    // El orden de las filas en el DOM ya viene del más reciente al más
    // antiguo (así se insertan/renderizan) -contar solo entre las que
    // coinciden con el filtro da directamente la página correcta.
    var indice = -1;
    table.querySelectorAll('.data-table__row').forEach(function (row) {
        var id = row.getAttribute('data-cierre-id');
        if (!idsQueCoinciden[id]) {
            row.hidden = true;
            return;
        }
        indice++;
        row.hidden = indice < desde || indice >= hasta;
    });

    if (emptyState) {
        emptyState.hidden = totalCoincidencias !== 0;
    }
    if (paginationEl) {
        paginationEl.hidden = totalCoincidencias === 0;
    }
    if (pageInfoEl) {
        pageInfoEl.textContent = 'Página ' + cajaHistorialPagina + ' de ' + totalPaginas;
    }
    if (prevBtn) {
        prevBtn.disabled = cajaHistorialPagina <= 1;
    }
    if (nextBtn) {
        nextBtn.disabled = cajaHistorialPagina >= totalPaginas;
    }
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
var CAJA_CHART_COLOR_DIGITAL = '#566573';

/** Calcula los puntos (x, y) de una serie -misma escala/zero-line para
 * poder dibujar efectivo y digital una encima de la otra. */
function calcularPuntosSerie(ultimos, campo, marginX, stepX, zeroY, plotTop, maxDiferencia) {
    return ultimos.map(function (cierre, i) {
        var x = marginX + i * stepX;
        var valor = cierre[campo];
        var clamped = Math.max(-maxDiferencia, Math.min(maxDiferencia, valor));
        var y = zeroY - (clamped / maxDiferencia) * (zeroY - plotTop);
        return { x: x, y: y, valor: valor, cierre: cierre };
    });
}

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

    // La escala se ajusta a la diferencia más grande de cualquiera de las
    // dos series (con un mínimo para que el gráfico no quede plano si todo
    // cuadra exacto).
    var maxAbs = ultimos.reduce(function (max, c) {
        return Math.max(max, Math.abs(c.diferencia), Math.abs(c.diferenciaDigital));
    }, 0);
    var MAX_DIFERENCIA = Math.max(5000, maxAbs);

    var W = 640;
    var H = 170;
    var plotTop = 20;
    var plotBottom = 130;
    var zeroY = (plotTop + plotBottom) / 2;
    var marginX = 26;
    var stepX = ultimos.length > 1 ? (W - marginX * 2) / (ultimos.length - 1) : 0;

    var puntosEfectivo = calcularPuntosSerie(ultimos, 'diferencia', marginX, stepX, zeroY, plotTop, MAX_DIFERENCIA);
    var puntosDigital = calcularPuntosSerie(ultimos, 'diferenciaDigital', marginX, stepX, zeroY, plotTop, MAX_DIFERENCIA);

    var lineaEfectivoPath = puntosEfectivo.map(function (p, i) {
        return (i === 0 ? 'M' : 'L') + p.x.toFixed(1) + ',' + p.y.toFixed(1);
    }).join(' ');

    var lineaDigitalPath = puntosDigital.map(function (p, i) {
        return (i === 0 ? 'M' : 'L') + p.x.toFixed(1) + ',' + p.y.toFixed(1);
    }).join(' ');

    var primero = puntosEfectivo[0];
    var ultimo = puntosEfectivo[puntosEfectivo.length - 1];
    var areaPath = 'M' + primero.x.toFixed(1) + ',' + zeroY.toFixed(1) + ' ' +
        puntosEfectivo.map(function (p) { return 'L' + p.x.toFixed(1) + ',' + p.y.toFixed(1); }).join(' ') +
        ' L' + ultimo.x.toFixed(1) + ',' + zeroY.toFixed(1) + ' Z';

    var zeroOffsetPct = (((zeroY - plotTop) / (plotBottom - plotTop)) * 100).toFixed(1);

    var puntosEfectivoHtml = puntosEfectivo.map(function (p) {
        var color = p.valor > 0 ? CAJA_CHART_COLOR_SAGE_DARK : (p.valor < 0 ? CAJA_CHART_COLOR_ERROR : CAJA_CHART_COLOR_MIST);
        return '<circle cx="' + p.x.toFixed(1) + '" cy="' + p.y.toFixed(1) + '" r="4.5" fill="' + color + '" stroke="' + CAJA_CHART_COLOR_SURFACE + '" stroke-width="2"></circle>';
    }).join('');

    var puntosDigitalHtml = puntosDigital.map(function (p) {
        return '<circle cx="' + p.x.toFixed(1) + '" cy="' + p.y.toFixed(1) + '" r="3.5" fill="' + CAJA_CHART_COLOR_DIGITAL + '" stroke="' + CAJA_CHART_COLOR_SURFACE + '" stroke-width="2"></circle>';
    }).join('');

    var valoresHtml = puntosEfectivo.map(function (p) {
        var d = p.valor;
        var texto = d > 0 ? '+' + formatNumber(d, 0) : (d < 0 ? '−' + formatNumber(Math.abs(d), 0) : 'Exacto');
        var color = d > 0 ? CAJA_CHART_COLOR_SAGE_DARK : (d < 0 ? CAJA_CHART_COLOR_ERROR : CAJA_CHART_COLOR_MIST);
        var labelY = d >= 0 ? p.y - 12 : p.y + 20;
        return '<text x="' + p.x.toFixed(1) + '" y="' + labelY.toFixed(1) + '" text-anchor="middle" class="caja-line-chart__value" fill="' + color + '">' + texto + '</text>';
    }).join('');

    var fechasHtml = puntosEfectivo.map(function (p) {
        return '<text x="' + p.x.toFixed(1) + '" y="163" text-anchor="middle" class="caja-line-chart__date">' + p.cierre.fecha.split(' ')[0] + '</text>';
    }).join('');

    container.innerHTML =
        '<div class="caja-line-chart__legend">' +
            '<span class="caja-line-chart__legend-item"><i style="background:' + CAJA_CHART_COLOR_SAGE_DARK + ';"></i>Efectivo</span>' +
            '<span class="caja-line-chart__legend-item"><i style="background:' + CAJA_CHART_COLOR_DIGITAL + '; border-radius:0;"></i>Digital</span>' +
        '</div>' +
        '<svg viewBox="0 0 ' + W + ' ' + H + '" class="caja-line-chart__svg">' +
            '<defs><linearGradient id="cajaDiffGradient" x1="0" y1="' + plotTop + '" x2="0" y2="' + plotBottom + '" gradientUnits="userSpaceOnUse">' +
                '<stop offset="0%" stop-color="' + CAJA_CHART_COLOR_SAGE + '" stop-opacity="0.35"></stop>' +
                '<stop offset="' + zeroOffsetPct + '%" stop-color="' + CAJA_CHART_COLOR_SAGE + '" stop-opacity="0.04"></stop>' +
                '<stop offset="' + zeroOffsetPct + '%" stop-color="' + CAJA_CHART_COLOR_ERROR + '" stop-opacity="0.04"></stop>' +
                '<stop offset="100%" stop-color="' + CAJA_CHART_COLOR_ERROR + '" stop-opacity="0.35"></stop>' +
            '</linearGradient></defs>' +
            '<line x1="' + marginX + '" y1="' + zeroY.toFixed(1) + '" x2="' + (W - marginX) + '" y2="' + zeroY.toFixed(1) + '" class="caja-line-chart__zero"></line>' +
            '<path d="' + areaPath + '" fill="url(#cajaDiffGradient)"></path>' +
            '<path d="' + lineaDigitalPath + '" class="caja-line-chart__line caja-line-chart__line--digital"></path>' +
            '<path d="' + lineaEfectivoPath + '" class="caja-line-chart__line"></path>' +
            puntosDigitalHtml + puntosEfectivoHtml + valoresHtml + fechasHtml +
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

        // Con dos conteos (efectivo y digital) el pill del encabezado solo
        // resume si algo quedó descuadrado -el detalle de cuál y por
        // cuánto va en las dos filas de "Diferencia" más abajo.
        var pill = document.getElementById('cierreSlideOverDiferenciaPill');
        if (cierre.diferencia === 0 && cierre.diferenciaDigital === 0) {
            pill.className = 'status-pill status-pill--sin-facturar';
            pill.textContent = 'Exacto';
        } else {
            var esSobrante = (cierre.diferencia + cierre.diferenciaDigital) >= 0;
            pill.className = 'status-pill ' + (esSobrante ? 'status-pill--sobrante' : 'status-pill--faltante');
            pill.textContent = 'Descuadrado';
        }

        document.getElementById('cierreSlideOverBase').textContent = formatCOP(cierre.baseInicial);
        document.getElementById('cierreSlideOverVentasEfectivo').textContent = formatCOP(cierre.ventasEfectivo);
        document.getElementById('cierreSlideOverGastos').textContent = formatCOP(cierre.gastosEfectivo);
        document.getElementById('cierreSlideOverCompras').textContent = formatCOP(cierre.comprasEfectivo);
        document.getElementById('cierreSlideOverNomina').textContent = formatCOP(cierre.nominaEfectivo);
        document.getElementById('cierreSlideOverVentasDigital').textContent = formatCOP(cierre.ventasDigital);
        document.getElementById('cierreSlideOverGastosDigital').textContent = formatCOP(cierre.gastosDigital);
        document.getElementById('cierreSlideOverComprasDigital').textContent = formatCOP(cierre.comprasDigital);
        document.getElementById('cierreSlideOverNominaDigital').textContent = formatCOP(cierre.nominaDigital);
        document.getElementById('cierreSlideOverEsperado').textContent = formatCOP(cierre.totalEsperado);
        document.getElementById('cierreSlideOverEsperadoDigital').textContent = formatCOP(cierre.totalEsperadoDigital);
        document.getElementById('cierreSlideOverGeneral').textContent = formatCOP(cierre.totalGeneral);
        document.getElementById('cierreSlideOverConteo').textContent = formatCOP(cierre.conteoReal);
        document.getElementById('cierreSlideOverDiferencia').textContent = formatDiferencia(cierre.diferencia);
        document.getElementById('cierreSlideOverConteoDigital').textContent = formatCOP(cierre.conteoDigital);
        document.getElementById('cierreSlideOverDiferenciaDigital').textContent = formatDiferencia(cierre.diferenciaDigital);
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
 * 4. Flujo completo: abrir/reabrir caja -> recibo en vivo -> cerrar caja
 * ------------------------------------------------------------------ */
function initCajaFlow() {
    var heroAbrir = document.getElementById('cajaHeroAbrir');
    var panelAbierta = document.getElementById('cajaAbiertaPanel');
    var baseInicialInput = document.getElementById('cajaBaseInicial');
    var abrirBtn = document.getElementById('abrirCajaBtn');
    var reabrirBtn = document.getElementById('reabrirCajaBtn');
    if (!heroAbrir || !panelAbierta || !abrirBtn) {
        return;
    }

    var estadoValorEl = document.getElementById('cajaEstadoValor');
    var estadoMetaEl = document.getElementById('cajaEstadoMeta');
    var estadoCard = document.getElementById('cajaEstadoCard');
    var statVentasEl = document.getElementById('cajaStatVentas');
    var statGastosEl = document.getElementById('cajaStatGastos');

    formatearInputDinero(baseInicialInput);

    function pintarRecibo(caja) {
        document.getElementById('reciboBase').textContent = formatCOP(caja.baseInicial);
        document.getElementById('reciboVentasEfectivo').textContent = formatCOP(caja.ventasEfectivo);
        document.getElementById('reciboGastos').textContent = formatCOP(caja.gastosEfectivo);
        document.getElementById('reciboCompras').textContent = formatCOP(caja.comprasEfectivo);
        document.getElementById('reciboNomina').textContent = formatCOP(caja.nominaEfectivo);
        document.getElementById('reciboTotalEsperado').textContent = formatCOP(caja.totalEsperado);
        document.getElementById('reciboVentasDigital').textContent = formatCOP(caja.ventasDigital);
        document.getElementById('reciboGastosDigital').textContent = formatCOP(caja.gastosDigital);
        document.getElementById('reciboComprasDigital').textContent = formatCOP(caja.comprasDigital);
        document.getElementById('reciboNominaDigital').textContent = formatCOP(caja.nominaDigital);
        document.getElementById('reciboTotalEsperadoDigital').textContent = formatCOP(caja.totalEsperadoDigital);
        document.getElementById('reciboTotalGeneral').textContent = formatCOP(caja.totalGeneral);
        document.getElementById('cajaHoraApertura').textContent = caja.horaApertura;
        document.getElementById('cajaAbrioPor').textContent = caja.abrioPor;

        statVentasEl.dataset.countupCancelado = '1';
        statGastosEl.dataset.countupCancelado = '1';
        statVentasEl.textContent = formatCOP(caja.ventasEfectivo + caja.ventasDigital);
        statGastosEl.textContent = formatCOP(caja.gastosEfectivo + caja.gastosDigital);
    }

    function pasarAAbierta(caja) {
        cajaActual = caja;
        pintarRecibo(caja);

        heroAbrir.hidden = true;
        panelAbierta.hidden = false;

        estadoValorEl.textContent = 'Abierta';
        estadoMetaEl.textContent = 'Base ' + formatCOP(caja.baseInicial) + ' · ' + caja.horaApertura;
        estadoCard.classList.remove('stat-card--mist');
        estadoCard.classList.add('stat-card--sage');
    }

    function pasarACerrada() {
        cajaActual = null;
        heroAbrir.hidden = false;
        panelAbierta.hidden = true;
        baseInicialInput.value = '';

        estadoValorEl.textContent = 'Cerrada';
        estadoMetaEl.textContent = 'Todavía no la has abierto';
        estadoCard.classList.remove('stat-card--sage');
        estadoCard.classList.add('stat-card--mist');
        statVentasEl.dataset.countupCancelado = '1';
        statGastosEl.dataset.countupCancelado = '1';
        statVentasEl.textContent = formatCOP(0);
        statGastosEl.textContent = formatCOP(0);
    }

    abrirBtn.addEventListener('click', function () {
        var base = valorDineroInput(baseInicialInput);
        if (!base) {
            baseInicialInput.focus();
            return;
        }

        var originalText = abrirBtn.textContent;
        abrirBtn.disabled = true;
        abrirBtn.textContent = 'Abriendo...';

        cajaApiRequest('POST', '/cliente/caja/abrir', { base_inicial: base })
            .then(function (json) {
                pasarAAbierta(json.caja);
            })
            .catch(function (error) {
                mostrarError(error.message);
            })
            .finally(function () {
                abrirBtn.disabled = false;
                abrirBtn.textContent = originalText;
            });
    });

    if (reabrirBtn) {
        reabrirBtn.addEventListener('click', function () {
            var id = reabrirBtn.getAttribute('data-caja-id');
            if (!id) {
                return;
            }

            var originalText = reabrirBtn.textContent;
            reabrirBtn.disabled = true;
            reabrirBtn.textContent = 'Reabriendo...';

            cajaApiRequest('POST', '/cliente/caja/' + id + '/reabrir')
                .then(function (json) {
                    // Esa caja deja de ser un cierre del historial -vuelve a
                    // estar abierta, así que se saca de la tabla/gráfico.
                    var row = document.querySelector('#cajaTableBody .data-table__row[data-cierre-id="' + id + '"]');
                    if (row) {
                        row.remove();
                    }
                    cajaCierres = cajaCierres.filter(function (c) { return String(c.id) !== String(id); });
                    delete cajaCierresById[id];
                    renderDiffChart();
                    actualizarStatCierresSinCuadrar();
                    poblarFiltroMeses();
                    renderHistorialCierres();

                    reabrirBtn.hidden = true;
                    pasarAAbierta(json.caja);
                })
                .catch(function (error) {
                    mostrarError(error.message);
                })
                .finally(function () {
                    reabrirBtn.disabled = false;
                    reabrirBtn.textContent = originalText;
                });
        });
    }

    initCerrarCajaModal(function () { return cajaActual; }, function (cierre) {
        pasarACerrada();

        if (reabrirBtn) {
            reabrirBtn.setAttribute('data-caja-id', cierre.id);
            reabrirBtn.hidden = false;
        }
    });
}

function actualizarStatCierresSinCuadrar() {
    var el = document.getElementById('cajaStatSinCuadrar');
    if (!el) {
        return;
    }
    // "Últimos 6" -mismo recorte que ya usa el gráfico de diferencia. Un
    // cierre queda "sin cuadrar" si falla el efectivo O el digital.
    var sinCuadrar = cajaCierres.slice(0, 6).filter(function (c) { return c.diferencia !== 0 || c.diferenciaDigital !== 0; }).length;
    el.dataset.countupCancelado = '1';
    el.setAttribute('data-count', sinCuadrar);
    el.textContent = formatNumber(sinCuadrar, 0);
}

function initCerrarCajaModal(getCajaActual, onCerrada) {
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
    var esperadoDigitalEl = document.getElementById('cerrarModalEsperadoDigital');
    var conteoDigitalInput = document.getElementById('conteoDigitalInput');
    var diferenciaDigitalBox = document.getElementById('cajaModalDiferenciaDigital');
    var diferenciaDigitalTexto = document.getElementById('cajaModalDiferenciaDigitalTexto');
    var confirmarBtn = document.getElementById('confirmarCierreBtn');

    formatearInputDinero(conteoInput);
    formatearInputDinero(conteoDigitalInput);

    // Misma lógica para el conteo físico y el digital -cada uno con su
    // propio input/caja de diferencia, comparado contra su propio esperado.
    function pintarDiferencia(input, box, texto, esperado) {
        if (!input.value.replace(/\D/g, '')) {
            box.hidden = true;
            return false;
        }

        var conteo = valorDineroInput(input);
        var diferencia = conteo - esperado;
        box.hidden = false;

        if (diferencia === 0) {
            box.className = 'caja-modal-diferencia es-exacto';
            texto.textContent = 'Cuadra exacto con lo esperado.';
        } else if (diferencia > 0) {
            box.className = 'caja-modal-diferencia es-sobrante';
            texto.textContent = 'Sobrante de ' + formatCOP(diferencia);
        } else {
            box.className = 'caja-modal-diferencia es-faltante';
            texto.textContent = 'Faltante de ' + formatCOP(Math.abs(diferencia));
        }

        return true;
    }

    function actualizarDiferencias() {
        var caja = getCajaActual();
        var tieneEfectivo = pintarDiferencia(conteoInput, diferenciaBox, diferenciaTexto, caja.totalEsperado);
        var tieneDigital = pintarDiferencia(conteoDigitalInput, diferenciaDigitalBox, diferenciaDigitalTexto, caja.totalEsperadoDigital);
        confirmarBtn.disabled = !(tieneEfectivo && tieneDigital);
    }

    conteoInput.addEventListener('input', actualizarDiferencias);
    conteoDigitalInput.addEventListener('input', actualizarDiferencias);

    function openModal() {
        var caja = getCajaActual();
        esperadoEl.textContent = formatCOP(caja.totalEsperado);
        esperadoDigitalEl.textContent = formatCOP(caja.totalEsperadoDigital);
        conteoInput.value = '';
        conteoDigitalInput.value = '';
        diferenciaBox.hidden = true;
        diferenciaDigitalBox.hidden = true;
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

    confirmarBtn.addEventListener('click', function () {
        if (confirmarBtn.disabled) {
            return;
        }

        var caja = getCajaActual();
        var conteo = valorDineroInput(conteoInput);
        var conteoDigital = valorDineroInput(conteoDigitalInput);

        var originalText = confirmarBtn.textContent;
        confirmarBtn.disabled = true;
        confirmarBtn.textContent = 'Cerrando...';

        cajaApiRequest('POST', '/cliente/caja/' + caja.id + '/cerrar', { conteo_fisico: conteo, conteo_digital: conteoDigital })
            .then(function (json) {
                var cierre = json.cierre;

                cajaCierres.unshift(cierre);
                cajaCierresById[cierre.id] = cierre;

                var tableBody = document.getElementById('cajaTableBody');
                if (tableBody) {
                    var row = document.createElement('tr');
                    row.className = 'data-table__row';
                    row.setAttribute('data-cierre-id', cierre.id);
                    row.tabIndex = 0;
                    row.innerHTML =
                        '<td><div class="data-table__title">' + cierre.fecha + '</div><div class="data-table__meta">Cerrada ' + cierre.horaCierre + '</div></td>' +
                        '<td class="data-table__meta">' + formatCOP(cierre.baseInicial) + '</td>' +
                        '<td class="data-table__title">' + formatCOP(cierre.totalEsperado) + '</td>' +
                        '<td></td>' +
                        '<td class="data-table__title">' + formatCOP(cierre.totalEsperadoDigital) + '</td>' +
                        '<td></td>';

                    pintarPillDiferencia(row.cells[3], cierre.diferencia);
                    pintarPillDiferencia(row.cells[5], cierre.diferenciaDigital);

                    tableBody.insertBefore(row, tableBody.firstChild);
                    if (window.wireCajaFilaCierre) {
                        window.wireCajaFilaCierre(row);
                    }
                }

                renderDiffChart();
                actualizarStatCierresSinCuadrar();
                poblarFiltroMeses();
                cajaHistorialPagina = 1;
                renderHistorialCierres();

                closeModal();
                onCerrada(cierre);
            })
            .catch(function (error) {
                mostrarError(error.message);
            })
            .finally(function () {
                confirmarBtn.disabled = false;
                confirmarBtn.textContent = originalText;
            });
    });
}
