/**
 * Stockly — Panel del negocio cliente: vista Ventas (vanilla JS)
 * Depende de cliente/layout.js (formatNumber, formatCOP, normalizarTexto)
 * ya cargado antes que este, y de cliente/venta-slide-over.js para el
 * panel de detalle (compartido también con el Dashboard).
 *
 * initVentasTable -> tabla con búsqueda, filtros y paginación (7 por
 * página); al hacer click en una fila abre el panel compartido vía
 * window.abrirVentaSlideOver().
 */

document.addEventListener('DOMContentLoaded', function () {
    initVentasTable();
});

function initVentasTable() {
    var table = document.getElementById('ventasTable');
    var dataScript = document.getElementById('ventasData');

    if (!table || !dataScript) {
        return;
    }

    var ventas = JSON.parse(dataScript.textContent);
    var ventasById = {};
    ventas.forEach(function (venta) {
        ventasById[venta.id] = venta;
    });

    var facturacionLabels = {
        sin_facturar: 'Sin facturar',
        facturada_individual: 'Facturada',
        incluida_en_consolidado: 'En consolidado'
    };

    var facturacionPillClass = {
        sin_facturar: 'status-pill--sin-facturar',
        facturada_individual: 'status-pill--facturada',
        incluida_en_consolidado: 'status-pill--facturada'
    };

    function wireFilaVentaRow(row) {
        var id = parseInt(row.getAttribute('data-venta-id'), 10);

        row.addEventListener('click', function () {
            window.abrirVentaSlideOver(ventasById[id]);
        });

        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                window.abrirVentaSlideOver(ventasById[id]);
            }
        });
    }

    table.querySelectorAll('.data-table__row').forEach(wireFilaVentaRow);

    /* ---------- Búsqueda + filtros + paginación ----------
     * Los filtros deciden QUÉ filas califican; la paginación decide
     * CUÁLES de esas se ven en pantalla (7 por página). Cambiar un
     * filtro siempre vuelve a la página 1 -si no, podrías quedar en
     * una página que ya no existe para el nuevo resultado.
     * ------------------------------------------------------------------ */
    var searchInput = document.getElementById('ventasSearch');
    var metodoFilter = document.getElementById('ventasMetodoFilter');
    var facturacionFilter = document.getElementById('ventasFacturacionFilter');
    var fechaFilter = document.getElementById('ventasFechaFilter');
    var verTodasBtn = document.getElementById('ventasVerTodas');
    var emptyState = document.getElementById('ventasEmpty');
    var paginationEl = document.getElementById('ventasPagination');
    var pageInfoEl = document.getElementById('ventasPageInfo');
    var prevBtn = document.getElementById('ventasPrevPage');
    var nextBtn = document.getElementById('ventasNextPage');

    var PAGE_SIZE = 7;
    var currentPage = 1;

    // Se vuelve a consultar el DOM en cada render (no se guarda una sola
    // vez al inicio) porque registrar una venta nueva agrega una fila
    // después -si se guardara una sola lista al cargar la página, esa
    // fila nueva quedaría fuera de la paginación.
    function getMatchingRows() {
        var term = normalizarTexto(searchInput.value.trim());
        var metodo = metodoFilter.value;
        var facturacion = facturacionFilter.value;
        var fecha = fechaFilter.value;

        return Array.prototype.filter.call(table.querySelectorAll('.data-table__row'), function (row) {
            var id = parseInt(row.getAttribute('data-venta-id'), 10);
            var venta = ventasById[id];
            var matchesTerm = !term || String(venta.id).indexOf(term) !== -1;
            var matchesMetodo = !metodo || venta.metodo === metodo;
            var matchesFacturacion = !facturacion || venta.estadoFacturacion === facturacion;
            // fechaTurno, no fecha -el filtro agrupa por turno de caja
            // (día en que se abrió), no por fecha calendario, para que un
            // turno que cruza la medianoche no se reparta entre dos días.
            var matchesFecha = !fecha || venta.fechaTurno === fecha;
            return matchesTerm && matchesMetodo && matchesFacturacion && matchesFecha;
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
    metodoFilter.addEventListener('change', applyFilters);
    facturacionFilter.addEventListener('change', applyFilters);
    fechaFilter.addEventListener('change', applyFilters);

    var resetFechaPicker = null;

    verTodasBtn.addEventListener('click', function () {
        fechaFilter.value = '';
        if (resetFechaPicker) resetFechaPicker();
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

    // Expuesto para que registrar una venta nueva (nueva-venta-modal.js)
    // pueda agregar su fila a esta tabla sin recargar la página.
    window.agregarVentaALaTabla = function (venta) {
        ventasById[venta.id] = venta;
        ventas.unshift(venta);

        var tbody = table.querySelector('tbody');
        var fila = crearFilaVenta(venta, facturacionLabels, facturacionPillClass);
        wireFilaVentaRow(fila);
        tbody.insertBefore(fila, tbody.firstChild);

        var contadorEl = document.querySelector('.cliente-page-header__date');
        if (contadorEl) {
            contadorEl.textContent = ventas.length + ' ventas registradas';
        }

        render();
    };

    // Expuesto para que venta-slide-over.js avise cuando se anula una
    // venta desde acá -no se quita la fila (para que quede el rastro
    // visible), se marca y su pill de "Estado de pago" pasa a "Anulada".
    window.marcarVentaAnulada = function (venta) {
        ventasById[venta.id] = venta;

        var row = table.querySelector('.data-table__row[data-venta-id="' + venta.id + '"]');
        if (!row) {
            return;
        }

        row.classList.add('venta-fila-anulada');

        var pillPago = row.cells[3].querySelector('.status-pill');
        if (pillPago) {
            pillPago.className = 'status-pill status-pill--sin-facturar';
            pillPago.textContent = 'Anulada';
        }
    };

    render();

    resetFechaPicker = initFechaPicker();
}

/** Crea la fila de tabla para una venta nueva (mismo markup que el Blade). */
function crearFilaVenta(venta, facturacionLabels, facturacionPillClass) {
    var row = document.createElement('tr');
    row.className = 'data-table__row';
    row.setAttribute('data-venta-id', venta.id);
    row.tabIndex = 0;

    var celdaVenta = row.insertCell();
    var titulo = document.createElement('div');
    titulo.className = 'data-table__title';
    titulo.textContent = 'Venta #' + venta.id;
    var meta = document.createElement('div');
    meta.className = 'data-table__meta';
    meta.textContent = venta.hora;
    celdaVenta.appendChild(titulo);
    celdaVenta.appendChild(meta);

    var celdaTotal = row.insertCell();
    celdaTotal.className = 'data-table__title';
    celdaTotal.textContent = formatCOP(venta.total);

    var celdaMetodo = row.insertCell();
    celdaMetodo.className = 'data-table__meta';
    celdaMetodo.textContent = venta.metodo === 'efectivo' ? 'Efectivo' : 'Digital (Wompi)';

    var celdaEstadoPago = row.insertCell();
    var pillPago = document.createElement('span');
    pillPago.className = 'status-pill status-pill--' + venta.estadoPago;
    pillPago.textContent = venta.estadoPago === 'pagada' ? 'Pagada' : 'Pendiente';
    celdaEstadoPago.appendChild(pillPago);

    var celdaFacturacion = row.insertCell();
    var pillFacturacion = document.createElement('span');
    pillFacturacion.className = 'status-pill ' + facturacionPillClass[venta.estadoFacturacion];
    pillFacturacion.textContent = facturacionLabels[venta.estadoFacturacion];
    celdaFacturacion.appendChild(pillFacturacion);

    return row;
}

/**
 * Date picker personalizado para #ventasFechaPickerWrap.
 * Retorna una función resetPicker() para que "Ver todas" limpie la etiqueta.
 * El calendario se posiciona con right:0 (desde CSS) para que su borde
 * derecho quede alineado al borde derecho del botón trigger.
 */
function initFechaPicker() {
    var wrap  = document.getElementById('ventasFechaPickerWrap');
    if (!wrap) return null;

    var btn   = document.getElementById('ventasFechaBtn');
    var lbl   = document.getElementById('ventasFechaLabel');
    var input = document.getElementById('ventasFechaFilter');
    var cal   = document.getElementById('ventasFechaCal');

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
            if (fut)          cls += ' vf-cal__day--future';
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

        // Posición por defecto: borde derecho del cal = borde derecho del btn.
        cal.style.right = '0';
        cal.style.left  = '';
        cal.hidden = false;
        btn.setAttribute('aria-expanded', 'true');

        // Si el calendario se sale por la izquierda, anclarlo al borde izquierdo.
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
