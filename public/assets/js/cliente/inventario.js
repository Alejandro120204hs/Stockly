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
 *   6. initNuevoProductoModal  -> alta de producto en el catálogo (demo)
 *   7. initRegistrarCompraModal -> compra a proveedor (con validación de
 *      factura) o informal -siempre suma a bodega, nunca a vitrina
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
 * Estado compartido: catálogo de productos (mutable -Transferir sí
 * cambia el stock en memoria) y compras (solo lectura, para el detalle).
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

    var overlay = document.getElementById('compraSlideOverOverlay');
    var slideOver = document.getElementById('compraSlideOver');
    var closeBtn = document.getElementById('compraSlideOverClose');

    function openCompra(id) {
        var compra = inventarioComprasById[id];
        if (!compra) {
            return;
        }

        document.getElementById('compraSlideOverTitulo').textContent = 'Compra #' + compra.id;

        var estadoPill = document.getElementById('compraSlideOverEstado');
        var pillClass = { validada: 'status-pill--facturada', por_validar: 'status-pill--pendiente', sin_factura: 'status-pill--sin-facturar' };
        estadoPill.className = 'status-pill ' + pillClass[compra.facturaEstado];
        estadoPill.textContent = facturaLabels[compra.facturaEstado];

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

    function closeSlideOver() {
        slideOver.classList.remove('is-open');
        slideOver.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-visible');
    }

    table.querySelectorAll('.data-table__row').forEach(function (row) {
        var id = parseInt(row.getAttribute('data-compra-id'), 10);
        row.addEventListener('click', function () { openCompra(id); });
        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openCompra(id);
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

    document.getElementById('productoSlideOverTitulo').textContent = producto.nombre;
    document.getElementById('productoSlideOverCategoria').textContent = producto.categoria;
    document.getElementById('productoSlideOverPrecioCosto').textContent = formatCOP(producto.precioCosto);
    document.getElementById('productoSlideOverPrecioVenta').textContent = formatCOP(producto.precioVenta);
    document.getElementById('productoSlideOverUnidad').textContent = producto.unidad;
    document.getElementById('productoSlideOverStockVitrina').textContent = producto.stockVitrina + ' ' + producto.unidad.toLowerCase() + 's';
    document.getElementById('productoSlideOverStockBodega').textContent = producto.stockBodega + ' ' + producto.unidad.toLowerCase() + 's';

    var transferirBtn = document.getElementById('productoSlideOverTransferirBtn');
    transferirBtn.onclick = function () { abrirTransferirModal(id); };

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
 * 6. Modal "Nuevo producto" -da de alta el producto en el catálogo.
 * Todo demo: no persiste ni aparece en las tablas todavía.
 * ------------------------------------------------------------------ */
function initNuevoProductoModal() {
    var openBtn = document.getElementById('nuevoProductoBtn');
    var modal = document.getElementById('nuevoProductoModal');
    var overlay = document.getElementById('nuevoProductoOverlay');
    if (!openBtn || !modal || !overlay) {
        return;
    }

    var closeBtn = document.getElementById('nuevoProductoClose');
    var nombreInput = document.getElementById('prodNombre');
    var costoInput = document.getElementById('prodPrecioCosto');
    var ventaInput = document.getElementById('prodPrecioVenta');
    var guardarBtn = document.getElementById('prodGuardarBtn');

    function updateGuardarState() {
        var valido = nombreInput.value.trim() !== '' && costoInput.value !== '' && ventaInput.value !== '';
        guardarBtn.disabled = !valido;
    }

    [nombreInput, costoInput, ventaInput].forEach(function (input) {
        input.addEventListener('input', updateGuardarState);
    });

    function resetModal() {
        nombreInput.value = '';
        costoInput.value = '';
        ventaInput.value = '';
        document.getElementById('prodCategoria').selectedIndex = 0;
        document.getElementById('prodUnidad').selectedIndex = 0;
        updateGuardarState();
    }

    function openModal() {
        resetModal();
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

    openBtn.addEventListener('click', openModal);
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
        var originalText = guardarBtn.textContent;
        guardarBtn.disabled = true;
        guardarBtn.textContent = 'Guardando...';

        window.setTimeout(function () {
            guardarBtn.textContent = originalText;
            closeModal();
        }, 700);
    });
}

/* --------------------------------------------------------------------
 * 7. Modal "Registrar compra" -proveedor (con validación de factura) o
 * informal. Cualquiera de los dos casos suma a BODEGA, nunca a vitrina
 * directamente -eso es lo que representa este formulario, aunque acá
 * (todo demo) no se refleje en las tablas todavía.
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
                        '<div class="venta-line__precio">' + formatCOP(linea.costo) + ' c/u</div>' +
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

        window.setTimeout(function () {
            registrarBtn.textContent = originalText;
            closeModal();
        }, 700);
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
        disponibleEl.textContent = 'Disponible en bodega: ' + producto.stockBodega + ' ' + producto.unidad.toLowerCase() + 's';
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

    function actualizarFilasProducto(producto) {
        [document.getElementById('vitrinaTable'), document.getElementById('bodegaTable')].forEach(function (table) {
            if (!table) {
                return;
            }
            var row = table.querySelector('.data-table__row[data-producto-id="' + producto.id + '"]');
            if (!row) {
                return;
            }
            var stockCell = row.cells[3];
            var esVitrina = table.id === 'vitrinaTable';
            var stock = esVitrina ? producto.stockVitrina : producto.stockBodega;
            stockCell.textContent = stock + ' ' + producto.unidad.toLowerCase() + 's';
        });

        if (document.getElementById('productoSlideOver').classList.contains('is-open')
            && document.getElementById('productoSlideOverTitulo').textContent === producto.nombre) {
            document.getElementById('productoSlideOverStockVitrina').textContent = producto.stockVitrina + ' ' + producto.unidad.toLowerCase() + 's';
            document.getElementById('productoSlideOverStockBodega').textContent = producto.stockBodega + ' ' + producto.unidad.toLowerCase() + 's';
        }
    }

    confirmarBtn.addEventListener('click', function () {
        var producto = inventarioProductosById[productoActualId];
        if (!producto) {
            return;
        }

        var cantidad = parseInt(cantidadInput.value, 10);

        if (!cantidad || cantidad < 1 || cantidad > producto.stockBodega) {
            errorEl.hidden = false;
            return;
        }

        errorEl.hidden = true;
        producto.stockBodega -= cantidad;
        producto.stockVitrina += cantidad;
        actualizarFilasProducto(producto);

        var originalText = confirmarBtn.textContent;
        confirmarBtn.textContent = 'Transferido ✓';
        window.setTimeout(function () {
            confirmarBtn.textContent = originalText;
            closeModal();
        }, 600);
    });
}
