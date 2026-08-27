/**
 * Stockly — Panel del negocio cliente: vista Ventas (vanilla JS)
 * Depende de cliente/layout.js (formatNumber, formatCOP, normalizarTexto)
 * ya cargado antes que este.
 *
 * initVentasTable -> tabla + panel lateral (slide-over) con búsqueda,
 * filtros y paginación (7 por página). `ventas` no guarda un comprador
 * directo (eso solo se asocia al facturar), así que el detalle acá se
 * enfoca en productos, pago y estado de facturación.
 */

document.addEventListener('DOMContentLoaded', function () {
    initVentasTable();
});

/** Una fila "etiqueta - valor" (mismo look que .slide-over__field), para
 * mostrar cantidad/precio unitario/total de una línea de venta sin meter
 * el valor (texto libre en el caso del nombre) directo en innerHTML. */
function crearCampoDetalleLinea(etiqueta, valor) {
    var field = document.createElement('div');
    field.className = 'slide-over__field';
    var span = document.createElement('span');
    span.textContent = etiqueta;
    var strong = document.createElement('strong');
    strong.textContent = valor;
    field.appendChild(span);
    field.appendChild(strong);
    return field;
}

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

    var overlay = document.getElementById('ventaSlideOverOverlay');
    var slideOver = document.getElementById('ventaSlideOver');
    var closeBtn = document.getElementById('ventaSlideOverClose');

    function openVenta(id) {
        var venta = ventasById[id];
        if (!venta) {
            return;
        }

        document.getElementById('ventaSlideOverTitulo').textContent = 'Venta #' + venta.id;

        var estadoPill = document.getElementById('ventaSlideOverEstadoPago');
        estadoPill.className = 'status-pill status-pill--' + venta.estadoPago;
        estadoPill.textContent = venta.estadoPago === 'pagada' ? 'Pagada' : 'Pendiente';

        document.getElementById('ventaSlideOverMetodo').textContent = venta.metodo === 'efectivo' ? 'Efectivo' : 'Digital (Wompi)';
        document.getElementById('ventaSlideOverTotal').textContent = formatCOP(venta.total);
        document.getElementById('ventaSlideOverGanancia').textContent = formatCOP(venta.ganancia);
        document.getElementById('ventaSlideOverFacturacion').textContent = facturacionLabels[venta.estadoFacturacion];

        var lineasContainer = document.getElementById('ventaSlideOverLineas');
        lineasContainer.innerHTML = '';
        venta.lineas.forEach(function (linea) {
            // linea.nombre es texto libre (nombre de producto) -va por
            // textContent, nunca por innerHTML. Cantidad, precio unitario
            // y total van cada uno en su propia fila "etiqueta - valor",
            // igual que Precio de costo/venta en el panel del producto.
            var wrapper = document.createElement('div');
            wrapper.className = 'compra-linea-producto';

            var nombreEl = document.createElement('div');
            nombreEl.className = 'compra-linea-producto__nombre';
            nombreEl.textContent = linea.nombre;
            wrapper.appendChild(nombreEl);

            wrapper.appendChild(crearCampoDetalleLinea('Cantidad', String(linea.cantidad)));
            wrapper.appendChild(crearCampoDetalleLinea('Precio unitario', formatCOP(linea.precio)));
            wrapper.appendChild(crearCampoDetalleLinea('Total', formatCOP(linea.cantidad * linea.precio)));

            lineasContainer.appendChild(wrapper);
        });

        slideOver.classList.add('is-open');
        slideOver.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-visible');
    }

    function closeSlideOver() {
        slideOver.classList.remove('is-open');
        slideOver.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-visible');
    }

    table.querySelectorAll('.data-table__row').forEach(function (row) {
        var id = parseInt(row.getAttribute('data-venta-id'), 10);

        row.addEventListener('click', function () {
            openVenta(id);
        });

        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openVenta(id);
            }
        });
    });

    closeBtn.addEventListener('click', closeSlideOver);
    overlay.addEventListener('click', closeSlideOver);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && slideOver.classList.contains('is-open')) {
            closeSlideOver();
        }
    });

    /* ---------- Búsqueda + filtros + paginación ----------
     * Los filtros deciden QUÉ filas califican; la paginación decide
     * CUÁLES de esas se ven en pantalla (7 por página). Cambiar un
     * filtro siempre vuelve a la página 1 -si no, podrías quedar en
     * una página que ya no existe para el nuevo resultado.
     * ------------------------------------------------------------------ */
    var searchInput = document.getElementById('ventasSearch');
    var metodoFilter = document.getElementById('ventasMetodoFilter');
    var facturacionFilter = document.getElementById('ventasFacturacionFilter');
    var emptyState = document.getElementById('ventasEmpty');
    var paginationEl = document.getElementById('ventasPagination');
    var pageInfoEl = document.getElementById('ventasPageInfo');
    var prevBtn = document.getElementById('ventasPrevPage');
    var nextBtn = document.getElementById('ventasNextPage');
    var allRows = Array.prototype.slice.call(table.querySelectorAll('.data-table__row'));

    var PAGE_SIZE = 7;
    var currentPage = 1;

    function getMatchingRows() {
        var term = normalizarTexto(searchInput.value.trim());
        var metodo = metodoFilter.value;
        var facturacion = facturacionFilter.value;

        return allRows.filter(function (row) {
            var id = parseInt(row.getAttribute('data-venta-id'), 10);
            var venta = ventasById[id];
            var matchesTerm = !term || String(venta.id).indexOf(term) !== -1;
            var matchesMetodo = !metodo || venta.metodo === metodo;
            var matchesFacturacion = !facturacion || venta.estadoFacturacion === facturacion;
            return matchesTerm && matchesMetodo && matchesFacturacion;
        });
    }

    function render() {
        var matching = getMatchingRows();
        var totalPages = Math.max(1, Math.ceil(matching.length / PAGE_SIZE));
        currentPage = Math.min(currentPage, totalPages);

        var start = (currentPage - 1) * PAGE_SIZE;
        var pageRows = matching.slice(start, start + PAGE_SIZE);

        allRows.forEach(function (row) {
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

    render();
}
