/**
 * Stockly — Panel del negocio cliente: vista Gastos (vanilla JS)
 * Depende de cliente/layout.js (formatCOP, formatNumber, normalizarTexto,
 * formatearInputDinero, valorDineroInput, mostrarError) ya cargado antes.
 *
 * initGastosTable -> tabla con búsqueda/filtros/paginación (mismo patrón
 * que Ventas), initRegistrarGastoModal -> modal con categoría, descripción,
 * responsable, monto y método de pago (mismo toggle de dos pasos que
 * Compras: Efectivo/Digital + De caja/Aparte), initGastoSlideOver -> panel
 * lateral con el detalle al hacer click en una fila.
 */

document.addEventListener('DOMContentLoaded', function () {
    initCountUp();
    initGastosTable();
    initRegistrarGastoModal();
    initGastoSlideOver();
});

/* --------------------------------------------------------------------
 * 0. Contador animado en las stat cards
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

var CATEGORIA_LABELS = { nomina: 'Nómina', arriendo: 'Arriendo', servicios: 'Servicios', otros: 'Otros' };
var METODO_LABELS = { efectivo: 'Efectivo (caja)', efectivo_externo: 'Efectivo (aparte)', digital: 'Digital (de hoy)', digital_externo: 'Digital (aparte)' };

function gastoApiRequest(method, url, data) {
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
 * 1. Tabla: búsqueda + filtros + paginación (7 por página)
 * ------------------------------------------------------------------ */
