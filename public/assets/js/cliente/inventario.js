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
 *   6b. initCategoriasModal    -> crear, renombrar y eliminar categorías
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
    initCategoriasModal();
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
var inventarioCategorias = [];

function cargarInventarioData() {
    var productosScript = document.getElementById('inventarioProductosData');
    var comprasScript = document.getElementById('inventarioComprasData');
    var categoriasScript = document.getElementById('inventarioCategoriasData');
    if (!productosScript || !comprasScript) {
        return false;
    }

    inventarioProductos = JSON.parse(productosScript.textContent);
    inventarioProductos.forEach(function (p) { inventarioProductosById[p.id] = p; });

    inventarioCompras = JSON.parse(comprasScript.textContent);
    inventarioCompras.forEach(function (c) { inventarioComprasById[c.id] = c; });

    inventarioCategorias = categoriasScript ? JSON.parse(categoriasScript.textContent) : [];

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

    var facturaLabels = { validada: 'Validada', por_validar: 'Por validar', sin_factura: 'Compra informal' };

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
var COMPRA_FACTURA_LABELS = { validada: 'Validada', por_validar: 'Por validar', sin_factura: 'Compra informal' };
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

/** Una fila "etiqueta - valor" (mismo look que .slide-over__field), para
 * mostrar cantidad/precio unitario/total de una línea de compra sin
 * meter el valor (texto libre en el caso del nombre) directo en innerHTML. */
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
        // El nombre del producto es texto libre escrito por el negocio -si
        // se metiera con innerHTML, un nombre malicioso ("<img onerror=...")
        // se ejecutaría en la sesión de cualquiera que abra esta compra.
        // Cantidad, precio unitario y total van cada uno en su propia fila
        // "etiqueta - valor", igual que Precio de costo/venta en el panel
        // del producto -así quedan claramente separados, no todos pegados
        // en una sola línea de texto.
        var wrapper = document.createElement('div');
        wrapper.className = 'compra-linea-producto';

        var nombreEl = document.createElement('div');
        nombreEl.className = 'compra-linea-producto__nombre';
        nombreEl.textContent = linea.nombre;
        wrapper.appendChild(nombreEl);

        wrapper.appendChild(crearCampoDetalleLinea('Cantidad', String(linea.cantidad)));
        wrapper.appendChild(crearCampoDetalleLinea('Precio unitario', formatCOP(linea.costo)));
        wrapper.appendChild(crearCampoDetalleLinea('Total', formatCOP(linea.cantidad * linea.costo)));

        lineasContainer.appendChild(wrapper);
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

    var celdaFecha = row.insertCell();
    celdaFecha.className = 'data-table__meta';
    celdaFecha.textContent = compra.fecha;

    // El nombre del proveedor es texto libre -mismo motivo que arriba, va
    // por textContent y no por innerHTML.
    var celdaProveedor = row.insertCell();
    var tituloProveedor = document.createElement('div');
    tituloProveedor.className = 'data-table__title';
    tituloProveedor.textContent = proveedorTexto;
    celdaProveedor.appendChild(tituloProveedor);

    var celdaCantidad = row.insertCell();
    celdaCantidad.className = 'data-table__meta';
    celdaCantidad.textContent = compra.lineas.length + ' producto' + (compra.lineas.length === 1 ? '' : 's');

    var celdaTotal = row.insertCell();
    celdaTotal.className = 'data-table__title';
    celdaTotal.textContent = formatCOP(compra.total);

    var celdaEstado = row.insertCell();
    var pill = document.createElement('span');
    pill.className = 'status-pill ' + COMPRA_FACTURA_PILL_CLASS[compra.facturaEstado];
    pill.textContent = COMPRA_FACTURA_LABELS[compra.facturaEstado];
    celdaEstado.appendChild(pill);

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

    var eliminarBtn = document.getElementById('productoSlideOverEliminarBtn');
    eliminarBtn.onclick = function () { eliminarProducto(id); };

    slideOver.classList.add('is-open');
    slideOver.setAttribute('aria-hidden', 'false');
    overlay.classList.add('is-visible');
}

/**
 * Baja lógica: el producto deja de aparecer en el catálogo (Vitrina,
 * Bodega, buscador de "Registrar compra"), pero las compras que ya lo
 * mencionan siguen intactas en el historial -por eso el backend hace un
 * soft delete, no lo borra de verdad.
 */
