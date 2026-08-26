/**
 * Stockly — Panel del negocio cliente: vista Inventario (vanilla JS)
 * Depende de cliente/layout.js (formatCOP, normalizarTexto) ya cargado
 * antes que este.
 *
 * Módulos:
 *   1. initCountUp             -> anima los números de las stat cards
 *   2. initInventarioTabs      -> pestañas Vitrina / Bodega / Compras
 *   3. initVitrinaTable        -> búsqueda/filtro + slide-over de producto
 *   4. initBodegaTable         -> búsqueda/filtro + botón "Transferir"
 *   5. initComprasTable        -> búsqueda/filtro + slide-over de compra
 *   6. initNuevoProductoModal  -> crear o editar un producto del catálogo
 *   7. initRegistrarCompraModal -> compra a proveedor (con validación de
 *      factura) o informal -se agrega al historial y siempre suma a
 *      bodega, nunca a vitrina
 *   8. initTransferirModal     -> mover stock de bodega a vitrina (única
 *      acción que sí actualiza el stock mostrado, para dar feedback real)
 */

document.addEventListener('DOMContentLoaded', function () {
    initCountUp();
    initInventarioTabs();
    initVitrinaTable();
    initBodegaTable();
    initComprasTable();
    initNuevoProductoModal();
    initRegistrarCompraModal();
    initTransferirModal();
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
 * Estado compartido: catálogo de productos y compras, ambos mutables
 * -Nuevo/Editar producto, Registrar compra y Transferir los actualizan
 * en memoria (sin backend, se pierde al recargar la página).
 * ------------------------------------------------------------------ */
var inventarioProductos = [];
var inventarioProductosById = {};
var inventarioCompras = [];
var inventarioComprasById = {};

function cargarInventarioData() {
    var productosScript = document.getElementById('inventarioProductosData');
    var comprasScript = document.getElementById('inventarioComprasData');
    if (!productosScript || !comprasScript) {
        return false;
    }

    inventarioProductos = JSON.parse(productosScript.textContent);
    inventarioProductos.forEach(function (p) { inventarioProductosById[p.id] = p; });

    inventarioCompras = JSON.parse(comprasScript.textContent);
    inventarioCompras.forEach(function (c) { inventarioComprasById[c.id] = c; });

    return true;
}
cargarInventarioData();

/** Pequeño helper para las llamadas al backend real de Inventario -incluye
 * el token CSRF (meta tag en cliente-layout) y convierte una respuesta con
 * error HTTP en un Error con el mensaje que mandó el servidor. */
function inventarioApiRequest(method, url, data) {
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
 * 2. Pestañas
 * ------------------------------------------------------------------ */
function initInventarioTabs() {
    var tabs = document.querySelectorAll('.inventario-tab');
    var panels = document.querySelectorAll('.inventario-tab-panel');
    if (tabs.length === 0) {
        return;
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = tab.getAttribute('data-tab');

            tabs.forEach(function (t) { t.classList.toggle('is-active', t === tab); });
            panels.forEach(function (panel) {
                panel.hidden = panel.getAttribute('data-tab-panel') !== target;
            });
        });
    });
}

/* --------------------------------------------------------------------
 * 3. Vitrina: búsqueda + filtro de categoría + slide-over de producto
 * ------------------------------------------------------------------ */
function initVitrinaTable() {
    var table = document.getElementById('vitrinaTable');
    if (!table) {
        return;
    }

    var searchInput = document.getElementById('vitrinaSearch');
    var categoriaFilter = document.getElementById('vitrinaCategoriaFilter');
    var emptyState = document.getElementById('vitrinaEmpty');

    function render() {
        var term = normalizarTexto(searchInput.value.trim());
        var categoria = categoriaFilter.value;
        var visibleCount = 0;

        table.querySelectorAll('.data-table__row').forEach(function (row) {
            var id = parseInt(row.getAttribute('data-producto-id'), 10);
            var producto = inventarioProductosById[id];
            var matchesTerm = !term || normalizarTexto(producto.nombre).indexOf(term) !== -1;
            var matchesCategoria = !categoria || producto.categoria === categoria;
            var visible = matchesTerm && matchesCategoria;
            row.hidden = !visible;
            if (visible) {
                visibleCount++;
            }
        });

        emptyState.hidden = visibleCount !== 0;
    }

    searchInput.addEventListener('input', render);
    categoriaFilter.addEventListener('change', render);

    table.querySelectorAll('.data-table__row').forEach(function (row) {
        var id = parseInt(row.getAttribute('data-producto-id'), 10);
        row.addEventListener('click', function () { abrirProductoSlideOver(id); });
        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                abrirProductoSlideOver(id);
            }
        });
    });
}

/* --------------------------------------------------------------------
 * 4. Bodega: búsqueda + filtro de categoría + botón "Transferir"
 * ------------------------------------------------------------------ */