function initGastosTable() {
    var table = document.getElementById('gastosTable');
    var dataScript = document.getElementById('gastosData');
    if (!table || !dataScript) {
        return;
    }

    var gastos = JSON.parse(dataScript.textContent);
    var gastosById = {};
    gastos.forEach(function (g) { gastosById[g.id] = g; });

    var searchInput = document.getElementById('gastosSearch');
    var categoriaFilter = document.getElementById('gastosCategoriaFilter');
    var metodoFilter = document.getElementById('gastosMetodoFilter');
    var fechaFilter = document.getElementById('gastosFechaFilter');
    var verTodosBtn = document.getElementById('gastosVerTodos');
    var emptyState = document.getElementById('gastosEmpty');
    var paginationEl = document.getElementById('gastosPagination');
    var pageInfoEl = document.getElementById('gastosPageInfo');
    var prevBtn = document.getElementById('gastosPrevPage');
    var nextBtn = document.getElementById('gastosNextPage');
    var statHoyEl = document.getElementById('gastoStatHoy');
    var statMesEl = document.getElementById('gastoStatMes');
    var contadorEl = document.querySelector('.cliente-page-header__date');

    var PAGE_SIZE = 7;
    var currentPage = 1;

    function wireFilaGastoRow(row) {
        var id = parseInt(row.getAttribute('data-gasto-id'), 10);

        row.addEventListener('click', function () {
            if (window.abrirGastoSlideOver) {
                window.abrirGastoSlideOver(gastosById[id]);
            }
        });

        row.addEventListener('keydown', function (event) {
            if ((event.key === 'Enter' || event.key === ' ') && window.abrirGastoSlideOver) {
                event.preventDefault();
                window.abrirGastoSlideOver(gastosById[id]);
            }
        });
    }

    table.querySelectorAll('.data-table__row').forEach(wireFilaGastoRow);

    function getMatchingRows() {
        var term = normalizarTexto(searchInput.value.trim());
        var categoria = categoriaFilter.value;
        var metodo = metodoFilter.value;
        var fecha = fechaFilter.value;

        return Array.prototype.filter.call(table.querySelectorAll('.data-table__row'), function (row) {
            var id = parseInt(row.getAttribute('data-gasto-id'), 10);
            var gasto = gastosById[id];
            var matchesTerm = !term
                || normalizarTexto(gasto.descripcion).indexOf(term) !== -1
                || (gasto.responsable && normalizarTexto(gasto.responsable).indexOf(term) !== -1);
            var matchesCategoria = !categoria || gasto.categoria === categoria;
            var matchesMetodo = !metodo || gasto.metodo === metodo;
            // fechaTurno, no fecha -agrupa por turno de caja (día en que
            // se abrió), no por fecha calendario, mismo criterio que Ventas.
            var matchesFecha = !fecha || gasto.fechaTurno === fecha;
            return matchesTerm && matchesCategoria && matchesMetodo && matchesFecha;
        });
    }

    function render() {
        var matching = getMatchingRows();
        var totalPages = Math.max(1, Math.ceil(matching.length / PAGE_SIZE));
        currentPage = Math.min(currentPage, totalPages);

        var start = (currentPage - 1) * PAGE_SIZE;
        var pageRows = matching.slice(start, start + PAGE_SIZE);

        table.querySelectorAll('.data-table__row').forEach(function (row) {
            row.hidden = pageRows.indexOf(row) === -1;
        });

        emptyState.hidden = matching.length !== 0;
        paginationEl.hidden = matching.length === 0;
        pageInfoEl.textContent = 'Página ' + currentPage + ' de ' + totalPages;
        prevBtn.disabled = currentPage <= 1;
        nextBtn.disabled = currentPage >= totalPages;
    }

    function applyFilters() {
        currentPage = 1;
        render();
    }

    searchInput.addEventListener('input', applyFilters);
    categoriaFilter.addEventListener('change', applyFilters);
    metodoFilter.addEventListener('change', applyFilters);
    fechaFilter.addEventListener('change', applyFilters);

    var resetGastosFechaPicker = null;

    verTodosBtn.addEventListener('click', function () {
        fechaFilter.value = '';
        if (resetGastosFechaPicker) resetGastosFechaPicker();
        applyFilters();
    });

    prevBtn.addEventListener('click', function () {
        if (currentPage > 1) {
            currentPage--;
            render();
        }
    });

    nextBtn.addEventListener('click', function () {
        currentPage++;
        render();
    });

    /** Crea la fila de tabla para un gasto nuevo (mismo markup que el Blade). */
    function crearFilaGasto(gasto) {
        var row = document.createElement('tr');
        row.className = 'data-table__row';
        row.setAttribute('data-gasto-id', gasto.id);
        row.tabIndex = 0;

        var celdaFecha = row.insertCell();
        var tituloFecha = document.createElement('div');
        tituloFecha.className = 'data-table__title';
        tituloFecha.textContent = gasto.fecha;
        var metaFecha = document.createElement('div');
        metaFecha.className = 'data-table__meta';
        metaFecha.textContent = gasto.hora;
        celdaFecha.appendChild(tituloFecha);
        celdaFecha.appendChild(metaFecha);

        var celdaCategoria = row.insertCell();
        var pill = document.createElement('span');
        pill.className = 'status-pill status-pill--sin-facturar';
        pill.textContent = CATEGORIA_LABELS[gasto.categoria];
        celdaCategoria.appendChild(pill);

        // descripcion/responsable son texto libre -van por textContent,
        // nunca directo en innerHTML.
        var celdaDescripcion = row.insertCell();
        celdaDescripcion.className = 'data-table__meta';
        celdaDescripcion.textContent = gasto.descripcion;

        var celdaResponsable = row.insertCell();
        celdaResponsable.className = 'data-table__meta';
        celdaResponsable.textContent = gasto.responsable || '—';

        var celdaMetodo = row.insertCell();
        celdaMetodo.className = 'data-table__meta';
        celdaMetodo.textContent = METODO_LABELS[gasto.metodo];

        var celdaMonto = row.insertCell();
        celdaMonto.className = 'data-table__title';
        celdaMonto.textContent = formatCOP(gasto.monto);

        return row;
    }

    // Expuesto para que "Registrar gasto" agregue su fila sin recargar.
    window.agregarGastoALaTabla = function (gasto) {
        gastosById[gasto.id] = gasto;
        gastos.unshift(gasto);

        var tbody = table.querySelector('tbody');
        var fila = crearFilaGasto(gasto);
        wireFilaGastoRow(fila);
        tbody.insertBefore(fila, tbody.firstChild);

        if (contadorEl) {
            contadorEl.textContent = gastos.length + (gastos.length === 1 ? ' gasto registrado' : ' gastos registrados');
        }

        // Todo gasto que se registra en esta página se registra ahora
        // mismo -siempre suma tanto a "hoy" como al mes actual, sin
        // necesidad de comparar fechas.
        var actualHoy = parseFloat(statHoyEl.getAttribute('data-count')) || 0;
        var actualMes = parseFloat(statMesEl.getAttribute('data-count')) || 0;
        statHoyEl.dataset.countupCancelado = '1';
        statMesEl.dataset.countupCancelado = '1';
        statHoyEl.setAttribute('data-count', actualHoy + gasto.monto);
        statMesEl.setAttribute('data-count', actualMes + gasto.monto);
        statHoyEl.textContent = formatCOP(actualHoy + gasto.monto);
        statMesEl.textContent = formatCOP(actualMes + gasto.monto);

        render();
    };

    render();

    resetGastosFechaPicker = initGastosFechaPicker();
}