function eliminarProducto(id) {
    var producto = inventarioProductosById[id];
    if (!producto) {
        return;
    }

    confirmarAccion({
        titulo: '¿Eliminar este producto?',
        texto: '"' + producto.nombre + '" se quitará del catálogo. Esta acción no se puede deshacer.',
        textoConfirmar: 'Sí, eliminar',
        peligro: true
    }).then(function (confirmado) {
        if (!confirmado) {
            return;
        }

        inventarioApiRequest('DELETE', '/cliente/inventario/productos/' + id)
            .then(function () {
                var vitrinaRow = document.querySelector('#vitrinaTable .data-table__row[data-producto-id="' + id + '"]');
                var bodegaRow = document.querySelector('#bodegaTable .data-table__row[data-producto-id="' + id + '"]');
                if (vitrinaRow) {
                    vitrinaRow.remove();
                }
                if (bodegaRow) {
                    bodegaRow.remove();
                }

                [['vitrinaTable', 'vitrinaEmpty'], ['bodegaTable', 'bodegaEmpty']].forEach(function (par) {
                    var table = document.getElementById(par[0]);
                    var emptyEl = document.getElementById(par[1]);
                    if (table && emptyEl) {
                        emptyEl.hidden = table.querySelectorAll('.data-table__row:not([hidden])').length !== 0;
                    }
                });

                var idx = inventarioProductos.findIndex(function (p) { return p.id === id; });
                if (idx !== -1) {
                    inventarioProductos.splice(idx, 1);
                }
                delete inventarioProductosById[id];

                cerrarProductoSlideOver();
                actualizarStatProductos();
                actualizarStatsValorInventario();
            })
            .catch(function (error) {
                mostrarError(error.message);
            });
    });
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

// El nombre, categoría y unidad de un producto son texto libre escrito por
// el negocio (ver "+ Agregar categoría/unidad..."), así que arman la fila
// con textContent en vez de innerHTML -si no, un nombre malicioso se
// ejecutaría en el navegador de cualquiera que cargue Vitrina/Bodega.
function crearFilaVitrina(producto) {
    var row = document.createElement('tr');
    row.className = 'data-table__row';
    row.setAttribute('data-producto-id', producto.id);
    row.tabIndex = 0;

    var celdaProducto = row.insertCell();
    var titulo = document.createElement('div');
    titulo.className = 'data-table__title';
    titulo.textContent = producto.nombre;
    var meta = document.createElement('div');
    meta.className = 'data-table__meta';
    meta.textContent = producto.unidad;
    celdaProducto.appendChild(titulo);
    celdaProducto.appendChild(meta);

    var celdaCategoria = row.insertCell();
    celdaCategoria.className = 'data-table__meta';
    celdaCategoria.textContent = producto.categoria;

    var celdaPrecio = row.insertCell();
    celdaPrecio.className = 'data-table__title';
    celdaPrecio.textContent = formatCOP(producto.precioVenta);

    var celdaStock = row.insertCell();
    celdaStock.className = 'data-table__meta';
    celdaStock.textContent = producto.stockVitrina;

    wireFilaProductoClick(row, producto.id);
    return row;
}

function crearFilaBodega(producto) {
    var row = document.createElement('tr');
    row.className = 'data-table__row';
    row.setAttribute('data-producto-id', producto.id);
    row.tabIndex = 0;

    var celdaProducto = row.insertCell();
    var titulo = document.createElement('div');
    titulo.className = 'data-table__title';
    titulo.textContent = producto.nombre;
    var meta = document.createElement('div');
    meta.className = 'data-table__meta';
    meta.textContent = producto.unidad;
    celdaProducto.appendChild(titulo);
    celdaProducto.appendChild(meta);

    var celdaCategoria = row.insertCell();
    celdaCategoria.className = 'data-table__meta';
    celdaCategoria.textContent = producto.categoria;

    var celdaCosto = row.insertCell();
    celdaCosto.className = 'data-table__title';
    celdaCosto.textContent = formatCOP(producto.precioCosto);

    var celdaStock = row.insertCell();
    celdaStock.className = 'data-table__meta';
    celdaStock.textContent = producto.stockBodega;

    var celdaAccion = row.insertCell();
    var transferirBtn = document.createElement('button');
    transferirBtn.type = 'button';
    transferirBtn.className = 'inventario-transfer-btn';
    transferirBtn.setAttribute('data-producto-id', producto.id);
    transferirBtn.textContent = 'Transferir';
    celdaAccion.appendChild(transferirBtn);

    wireFilaProductoClick(row, producto.id);
    transferirBtn.addEventListener('click', function (event) {
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
        var count = inventarioCategorias.length;
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
        nuevaUnidadRow.hidden = true;
        updateGuardarState();
    }

    function llenarFormulario(producto) {
        nombreInput.value = producto.nombre;
        costoInput.value = formatNumber(producto.precioCosto, 0);
        ventaInput.value = formatNumber(producto.precioVenta, 0);
        categoriaSelect.value = producto.categoria;
        unidadSelect.value = producto.unidad;
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
                mostrarError(error.message);
            })
            .finally(function () {
                guardarBtn.disabled = false;
                guardarBtn.textContent = originalText;
            });
    });
}