function initBodegaTable() {
    var table = document.getElementById('bodegaTable');
    if (!table) {
        return;
    }

    var searchInput = document.getElementById('bodegaSearch');
    var categoriaFilter = document.getElementById('bodegaCategoriaFilter');
    var emptyState = document.getElementById('bodegaEmpty');

    function render() {
        var term = normalizarTexto(searchInput.value.trim());
        var categoria = categoriaFilter.value;
        var visibleCount = 0;

        table.querySelectorAll('.data-table__row').forEach(function (row) {
            var id = parseInt(row.getAttribute('data-producto-id'), 10);
            var producto = inventarioProductosById[id];
            var matchesTerm = !term || normalizarTexto(producto.nombre).indexOf(term) !== -1;
            var matchesCategoria = !categoria || producto.categoria === categoria;
            var visible = matchesTerm && matchesCategoria;
            row.hidden = !visible;
            if (visible) {
                visibleCount++;
            }
        });

        emptyState.hidden = visibleCount !== 0;
    }

    searchInput.addEventListener('input', render);
    categoriaFilter.addEventListener('change', render);

    table.querySelectorAll('.data-table__row').forEach(function (row) {
        var id = parseInt(row.getAttribute('data-producto-id'), 10);
        row.addEventListener('click', function () { abrirProductoSlideOver(id); });
        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                abrirProductoSlideOver(id);
            }
        });
    });

    table.querySelectorAll('.inventario-transfer-btn').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.stopPropagation();
            var id = parseInt(btn.getAttribute('data-producto-id'), 10);
            abrirTransferirModal(id);
        });
    });
}

/* --------------------------------------------------------------------
 * 5. Compras: búsqueda + filtro de estado de factura + slide-over
 * ------------------------------------------------------------------ */
function initComprasTable() {
    var table = document.getElementById('comprasTable');
    if (!table) {
        return;
    }

    var searchInput = document.getElementById('comprasSearch');
    var estadoFilter = document.getElementById('comprasEstadoFilter');
    var emptyState = document.getElementById('comprasEmpty');

    var facturaLabels = { validada: 'Validada', por_validar: 'Por validar', sin_factura: 'Sin factura' };

    function render() {
        var term = normalizarTexto(searchInput.value.trim());
        var estado = estadoFilter.value;
        var visibleCount = 0;

        table.querySelectorAll('.data-table__row').forEach(function (row) {
            var id = parseInt(row.getAttribute('data-compra-id'), 10);
            var compra = inventarioComprasById[id];
            var proveedorTexto = compra.proveedor || 'compra informal';
            var matchesTerm = !term || normalizarTexto(proveedorTexto).indexOf(term) !== -1;
            var matchesEstado = !estado || compra.facturaEstado === estado;
            var visible = matchesTerm && matchesEstado;
            row.hidden = !visible;
            if (visible) {
                visibleCount++;
            }
        });

        emptyState.hidden = visibleCount !== 0;
    }

    searchInput.addEventListener('input', render);
    estadoFilter.addEventListener('change', render);

    wireFilaCompra(table);
}

/* --------------------------------------------------------------------
 * Slide-over de compra + creación de filas nuevas (usado al listar y al
 * registrar una compra nueva).
 * ------------------------------------------------------------------ */
var COMPRA_FACTURA_LABELS = { validada: 'Validada', por_validar: 'Por validar', sin_factura: 'Sin factura' };
var COMPRA_FACTURA_PILL_CLASS = { validada: 'status-pill--facturada', por_validar: 'status-pill--pendiente', sin_factura: 'status-pill--sin-facturar' };

function wireFilaCompraRow(row) {
    var id = parseInt(row.getAttribute('data-compra-id'), 10);
    row.addEventListener('click', function () { abrirCompraSlideOver(id); });
    row.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            abrirCompraSlideOver(id);
        }
    });
}

function wireFilaCompra(scopeEl) {
    scopeEl.querySelectorAll('.data-table__row').forEach(wireFilaCompraRow);
}

function abrirCompraSlideOver(id) {
    var compra = inventarioComprasById[id];
    var overlay = document.getElementById('compraSlideOverOverlay');
    var slideOver = document.getElementById('compraSlideOver');
    if (!compra || !overlay || !slideOver) {
        return;
    }

    document.getElementById('compraSlideOverTitulo').textContent = 'Compra #' + compra.id;

    var estadoPill = document.getElementById('compraSlideOverEstado');
    estadoPill.className = 'status-pill ' + COMPRA_FACTURA_PILL_CLASS[compra.facturaEstado];
    estadoPill.textContent = COMPRA_FACTURA_LABELS[compra.facturaEstado];

    document.getElementById('compraSlideOverOrigen').textContent = compra.proveedor || 'Compra informal (sin proveedor)';
    document.getElementById('compraSlideOverTotal').textContent = formatCOP(compra.total);

    var cufeRow = document.getElementById('compraSlideOverCufeRow');
    if (compra.cufe) {
        cufeRow.hidden = false;
        document.getElementById('compraSlideOverCufe').textContent = compra.cufe;
    } else {
        cufeRow.hidden = true;
    }

    var lineasContainer = document.getElementById('compraSlideOverLineas');
    lineasContainer.innerHTML = '';
    compra.lineas.forEach(function (linea) {
        var item = document.createElement('div');
        item.className = 'venta-detalle-item';
        item.innerHTML =
            '<div>' +
                '<div class="venta-detalle-item__nombre">' + linea.nombre + '</div>' +
                '<div class="venta-detalle-item__cantidad">' + linea.cantidad + ' x ' + formatCOP(linea.costo) + '</div>' +
            '</div>' +
            '<div class="venta-detalle-item__monto">' + formatCOP(linea.cantidad * linea.costo) + '</div>';
        lineasContainer.appendChild(item);
    });

    slideOver.classList.add('is-open');
    slideOver.setAttribute('aria-hidden', 'false');
    overlay.classList.add('is-visible');
}

function cerrarCompraSlideOver() {
    var overlay = document.getElementById('compraSlideOverOverlay');
    var slideOver = document.getElementById('compraSlideOver');
    slideOver.classList.remove('is-open');
    slideOver.setAttribute('aria-hidden', 'true');
    overlay.classList.remove('is-visible');
}