/* --------------------------------------------------------------------
 * 2. Modal "Registrar gasto"
 * ------------------------------------------------------------------ */
function initRegistrarGastoModal() {
    var openBtn = document.getElementById('registrarGastoBtn');
    var modal = document.getElementById('registrarGastoModal');
    var overlay = document.getElementById('registrarGastoOverlay');
    if (!openBtn || !modal || !overlay) {
        return;
    }

    var closeBtn = document.getElementById('registrarGastoClose');
    var categoriaSelect = document.getElementById('gastoCategoria');
    var descripcionInput = document.getElementById('gastoDescripcion');
    var responsableInput = document.getElementById('gastoResponsable');
    var montoInput = document.getElementById('gastoMonto');
    var btnEfectivo = document.getElementById('gastoBtnEfectivo');
    var btnDigital = document.getElementById('gastoBtnDigital');
    var btnOrigenHoy = document.getElementById('gastoBtnOrigenHoy');
    var btnOrigenExterno = document.getElementById('gastoBtnOrigenExterno');
    var metodoHint = document.getElementById('gastoMetodoHint');
    var registrarBtn = document.getElementById('gastoRegistrarBtn');

    formatearInputDinero(montoInput);

    var metodo = 'efectivo';
    var origen = 'hoy';

    // Las dos opciones son siempre plata DEL NEGOCIO, nunca personal -la
    // diferencia es solo si salió del cajón/lo digital de HOY (afecta el
    // cierre de caja) o de lo que el negocio ya tenía guardado de antes,
    // como el arriendo pagado desde la cuenta del negocio (sigue siendo un
    // gasto real, solo que no se descuenta del cierre de caja de hoy).
    var METODO_HINTS = {
        efectivo_hoy: 'Sacaste la plata física de la caja del negocio -se descuenta del cierre de caja de hoy.',
        efectivo_externo: 'Plata del negocio, pero no del cajón de hoy (ej: la sacaste de lo guardado de otros días) -sigue siendo un gasto real del negocio, solo que no afecta el cierre de caja de hoy.',
        digital_hoy: 'Pagaste con la plata digital que el negocio recibió hoy (Wompi/transferencia) -se descuenta del total esperado en digital de hoy.',
        digital_externo: 'Pagaste por transferencia desde la cuenta del negocio, pero no con lo digital de hoy (ej: el arriendo) -sigue siendo un gasto real del negocio, solo que no afecta el cierre de caja de hoy.'
    };

    function metodoPagoFinal() {
        return origen === 'hoy' ? metodo : metodo + '_externo';
    }

    function actualizarHint() {
        metodoHint.textContent = METODO_HINTS[metodo + '_' + origen];
    }

    function setMetodo(nuevo) {
        metodo = nuevo;
        btnEfectivo.classList.toggle('is-active', nuevo === 'efectivo');
        btnDigital.classList.toggle('is-active', nuevo === 'digital');
        actualizarHint();
    }

    function setOrigen(nuevo) {
        origen = nuevo;
        btnOrigenHoy.classList.toggle('is-active', nuevo === 'hoy');
        btnOrigenExterno.classList.toggle('is-active', nuevo === 'externo');
        actualizarHint();
    }

    btnEfectivo.addEventListener('click', function () { setMetodo('efectivo'); });
    btnDigital.addEventListener('click', function () { setMetodo('digital'); });
    btnOrigenHoy.addEventListener('click', function () { setOrigen('hoy'); });
    btnOrigenExterno.addEventListener('click', function () { setOrigen('externo'); });

    function actualizarBotonHabilitado() {
        registrarBtn.disabled = !(descripcionInput.value.trim() && valorDineroInput(montoInput) > 0);
    }

    descripcionInput.addEventListener('input', actualizarBotonHabilitado);
    montoInput.addEventListener('input', actualizarBotonHabilitado);

    function resetModal() {
        categoriaSelect.selectedIndex = 0;
        descripcionInput.value = '';
        responsableInput.value = '';
        montoInput.value = '';
        setMetodo('efectivo');
        setOrigen('hoy');
        registrarBtn.disabled = true;
    }

    function openModal() {
        resetModal();
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-visible');
        window.setTimeout(function () { descripcionInput.focus(); }, 250);
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

    registrarBtn.addEventListener('click', function () {
        if (registrarBtn.disabled) {
            return;
        }

        var originalText = registrarBtn.textContent;
        registrarBtn.disabled = true;
        registrarBtn.textContent = 'Registrando...';

        var payload = {
            categoria: categoriaSelect.value,
            descripcion: descripcionInput.value.trim(),
            responsable: responsableInput.value.trim() || null,
            monto: valorDineroInput(montoInput),
            metodo_pago: metodoPagoFinal()
        };

        gastoApiRequest('POST', '/cliente/gastos', payload)
            .then(function (json) {
                if (window.agregarGastoALaTabla) {
                    window.agregarGastoALaTabla(json.gasto);
                }
                closeModal();
            })
            .catch(function (error) {
                mostrarError(error.message);
            })
            .finally(function () {
                registrarBtn.disabled = false;
                registrarBtn.textContent = originalText;
            });
    });
}

/* --------------------------------------------------------------------
 * 3. Panel lateral con el detalle de un gasto
 * ------------------------------------------------------------------ */
function initGastoSlideOver() {
    var overlay = document.getElementById('gastoSlideOverOverlay');
    var slideOver = document.getElementById('gastoSlideOver');
    var closeBtn = document.getElementById('gastoSlideOverClose');
    if (!overlay || !slideOver || !closeBtn) {
        return;
    }

    function cerrar() {
        slideOver.classList.remove('is-open');
        slideOver.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-visible');
    }

    window.abrirGastoSlideOver = function (gasto) {
        if (!gasto) {
            return;
        }

        document.getElementById('gastoSlideOverTitulo').textContent = 'Gasto #' + gasto.id;
        document.getElementById('gastoSlideOverCategoria').textContent = CATEGORIA_LABELS[gasto.categoria];

        // descripcion/responsable/registradoPor son texto libre -van por
        // textContent, nunca directo en innerHTML.
        document.getElementById('gastoSlideOverDescripcion').textContent = gasto.descripcion;
        document.getElementById('gastoSlideOverResponsable').textContent = gasto.responsable || '—';
        document.getElementById('gastoSlideOverMetodo').textContent = METODO_LABELS[gasto.metodo];
        document.getElementById('gastoSlideOverMonto').textContent = formatCOP(gasto.monto);
        document.getElementById('gastoSlideOverFecha').textContent = gasto.fecha + ', ' + gasto.hora;
        document.getElementById('gastoSlideOverRegistradoPor').textContent = gasto.registradoPor;

        slideOver.classList.add('is-open');
        slideOver.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-visible');
    };

    closeBtn.addEventListener('click', cerrar);
    overlay.addEventListener('click', cerrar);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && slideOver.classList.contains('is-open')) {
            cerrar();
        }
    });
}

