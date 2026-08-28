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

    verTodasBtn.addEventListener('click', function () {
        fechaFilter.value = '';
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