(function wireCompraSlideOverClose() {
    var overlay = document.getElementById('compraSlideOverOverlay');
    var slideOver = document.getElementById('compraSlideOver');
    var closeBtn = document.getElementById('compraSlideOverClose');
    if (!overlay || !slideOver || !closeBtn) {
        return;
    }
    closeBtn.addEventListener('click', cerrarCompraSlideOver);
    overlay.addEventListener('click', cerrarCompraSlideOver);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && slideOver.classList.contains('is-open')) {
            cerrarCompraSlideOver();
        }
    });
})();

/** Crea la fila de tabla para una compra nueva (mismo markup que el Blade). */
function crearFilaCompra(compra) {
    var row = document.createElement('tr');
    row.className = 'data-table__row';
    row.setAttribute('data-compra-id', compra.id);
    row.tabIndex = 0;

    var proveedorTexto = compra.proveedor || 'Compra informal';

    row.innerHTML =
        '<td class="data-table__meta">' + compra.fecha + '</td>' +
        '<td><div class="data-table__title">' + proveedorTexto + '</div></td>' +
        '<td class="data-table__meta">' + compra.lineas.length + ' producto' + (compra.lineas.length === 1 ? '' : 's') + '</td>' +
        '<td class="data-table__title">' + formatCOP(compra.total) + '</td>' +
        '<td><span class="status-pill ' + COMPRA_FACTURA_PILL_CLASS[compra.facturaEstado] + '">' + COMPRA_FACTURA_LABELS[compra.facturaEstado] + '</span></td>';

    return row;
}

/* --------------------------------------------------------------------
 * Refresca nombre/categoría/precios/stock de un producto en TODAS las
 * tablas donde aparece, y en el slide-over si está abierto mostrándolo
 * -usado por Transferir, Editar producto y Registrar compra.
 * ------------------------------------------------------------------ */
function actualizarFilaProducto(producto) {
    var vitrinaRow = document.querySelector('#vitrinaTable .data-table__row[data-producto-id="' + producto.id + '"]');
    if (vitrinaRow) {
        vitrinaRow.querySelector('.data-table__title').firstChild.textContent = producto.nombre + ' ';
        vitrinaRow.querySelector('.data-table__meta').textContent = producto.unidad;
        vitrinaRow.cells[1].textContent = producto.categoria;
        vitrinaRow.cells[2].textContent = formatCOP(producto.precioVenta);
        vitrinaRow.cells[3].textContent = producto.stockVitrina;
    }

    var bodegaRow = document.querySelector('#bodegaTable .data-table__row[data-producto-id="' + producto.id + '"]');
    if (bodegaRow) {
        bodegaRow.querySelector('.data-table__title').firstChild.textContent = producto.nombre + ' ';
        bodegaRow.querySelector('.data-table__meta').textContent = producto.unidad;
        bodegaRow.cells[1].textContent = producto.categoria;
        bodegaRow.cells[2].textContent = formatCOP(producto.precioCosto);
        bodegaRow.cells[3].textContent = producto.stockBodega;
    }

    var slideOver = document.getElementById('productoSlideOver');
    if (slideOver.dataset.productoId === String(producto.id)) {
        document.getElementById('productoSlideOverTitulo').textContent = producto.nombre;
        document.getElementById('productoSlideOverCategoria').textContent = producto.categoria;
        document.getElementById('productoSlideOverPrecioCosto').textContent = formatCOP(producto.precioCosto);
        document.getElementById('productoSlideOverPrecioVenta').textContent = formatCOP(producto.precioVenta);
        document.getElementById('productoSlideOverUnidad').textContent = producto.unidad;
        document.getElementById('productoSlideOverStockVitrina').textContent = producto.stockVitrina;
        document.getElementById('productoSlideOverStockBodega').textContent = producto.stockBodega;
    }
}

/* --------------------------------------------------------------------
 * Slide-over de producto (compartido entre Vitrina y Bodega)
 * ------------------------------------------------------------------ */
function abrirProductoSlideOver(id) {
    var producto = inventarioProductosById[id];
    var overlay = document.getElementById('productoSlideOverOverlay');
    var slideOver = document.getElementById('productoSlideOver');
    if (!producto || !overlay || !slideOver) {
        return;
    }

    slideOver.dataset.productoId = String(id);

    document.getElementById('productoSlideOverTitulo').textContent = producto.nombre;
    document.getElementById('productoSlideOverCategoria').textContent = producto.categoria;
    document.getElementById('productoSlideOverPrecioCosto').textContent = formatCOP(producto.precioCosto);
    document.getElementById('productoSlideOverPrecioVenta').textContent = formatCOP(producto.precioVenta);
    document.getElementById('productoSlideOverUnidad').textContent = producto.unidad;
    document.getElementById('productoSlideOverStockVitrina').textContent = producto.stockVitrina;
    document.getElementById('productoSlideOverStockBodega').textContent = producto.stockBodega;

    var transferirBtn = document.getElementById('productoSlideOverTransferirBtn');
    transferirBtn.onclick = function () { abrirTransferirModal(id); };

    var editarBtn = document.getElementById('productoSlideOverEditarBtn');
    editarBtn.onclick = function () {
        cerrarProductoSlideOver();
        window.abrirEditarProducto(id);
    };

    slideOver.classList.add('is-open');
    slideOver.setAttribute('aria-hidden', 'false');
    overlay.classList.add('is-visible');
}

function cerrarProductoSlideOver() {
    var overlay = document.getElementById('productoSlideOverOverlay');
    var slideOver = document.getElementById('productoSlideOver');
    slideOver.classList.remove('is-open');
    slideOver.setAttribute('aria-hidden', 'true');
    overlay.classList.remove('is-visible');
}