/* --------------------------------------------------------------------
 * 4. Date picker personalizado para el filtro de fecha
 *    Mismo comportamiento que el de Ventas: right:0 alinea el borde
 *    derecho del calendario con el borde del botón; si desborda por la
 *    izquierda, cambia a left:0 automáticamente.
 * ------------------------------------------------------------------ */
function initGastosFechaPicker() {
    var wrap  = document.getElementById('gastosFechaPickerWrap');
    if (!wrap) return null;

    var btn   = document.getElementById('gastosFechaBtn');
    var lbl   = document.getElementById('gastosFechaLabel');
    var input = document.getElementById('gastosFechaFilter');
    var cal   = document.getElementById('gastosFechaCal');

    var MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio',
                 'Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    var DIAS  = ['Do','Lu','Ma','Mi','Ju','Vi','Sa'];
    var viewY, viewM;

    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function toISO(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function parseISO(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
    function fmtLabel(s) {
        if (!s) return 'Cualquier fecha';
        var p = s.split('-'); return p[2] + '/' + p[1] + '/' + p[0];
    }

    lbl.textContent = fmtLabel(input.value);

    function render() {
        var todayStr = toISO(new Date());
        var selStr   = input.value;
        var now      = new Date();
        var firstDow    = new Date(viewY, viewM, 1).getDay();
        var daysInMonth = new Date(viewY, viewM + 1, 0).getDate();
        var canNext     = !(viewY > now.getFullYear() ||
                           (viewY === now.getFullYear() && viewM >= now.getMonth()));

        var h = '<div class="vf-cal__header">' +
            '<button type="button" class="vf-cal__nav" data-dir="-1" aria-label="Mes anterior">' +
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg></button>' +
            '<span class="vf-cal__title">' + MESES[viewM] + ' ' + viewY + '</span>' +
            '<button type="button" class="vf-cal__nav" data-dir="1" aria-label="Mes siguiente"' + (canNext ? '' : ' disabled') + '>' +
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>' +
            '</div><div class="vf-cal__grid">';

        DIAS.forEach(function (d) { h += '<span class="vf-cal__dow">' + d + '</span>'; });
        for (var i = 0; i < firstDow; i++) { h += '<span class="vf-cal__empty"></span>'; }
        for (var day = 1; day <= daysInMonth; day++) {
            var ds  = viewY + '-' + pad(viewM + 1) + '-' + pad(day);
            var cls = 'vf-cal__day';
            var fut = ds > todayStr;
            if (fut)             cls += ' vf-cal__day--future';
            if (ds === todayStr) cls += ' vf-cal__day--today';
            if (ds === selStr)   cls += ' vf-cal__day--selected';
            h += '<button type="button" class="' + cls + '" data-date="' + ds + '"' + (fut ? ' disabled' : '') + '>' + day + '</button>';
        }
        h += '</div>';
        cal.innerHTML = h;

        cal.querySelectorAll('.vf-cal__nav').forEach(function (nb) {
            nb.addEventListener('click', function (e) {
                e.stopPropagation();
                var dir = parseInt(this.getAttribute('data-dir'));
                viewM += dir;
                if (viewM < 0)  { viewM = 11; viewY--; }
                if (viewM > 11) { viewM = 0;  viewY++; }
                render();
            });
        });
        cal.querySelectorAll('.vf-cal__day:not([disabled])').forEach(function (db) {
            db.addEventListener('click', function (e) {
                e.stopPropagation();
                pick(this.getAttribute('data-date'));
            });
        });
    }

    function pick(ds) {
        input.value = ds;
        lbl.textContent = fmtLabel(ds);
        close();
        input.dispatchEvent(new Event('change'));
    }

    function open() {
        var base = input.value ? parseISO(input.value) : new Date();
        viewY = base.getFullYear();
        viewM = base.getMonth();
        render();
        cal.style.right = '0';
        cal.style.left  = '';
        cal.hidden = false;
        btn.setAttribute('aria-expanded', 'true');
        if (cal.getBoundingClientRect().left < 8) {
            cal.style.right = '';
            cal.style.left  = '0';
        }
    }

    function close() {
        cal.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
    }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        cal.hidden ? open() : close();
    });
    document.addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    cal.addEventListener('click', function (e) { e.stopPropagation(); });

    return function resetPicker() {
        input.value = '';
        lbl.textContent = 'Cualquier fecha';
    };
}