/* --------------------------------------------------------------------
 * 6b. Modal "Categorías" -crear, renombrar y eliminar. Aparte del modal
 * de producto (donde solo se elige una categoría ya existente) para que
 * ese formulario quede simple; gestionar el catálogo de categorías vive
 * en su propio lugar, con acceso desde el botón "Categorías" del header.
 * ------------------------------------------------------------------ */
function initCategoriasModal() {
    var openBtn = document.getElementById('categoriasBtn');
    var modal = document.getElementById('categoriasModal');
    var overlay = document.getElementById('categoriasOverlay');
    if (!openBtn || !modal || !overlay) {
        return;
    }

    var closeBtn = document.getElementById('categoriasClose');
    var listaEl = document.getElementById('categoriasLista');
    var emptyEl = document.getElementById('categoriasEmpty');
    var nuevaInput = document.getElementById('categoriaNuevaInput');
    var nuevaConfirmar = document.getElementById('categoriaNuevaConfirmar');

    var TRASH_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13"/></svg>';
    var PENCIL_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';

    /** Refleja un cambio de nombre/alta/baja de categoría en todos los
     * selectores que la muestran (el de producto y los filtros de
     * Vitrina/Bodega), para que no haga falta recargar la página. */
    function sincronizarSelects(fn) {
        [document.getElementById('prodCategoria'), document.getElementById('vitrinaCategoriaFilter'), document.getElementById('bodegaCategoriaFilter')].forEach(function (select) {
            if (select) {
                fn(select);
            }
        });
    }

    function agregarOpcion(select, nombre) {
        var placeholder = select.querySelector('option[value=""]');
        if (placeholder) {
            placeholder.remove();
        }
        select.appendChild(new Option(nombre, nombre));
    }

    function renombrarOpcion(select, viejoNombre, nuevoNombre) {
        var opcion = Array.prototype.find.call(select.options, function (opt) { return opt.value === viejoNombre; });
        if (opcion) {
            opcion.value = nuevoNombre;
            opcion.textContent = nuevoNombre;
        }
    }

    function quitarOpcion(select, nombre) {
        var opcion = Array.prototype.find.call(select.options, function (opt) { return opt.value === nombre; });
        if (opcion) {
            opcion.remove();
        }
        if (select.id === 'prodCategoria' && select.options.length === 0) {
            select.appendChild(new Option('Primero crea una categoría...', ''));
            select.value = '';
        }
    }

    /** Los productos ya cargados en memoria (y sus filas/slide-over)
     * también muestran el nombre de categoría -si se renombra, hay que
     * actualizarlos ahí también para que no queden con el nombre viejo. */
    function renombrarEnProductos(viejoNombre, nuevoNombre) {
        inventarioProductos.forEach(function (producto) {
            if (producto.categoria === viejoNombre) {
                producto.categoria = nuevoNombre;
                actualizarFilaProducto(producto);
            }
        });
    }

    function renderLista() {
        listaEl.innerHTML = '';
        emptyEl.hidden = inventarioCategorias.length !== 0;

        inventarioCategorias.forEach(function (nombre) {
            listaEl.appendChild(crearFilaCategoria(nombre));
        });
    }

    function crearFilaCategoria(nombre) {
        var row = document.createElement('div');
        row.className = 'categoria-row';
        row.innerHTML =
            '<span class="categoria-row__nombre"></span>' +
            '<div class="categoria-row__actions">' +
            '<button type="button" class="categoria-row__btn categoria-row__btn--editar" title="Renombrar">' + PENCIL_SVG + '</button>' +
            '<button type="button" class="categoria-row__btn categoria-row__btn--eliminar" title="Eliminar">' + TRASH_SVG + '</button>' +
            '</div>';
        row.querySelector('.categoria-row__nombre').textContent = nombre;

        row.querySelector('.categoria-row__btn--editar').addEventListener('click', function () {
            activarEdicion(row, nombre);
        });
        row.querySelector('.categoria-row__btn--eliminar').addEventListener('click', function () {
            eliminarCategoria(nombre);
        });

        return row;
    }

    function activarEdicion(row, nombreActual) {
        row.className = 'categoria-row categoria-row--editando';
        row.innerHTML =
            '<input type="text" class="cliente-input categoria-row__input" style="flex:1;">' +
            '<div class="categoria-row__actions" style="margin-left:8px;">' +
            '<button type="button" class="categoria-row__btn categoria-row__btn--confirmar" title="Guardar">✓</button>' +
            '<button type="button" class="categoria-row__btn categoria-row__btn--cancelar" title="Cancelar">✕</button>' +
            '</div>';

        var input = row.querySelector('.categoria-row__input');
        input.value = nombreActual;
        input.focus();
        input.select();

        function cancelar() {
            row.replaceWith(crearFilaCategoria(nombreActual));
        }

        function confirmar() {
            var nombreNuevo = input.value.trim();
            if (!nombreNuevo || nombreNuevo === nombreActual) {
                cancelar();
                return;
            }

            var yaExiste = inventarioCategorias.some(function (c) { return c.toLowerCase() === nombreNuevo.toLowerCase() && c !== nombreActual; });
            if (yaExiste) {
                input.setCustomValidity('Ya existe una categoría con ese nombre.');
                input.reportValidity();
                return;
            }
            input.setCustomValidity('');

            var botonConfirmar = row.querySelector('.categoria-row__btn--confirmar');
            botonConfirmar.disabled = true;

            inventarioApiRequest('PUT', '/cliente/inventario/categorias', { nombre_actual: nombreActual, nombre_nuevo: nombreNuevo })
                .then(function () {
                    var idx = inventarioCategorias.indexOf(nombreActual);
                    if (idx !== -1) {
                        inventarioCategorias[idx] = nombreNuevo;
                    }

                    sincronizarSelects(function (select) { renombrarOpcion(select, nombreActual, nombreNuevo); });
                    renombrarEnProductos(nombreActual, nombreNuevo);

                    row.replaceWith(crearFilaCategoria(nombreNuevo));
                })
                .catch(function (error) {
                    input.setCustomValidity(error.message);
                    input.reportValidity();
                    botonConfirmar.disabled = false;
                });
        }

        row.querySelector('.categoria-row__btn--confirmar').addEventListener('click', confirmar);
        row.querySelector('.categoria-row__btn--cancelar').addEventListener('click', cancelar);
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                confirmar();
            } else if (event.key === 'Escape') {
                event.preventDefault();
                cancelar();
            }
        });
    }

    function eliminarCategoria(nombre) {
        confirmarAccion({
            titulo: '¿Eliminar esta categoría?',
            texto: '"' + nombre + '" se borrará. Solo se puede si ningún producto la está usando.',
            textoConfirmar: 'Sí, eliminar',
            peligro: true
        }).then(function (confirmado) {
            if (!confirmado) {
                return;
            }

            inventarioApiRequest('DELETE', '/cliente/inventario/categorias', { nombre: nombre })
                .then(function () {
                    var idx = inventarioCategorias.indexOf(nombre);
                    if (idx !== -1) {
                        inventarioCategorias.splice(idx, 1);
                    }

                    sincronizarSelects(function (select) { quitarOpcion(select, nombre); });
                    renderLista();
                    actualizarStatProductos();
                })
                .catch(function (error) {
                    mostrarError(error.message);
                });
        });
    }

    nuevaInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            nuevaConfirmar.click();
        }
    });

    nuevaConfirmar.addEventListener('click', function () {
        var nombre = nuevaInput.value.trim();
        if (!nombre) {
            nuevaInput.focus();
            return;
        }

        var yaExiste = inventarioCategorias.some(function (c) { return c.toLowerCase() === nombre.toLowerCase(); });
        if (yaExiste) {
            nuevaInput.setCustomValidity('Ya existe una categoría con ese nombre.');
            nuevaInput.reportValidity();
            nuevaInput.focus();
            return;
        }
        nuevaInput.setCustomValidity('');

        var originalText = nuevaConfirmar.textContent;
        nuevaConfirmar.disabled = true;
        nuevaConfirmar.textContent = 'Agregando...';

        inventarioApiRequest('POST', '/cliente/inventario/categorias', { nombre: nombre })
            .then(function (json) {
                var nombreCreado = json.categoria;
                inventarioCategorias.push(nombreCreado);

                sincronizarSelects(function (select) { agregarOpcion(select, nombreCreado); });
                renderLista();
                actualizarStatProductos();

                nuevaInput.value = '';
            })
            .catch(function (error) {
                nuevaInput.setCustomValidity(error.message);
                nuevaInput.reportValidity();
            })
            .finally(function () {
                nuevaConfirmar.disabled = false;
                nuevaConfirmar.textContent = originalText;
            });
    });

    function openModal() {
        renderLista();
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-visible');
        window.setTimeout(function () { nuevaInput.focus(); }, 250);
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
    var proveedorSelect = document.getElementById('compraProveedorSelect');
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
                // linea.nombre es texto libre (nombre de producto) -va por
                // textContent más abajo, nunca directo en el innerHTML.
                row.innerHTML =
                    '<div class="venta-line__info">' +
                        '<div class="venta-line__nombre"></div>' +
                        '<div class="compra-line__costo-row">' +
                            '<span>$</span>' +
                            '<input type="text" class="compra-line__costo-input" value="' + formatNumber(linea.costo, 0) + '">' +
                            '<span>c/u</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="venta-line__qty">' +
                        '<button type="button" class="venta-line__qty-btn" data-action="dec">−</button>' +
                        '<input type="text" inputmode="numeric" class="venta-line__qty-value venta-line__qty-value--input" value="' + linea.cantidad + '">' +
                        '<button type="button" class="venta-line__qty-btn" data-action="inc">+</button>' +
                    '</div>' +
                    '<div class="venta-line__subtotal">' + formatCOP(linea.cantidad * linea.costo) + '</div>' +
                    '<button type="button" class="venta-line__remove" aria-label="Quitar">' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"/></svg>' +
                    '</button>';

                row.querySelector('.venta-line__nombre').textContent = linea.nombre;

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

                // La cantidad también se puede escribir directamente (más
                // fácil que darle a "+" cien veces si son 100 unidades). Se
                // actualiza en vivo sin reconstruir la fila -si no, el
                // cursor/foco se perdería mientras escribes.
                var qtyInputLinea = row.querySelector('.venta-line__qty-value--input');
                qtyInputLinea.addEventListener('input', function (event) {
                    var digitos = event.target.value.replace(/\D/g, '');
                    event.target.value = digitos;
                    linea.cantidad = digitos ? parseInt(digitos, 10) : 0;
                    row.querySelector('.venta-line__subtotal').textContent = formatCOP(linea.cantidad * linea.costo);
                    totalEl.textContent = formatCOP(getTotal());
                });
                qtyInputLinea.addEventListener('blur', function () {
                    if (!linea.cantidad || linea.cantidad < 1) {
                        linea.cantidad = 1;
                        qtyInputLinea.value = '1';
                        row.querySelector('.venta-line__subtotal').textContent = formatCOP(linea.cantidad * linea.costo);
                        totalEl.textContent = formatCOP(getTotal());
                    }
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
                // producto.nombre es texto libre -va por textContent más
                // abajo, nunca directo en el innerHTML.
                item.innerHTML =
                    '<span class="venta-product-result__nombre"></span>' +
                    '<span class="venta-product-result__precio">' + formatCOP(producto.precioCosto) + ' c/u</span>';
                item.querySelector('.venta-product-result__nombre').textContent = producto.nombre;

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
        proveedorSelect.selectedIndex = 0;
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
            proveedor_id: tipo === 'proveedor' ? (proveedorSelect.value || null) : null,
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
                mostrarError(error.message);
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