(function wireProductoSlideOverClose() {
    var overlay = document.getElementById('productoSlideOverOverlay');
    var slideOver = document.getElementById('productoSlideOver');
    var closeBtn = document.getElementById('productoSlideOverClose');
    if (!overlay || !slideOver || !closeBtn) {
        return;
    }
    closeBtn.addEventListener('click', cerrarProductoSlideOver);
    overlay.addEventListener('click', cerrarProductoSlideOver);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && slideOver.classList.contains('is-open')) {
            cerrarProductoSlideOver();
        }
    });
})();

/* --------------------------------------------------------------------
 * Filas nuevas de Vitrina/Bodega (mismo markup que el Blade) -usadas al
 * crear un producto nuevo desde el modal.
 * ------------------------------------------------------------------ */
function wireFilaProductoClick(row, id) {
    row.addEventListener('click', function () { abrirProductoSlideOver(id); });
    row.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            abrirProductoSlideOver(id);
        }
    });
}

function crearFilaVitrina(producto) {
    var row = document.createElement('tr');
    row.className = 'data-table__row';
    row.setAttribute('data-producto-id', producto.id);
    row.tabIndex = 0;
    row.innerHTML =
        '<td><div class="data-table__title">' + producto.nombre + '</div><div class="data-table__meta">' + producto.unidad + '</div></td>' +
        '<td class="data-table__meta">' + producto.categoria + '</td>' +
        '<td class="data-table__title">' + formatCOP(producto.precioVenta) + '</td>' +
        '<td class="data-table__meta">' + producto.stockVitrina + '</td>';
    wireFilaProductoClick(row, producto.id);
    return row;
}

function crearFilaBodega(producto) {
    var row = document.createElement('tr');
    row.className = 'data-table__row';
    row.setAttribute('data-producto-id', producto.id);
    row.tabIndex = 0;
    row.innerHTML =
        '<td><div class="data-table__title">' + producto.nombre + '</div><div class="data-table__meta">' + producto.unidad + '</div></td>' +
        '<td class="data-table__meta">' + producto.categoria + '</td>' +
        '<td class="data-table__title">' + formatCOP(producto.precioCosto) + '</td>' +
        '<td class="data-table__meta">' + producto.stockBodega + '</td>' +
        '<td><button type="button" class="inventario-transfer-btn" data-producto-id="' + producto.id + '">Transferir</button></td>';
    wireFilaProductoClick(row, producto.id);
    row.querySelector('.inventario-transfer-btn').addEventListener('click', function (event) {
        event.stopPropagation();
        abrirTransferirModal(producto.id);
    });
    return row;
}

/* --------------------------------------------------------------------
 * Stat cards: recalculadas (sin repetir la animación) cada vez que se
 * crea/edita un producto o se registra una compra.
 * ------------------------------------------------------------------ */
function actualizarStatProductos() {
    var statProductos = document.getElementById('statProductos');
    var statCategoriasMeta = document.getElementById('statCategoriasMeta');
    if (statProductos) {
        statProductos.textContent = formatNumber(inventarioProductos.length, 0);
    }
    if (statCategoriasMeta) {
        var categoriasUnicas = {};
        inventarioProductos.forEach(function (p) { categoriasUnicas[p.categoria] = true; });
        var count = Object.keys(categoriasUnicas).length;
        statCategoriasMeta.textContent = count + ' categoría' + (count === 1 ? '' : 's');
    }
}

function actualizarStatsValorInventario() {
    var statValorBodega = document.getElementById('statValorBodega');
    var statValorVitrina = document.getElementById('statValorVitrina');
    var statValorTotal = document.getElementById('statValorTotal');
    if (!statValorBodega || !statValorVitrina) {
        return;
    }
    var valorBodega = inventarioProductos.reduce(function (sum, p) { return sum + p.stockBodega * p.precioCosto; }, 0);
    var valorVitrina = inventarioProductos.reduce(function (sum, p) { return sum + p.stockVitrina * p.precioCosto; }, 0);
    statValorBodega.textContent = formatCOP(valorBodega);
    statValorVitrina.textContent = formatCOP(valorVitrina);
    if (statValorTotal) {
        statValorTotal.textContent = formatCOP(valorBodega + valorVitrina);
    }
}

/** Una compra recién registrada siempre es "de este mes" -no hace falta
 * volver a escanear fechas ya formateadas por el servidor, basta con
 * sumar uno al contador que ya trajo la carga inicial de la página. */
function incrementarStatComprasMes() {
    var statComprasMes = document.getElementById('statComprasMes');
    if (!statComprasMes) {
        return;
    }
    var actual = parseInt(statComprasMes.textContent, 10) || 0;
    statComprasMes.textContent = formatNumber(actual + 1, 0);
}

/* --------------------------------------------------------------------
 * 6. Modal "Nuevo producto" / "Editar producto" -mismo modal, dos modos.
 * Crear sí agrega el producto al catálogo (fila nueva, stock en 0).
 * Editar actualiza nombre/categoría/precios/unidad del producto y
 * refresca todo lo que dependa de él (filas, slide-over, advertencias).
 * ------------------------------------------------------------------ */
function initNuevoProductoModal() {
    var openBtn = document.getElementById('nuevoProductoBtn');
    var modal = document.getElementById('nuevoProductoModal');
    var overlay = document.getElementById('nuevoProductoOverlay');
    if (!openBtn || !modal || !overlay) {
        return;
    }

    var closeBtn = document.getElementById('nuevoProductoClose');
    var titleEl = document.getElementById('nuevoProductoTitle');
    var nombreInput = document.getElementById('prodNombre');
    var costoInput = document.getElementById('prodPrecioCosto');
    var ventaInput = document.getElementById('prodPrecioVenta');
    var unidadSelect = document.getElementById('prodUnidad');
    var guardarBtn = document.getElementById('prodGuardarBtn');
    var categoriaSelect = document.getElementById('prodCategoria');
    var nuevaCategoriaRow = document.getElementById('nuevaCategoriaRow');
    var nuevaCategoriaInput = document.getElementById('nuevaCategoriaInput');
    var nuevaCategoriaConfirmar = document.getElementById('nuevaCategoriaConfirmar');
    var nuevaCategoriaCancelar = document.getElementById('nuevaCategoriaCancelar');
    var nuevaUnidadRow = document.getElementById('nuevaUnidadRow');
    var nuevaUnidadInput = document.getElementById('nuevaUnidadInput');
    var nuevaUnidadConfirmar = document.getElementById('nuevaUnidadConfirmar');
    var nuevaUnidadCancelar = document.getElementById('nuevaUnidadCancelar');

    // null = modo "crear"; con un id = modo "editar" ese producto.
    var productoEditandoId = null;

    formatearInputDinero(costoInput);
    formatearInputDinero(ventaInput);

    function updateGuardarState() {
        var valido = nombreInput.value.trim() !== '' && costoInput.value !== '' && ventaInput.value !== '';
        guardarBtn.disabled = !valido;
    }

    [nombreInput, costoInput, ventaInput].forEach(function (input) {
        input.addEventListener('input', updateGuardarState);
    });

    /* ---------- Agregar categoría al vuelo ----------
     * "+ Agregar categoría..." es una opción especial (no una categoría
     * real) al final del selector. Elegirla abre un campo chiquito para
     * escribir el nombre; al confirmar, se inserta como opción real
     * antes del sentinel y también se agrega a los filtros de Vitrina y
     * Bodega, para que quede disponible en todo el panel. */
    categoriaSelect.addEventListener('change', function () {
        if (categoriaSelect.value === '__nueva__') {
            nuevaCategoriaRow.hidden = false;
            nuevaCategoriaInput.value = '';
            nuevaCategoriaInput.focus();
        }
    });

    function cancelarNuevaCategoria() {
        nuevaCategoriaRow.hidden = true;
        categoriaSelect.selectedIndex = 0;
    }

    nuevaCategoriaCancelar.addEventListener('click', cancelarNuevaCategoria);

    nuevaCategoriaInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            nuevaCategoriaConfirmar.click();
        }
    });

    nuevaCategoriaConfirmar.addEventListener('click', function () {
        var nombre = nuevaCategoriaInput.value.trim();
        if (!nombre) {
            nuevaCategoriaInput.focus();
            return;
        }

        var yaExiste = Array.prototype.some.call(categoriaSelect.options, function (opt) {
            return opt.value.toLowerCase() === nombre.toLowerCase();
        });
        if (yaExiste) {
            nuevaCategoriaInput.setCustomValidity('Ya existe una categoría con ese nombre.');
            nuevaCategoriaInput.reportValidity();
            nuevaCategoriaInput.focus();
            return;
        }
        nuevaCategoriaInput.setCustomValidity('');

        var originalText = nuevaCategoriaConfirmar.textContent;
        nuevaCategoriaConfirmar.disabled = true;
        nuevaCategoriaConfirmar.textContent = 'Agregando...';

        inventarioApiRequest('POST', '/cliente/inventario/categorias', { nombre: nombre })
            .then(function (json) {
                var nombreCreado = json.categoria;
                var sentinelOption = categoriaSelect.querySelector('option[value="__nueva__"]');
                categoriaSelect.insertBefore(new Option(nombreCreado, nombreCreado), sentinelOption);
                categoriaSelect.value = nombreCreado;

                [document.getElementById('vitrinaCategoriaFilter'), document.getElementById('bodegaCategoriaFilter')].forEach(function (filterSelect) {
                    if (filterSelect) {
                        filterSelect.appendChild(new Option(nombreCreado, nombreCreado));
                    }
                });

                nuevaCategoriaRow.hidden = true;
            })
            .catch(function (error) {
                nuevaCategoriaInput.setCustomValidity(error.message);
                nuevaCategoriaInput.reportValidity();
            })
            .finally(function () {
                nuevaCategoriaConfirmar.disabled = false;
                nuevaCategoriaConfirmar.textContent = originalText;
            });
    });

    /* ---------- Agregar unidad de medida al vuelo ----------
     * Mismo patrón que categorías, pero sin llamar al backend: la
     * columna unidad_medida es texto libre (no tiene tabla propia), así
     * que "agregarla" es solo insertarla como opción -queda real en
     * cuanto un producto la use, y la próxima carga de la página ya la
     * trae disponible (ver InventarioController@index). */
    unidadSelect.addEventListener('change', function () {
        if (unidadSelect.value === '__nueva__') {
            nuevaUnidadRow.hidden = false;
            nuevaUnidadInput.value = '';
            nuevaUnidadInput.focus();
        }
    });

    nuevaUnidadCancelar.addEventListener('click', function () {
        nuevaUnidadRow.hidden = true;
        unidadSelect.selectedIndex = 0;
    });

    nuevaUnidadInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            nuevaUnidadConfirmar.click();
        }
    });

    nuevaUnidadConfirmar.addEventListener('click', function () {
        var nombre = nuevaUnidadInput.value.trim();
        if (!nombre) {
            nuevaUnidadInput.focus();
            return;
        }

        var yaExiste = Array.prototype.some.call(unidadSelect.options, function (opt) {
            return opt.value.toLowerCase() === nombre.toLowerCase();
        });
        if (yaExiste) {
            nuevaUnidadInput.setCustomValidity('Ya existe esa unidad de medida.');
            nuevaUnidadInput.reportValidity();
            nuevaUnidadInput.focus();
            return;
        }
        nuevaUnidadInput.setCustomValidity('');

        var sentinelOption = unidadSelect.querySelector('option[value="__nueva__"]');
        unidadSelect.insertBefore(new Option(nombre, nombre), sentinelOption);
        unidadSelect.value = nombre;
        nuevaUnidadRow.hidden = true;
    });

    function resetModalVacio() {
        nombreInput.value = '';
        costoInput.value = '';
        ventaInput.value = '';
        categoriaSelect.selectedIndex = 0;
        unidadSelect.selectedIndex = 0;
        nuevaCategoriaRow.hidden = true;
        nuevaUnidadRow.hidden = true;
        updateGuardarState();
    }

    function llenarFormulario(producto) {
        nombreInput.value = producto.nombre;
        costoInput.value = formatNumber(producto.precioCosto, 0);
        ventaInput.value = formatNumber(producto.precioVenta, 0);
        categoriaSelect.value = producto.categoria;
        unidadSelect.value = producto.unidad;
        nuevaCategoriaRow.hidden = true;
        nuevaUnidadRow.hidden = true;
        updateGuardarState();
    }

    function openModal(producto) {
        productoEditandoId = producto ? producto.id : null;

        if (producto) {
            titleEl.textContent = 'Editar producto';
            guardarBtn.textContent = 'Guardar cambios';
            llenarFormulario(producto);
        } else {
            titleEl.textContent = 'Nuevo producto';
            guardarBtn.textContent = 'Guardar producto';
            resetModalVacio();
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-visible');
        window.setTimeout(function () { nombreInput.focus(); }, 250);
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-visible');
    }

    openBtn.addEventListener('click', function () { openModal(null); });
    window.abrirEditarProducto = function (id) {
        var producto = inventarioProductosById[id];
        if (producto) {
            openModal(producto);
        }
    };

    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    guardarBtn.addEventListener('click', function () {
        if (guardarBtn.disabled) {
            return;
        }
        if (categoriaSelect.value === '__nueva__') {
            nuevaCategoriaRow.hidden = false;
            nuevaCategoriaInput.focus();
            return;
        }
        if (unidadSelect.value === '__nueva__') {
            nuevaUnidadRow.hidden = false;
            nuevaUnidadInput.focus();
            return;
        }

        var originalText = guardarBtn.textContent;
        guardarBtn.disabled = true;
        guardarBtn.textContent = productoEditandoId ? 'Guardando cambios...' : 'Guardando...';

        var payload = {
            nombre: nombreInput.value.trim(),
            categoria: categoriaSelect.value,
            precio_costo: valorDineroInput(costoInput),
            precio_venta: valorDineroInput(ventaInput),
            unidad_medida: unidadSelect.value
        };

        var url = productoEditandoId
            ? '/cliente/inventario/productos/' + productoEditandoId
            : '/cliente/inventario/productos';
        var method = productoEditandoId ? 'PUT' : 'POST';

        inventarioApiRequest(method, url, payload)
            .then(function (json) {
                var producto = json.producto;

                if (productoEditandoId) {
                    inventarioProductosById[producto.id] = producto;
                    var idx = inventarioProductos.findIndex(function (p) { return p.id === producto.id; });
                    if (idx !== -1) {
                        inventarioProductos[idx] = producto;
                    }
                    actualizarFilaProducto(producto);
                    actualizarStatProductos();
                } else {
                    inventarioProductos.push(producto);
                    inventarioProductosById[producto.id] = producto;

                    var vitrinaTbody = document.querySelector('#vitrinaTable tbody');
                    var bodegaTbody = document.querySelector('#bodegaTable tbody');
                    if (vitrinaTbody) {
                        vitrinaTbody.appendChild(crearFilaVitrina(producto));
                    }
                    if (bodegaTbody) {
                        bodegaTbody.appendChild(crearFilaBodega(producto));
                    }

                    actualizarStatProductos();
                }

                // El precio de costo pudo cambiar al editar -eso afecta el
                // valor de bodega/vitrina/total aunque el stock no se haya
                // movido, así que se recalcula siempre, no solo en compras.
                actualizarStatsValorInventario();

                closeModal();
            })
            .catch(function (error) {
                window.alert(error.message);
            })
            .finally(function () {
                guardarBtn.disabled = false;
                guardarBtn.textContent = originalText;
            });
    });
}

/* --------------------------------------------------------------------
 * 7. Modal "Registrar compra" -proveedor (con validación de factura) o
 * informal. Cualquiera de los dos casos suma a BODEGA, nunca a vitrina
 * directamente. Todo esto vive solo en memoria del navegador (sin
 * backend, se pierde al recargar), pero sí actualiza las tablas y
 * stat cards en vivo para que se sienta real.
 * ------------------------------------------------------------------ */
function initRegistrarCompraModal() {
    var openBtn = document.getElementById('registrarCompraBtn');
    var modal = document.getElementById('registrarCompraModal');
    var overlay = document.getElementById('registrarCompraOverlay');
    if (!openBtn || !modal || !overlay) {
        return;
    }

    var closeBtn = document.getElementById('registrarCompraClose');
    var tipoProveedorBtn = document.getElementById('compraTipoProveedorBtn');
    var tipoInformalBtn = document.getElementById('compraTipoInformalBtn');
    var proveedorFields = document.getElementById('compraProveedorFields');
    var informalHint = document.getElementById('compraInformalHint');
    var proveedorNombreInput = document.getElementById('compraProveedorNombre');
    var cufeInput = document.getElementById('compraCufeInput');
    var validarBtn = document.getElementById('compraValidarBtn');
    var validarStatus = document.getElementById('compraValidarStatus');
    var searchInput = document.getElementById('compraProductoSearch');
    var resultsBox = document.getElementById('compraProductoResults');
    var linesContainer = document.getElementById('compraLines');
    var linesEmpty = document.getElementById('compraLinesEmpty');
    var totalEl = document.getElementById('compraTotal');
    var registrarBtn = document.getElementById('compraRegistrarBtn');

    var tipo = 'proveedor';
    var lineas = [];
    var validarTimeout = null;

    function setTipo(nuevo) {
        tipo = nuevo;
        tipoProveedorBtn.classList.toggle('is-active', nuevo === 'proveedor');
        tipoInformalBtn.classList.toggle('is-active', nuevo === 'informal');
        proveedorFields.hidden = nuevo !== 'proveedor';
        informalHint.hidden = nuevo !== 'informal';
    }

    tipoProveedorBtn.addEventListener('click', function () { setTipo('proveedor'); });
    tipoInformalBtn.addEventListener('click', function () { setTipo('informal'); });

    validarBtn.addEventListener('click', function () {
        if (!cufeInput.value.trim()) {
            validarStatus.className = 'compra-validar-status';
            validarStatus.textContent = 'Ingresa el CUFE o escanea el QR de la factura antes de validar.';
            return;
        }

        validarStatus.className = 'compra-validar-status is-validando';
        validarStatus.textContent = 'Validando ante la DIAN...';

        window.clearTimeout(validarTimeout);
        validarTimeout = window.setTimeout(function () {
            validarStatus.className = 'compra-validar-status is-validada';
            validarStatus.textContent = '✓ Factura validada. Ahora agrega los productos de la compra abajo.';
        }, 1400);
    });

    function getTotal() {
        return lineas.reduce(function (sum, linea) {
            return sum + linea.cantidad * linea.costo;
        }, 0);
    }

    function renderLineas() {
        linesContainer.innerHTML = '';

        if (lineas.length === 0) {
            linesContainer.appendChild(linesEmpty);
            linesEmpty.hidden = false;
        } else {
            linesEmpty.hidden = true;

            lineas.forEach(function (linea, index) {
                var row = document.createElement('div');
                row.className = 'venta-line';
                row.innerHTML =
                    '<div class="venta-line__info">' +
                        '<div class="venta-line__nombre">' + linea.nombre + '</div>' +
                        '<div class="compra-line__costo-row">' +
                            '<span>$</span>' +
                            '<input type="text" class="compra-line__costo-input" value="' + formatNumber(linea.costo, 0) + '">' +
                            '<span>c/u</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="venta-line__qty">' +
                        '<button type="button" class="venta-line__qty-btn" data-action="dec">−</button>' +
                        '<span class="venta-line__qty-value">' + linea.cantidad + '</span>' +
                        '<button type="button" class="venta-line__qty-btn" data-action="inc">+</button>' +
                    '</div>' +
                    '<div class="venta-line__subtotal">' + formatCOP(linea.cantidad * linea.costo) + '</div>' +
                    '<button type="button" class="venta-line__remove" aria-label="Quitar">' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"/></svg>' +
                    '</button>';

                row.querySelector('[data-action="inc"]').addEventListener('click', function () {
                    linea.cantidad++;
                    renderLineas();
                });
                row.querySelector('[data-action="dec"]').addEventListener('click', function () {
                    linea.cantidad = Math.max(1, linea.cantidad - 1);
                    renderLineas();
                });
                row.querySelector('.venta-line__remove').addEventListener('click', function () {
                    lineas.splice(index, 1);
                    renderLineas();
                });

                // El costo se edita en vivo sin reconstruir la fila -si
                // hiciéramos renderLineas() en cada tecla, el input se
                // recrearía y el cursor/foco se perdería mientras escribes.
                var costoInputLinea = row.querySelector('.compra-line__costo-input');
                formatearInputDinero(costoInputLinea);
                costoInputLinea.addEventListener('input', function (event) {
                    linea.costo = valorDineroInput(event.target);
                    row.querySelector('.venta-line__subtotal').textContent = formatCOP(linea.cantidad * linea.costo);
                    totalEl.textContent = formatCOP(getTotal());
                });

                linesContainer.appendChild(row);
            });
        }

        totalEl.textContent = formatCOP(getTotal());
        registrarBtn.disabled = lineas.length === 0;
    }

    searchInput.addEventListener('input', function () {
        var term = normalizarTexto(searchInput.value.trim());

        if (!term) {
            resultsBox.hidden = true;
            return;
        }

        var matches = inventarioProductos.filter(function (producto) {
            return normalizarTexto(producto.nombre).indexOf(term) !== -1;
        });

        resultsBox.innerHTML = '';

        if (matches.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'venta-product-results__empty';
            empty.textContent = 'No se encontró ningún producto en el catálogo.';
            resultsBox.appendChild(empty);
        } else {
            matches.forEach(function (producto) {
                var item = document.createElement('div');
                item.className = 'venta-product-result';
                item.innerHTML =
                    '<span>' + producto.nombre + '</span>' +
                    '<span class="venta-product-result__precio">' + formatCOP(producto.precioCosto) + ' c/u</span>';

                item.addEventListener('click', function () {
                    var existente = lineas.find(function (l) { return l.id === producto.id; });
                    if (existente) {
                        existente.cantidad++;
                    } else {
                        lineas.push({ id: producto.id, nombre: producto.nombre, costo: producto.precioCosto, cantidad: 1 });
                    }
                    renderLineas();
                    searchInput.value = '';
                    resultsBox.hidden = true;
                });

                resultsBox.appendChild(item);
            });
        }

        resultsBox.hidden = false;
    });

    document.addEventListener('click', function (event) {
        if (!resultsBox.contains(event.target) && event.target !== searchInput) {
            resultsBox.hidden = true;
        }
    });

    function resetModal() {
        setTipo('proveedor');
        proveedorNombreInput.value = '';
        cufeInput.value = '';
        validarStatus.className = 'compra-validar-status';
        validarStatus.textContent = 'Sin validar todavía. El QR solo confirma que la factura existe ante la DIAN -los productos se agregan abajo, a mano.';
        window.clearTimeout(validarTimeout);
        lineas = [];
        searchInput.value = '';
        resultsBox.hidden = true;
        renderLineas();
    }

    function openModal() {
        resetModal();
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-visible');
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-visible');
        window.clearTimeout(validarTimeout);
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
            tipo: tipo,
            proveedor_nombre: tipo === 'proveedor' ? (proveedorNombreInput.value.trim() || null) : null,
            cufe: tipo === 'proveedor' ? (cufeInput.value.trim() || null) : null,
            factura_validada: tipo === 'proveedor' && validarStatus.classList.contains('is-validada'),
            lineas: lineas.map(function (l) {
                return { producto_id: l.id, cantidad: l.cantidad, costo: l.costo };
            })
        };

        inventarioApiRequest('POST', '/cliente/inventario/compras', payload)
            .then(function (json) {
                var compra = json.compra;
                inventarioCompras.unshift(compra);
                inventarioComprasById[compra.id] = compra;

                var comprasTbody = document.querySelector('#comprasTable tbody');
                if (comprasTbody) {
                    var nuevaFila = crearFilaCompra(compra);
                    wireFilaCompraRow(nuevaFila);
                    comprasTbody.insertBefore(nuevaFila, comprasTbody.firstChild);
                }

                // Toda compra suma a BODEGA -nunca a vitrina directamente.
                // El costo de referencia del producto NO se toca acá con
                // un simple promedio hecho en JS: esa cuenta (costo
                // promedio ponderado) ya vive en el backend real cuando
                // haga falta, no se improvisa en el frontend.
                (json.productosActualizados || []).forEach(function (actualizado) {
                    var producto = inventarioProductosById[actualizado.id];
                    if (producto) {
                        producto.stockVitrina = actualizado.stockVitrina;
                        producto.stockBodega = actualizado.stockBodega;
                        actualizarFilaProducto(producto);
                    }
                });

                actualizarStatsValorInventario();
                incrementarStatComprasMes();
                closeModal();
            })
            .catch(function (error) {
                window.alert(error.message);
            })
            .finally(function () {
                registrarBtn.disabled = false;
                registrarBtn.textContent = originalText;
            });
    });
}

/* --------------------------------------------------------------------
 * 8. Modal "Transferir" -bodega a vitrina. Es la única acción del panel
 * que sí actualiza el stock mostrado en vivo (misma idea que "Abrir
 * caja" en el dashboard): da feedback real aunque no haya backend.
 * ------------------------------------------------------------------ */
function initTransferirModal() {
    var modal = document.getElementById('transferirModal');
    var overlay = document.getElementById('transferirOverlay');
    if (!modal || !overlay) {
        return;
    }

    var closeBtn = document.getElementById('transferirClose');
    var nombreEl = document.getElementById('transferirProductoNombre');
    var disponibleEl = document.getElementById('transferirDisponible');
    var cantidadInput = document.getElementById('transferirCantidad');
    var errorEl = document.getElementById('transferirError');
    var confirmarBtn = document.getElementById('transferirConfirmarBtn');

    var productoActualId = null;

    window.abrirTransferirModal = function (id) {
        var producto = inventarioProductosById[id];
        if (!producto) {
            return;
        }

        productoActualId = id;
        nombreEl.textContent = producto.nombre;
        disponibleEl.textContent = 'Disponible en bodega: ' + producto.stockBodega;
        cantidadInput.value = '';
        cantidadInput.max = producto.stockBodega;
        errorEl.hidden = true;

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-visible');
        window.setTimeout(function () { cantidadInput.focus(); }, 250);
    };

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-visible');
    }

    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    confirmarBtn.addEventListener('click', function () {
        var producto = inventarioProductosById[productoActualId];
        if (!producto) {
            return;
        }

        var cantidad = parseInt(cantidadInput.value, 10);

        if (!cantidad || cantidad < 1 || cantidad > producto.stockBodega) {
            errorEl.textContent = 'No puedes transferir más de lo que hay en bodega.';
            errorEl.hidden = false;
            return;
        }

        errorEl.hidden = true;

        var originalText = confirmarBtn.textContent;
        confirmarBtn.disabled = true;

        inventarioApiRequest('POST', '/cliente/inventario/transferencias', { producto_id: producto.id, cantidad: cantidad })
            .then(function (json) {
                producto.stockVitrina = json.producto.stockVitrina;
                producto.stockBodega = json.producto.stockBodega;
                actualizarFilaProducto(producto);
                actualizarStatsValorInventario();

                confirmarBtn.textContent = 'Transferido ✓';
                window.setTimeout(function () {
                    confirmarBtn.textContent = originalText;
                    confirmarBtn.disabled = false;
                    closeModal();
                }, 600);
            })
            .catch(function (error) {
                errorEl.textContent = error.message;
                errorEl.hidden = false;
                confirmarBtn.disabled = false;
            });
    });
}
