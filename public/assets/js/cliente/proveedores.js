/**
 * Stockly — Panel del negocio cliente: vista Proveedores (vanilla JS)
 * Depende de cliente/layout.js (formatCOP, formatNumber, normalizarTexto,
 * confirmarAccion, mostrarError) ya cargado antes que este.
 *
 * Módulos:
 *   1. initCountUp          -> anima los números de las stat cards
 *   2. initProveedoresTable -> búsqueda + slide-over de proveedor
 *   3. initProveedorModal   -> crear o editar un proveedor
 */

document.addEventListener('DOMContentLoaded', function () {
    initCountUp();
    cargarProveedoresData();
    initProveedoresTable();
    initProveedorModal();
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
 * Estado compartido: catálogo de proveedores, mutable en memoria
 * -Nuevo/Editar/Eliminar lo mantienen sincronizado con el backend real.
 * ------------------------------------------------------------------ */
var proveedoresById = {};

function cargarProveedoresData() {
    var script = document.getElementById('proveedoresData');
    if (!script) {
        return;
    }

    JSON.parse(script.textContent).forEach(function (proveedor) {
        proveedoresById[proveedor.id] = proveedor;
    });
}

/** Mismo helper que en inventario.js -incluye el token CSRF y convierte
 * una respuesta con error HTTP en un Error con el mensaje del servidor. */
function proveedoresApiRequest(method, url, data) {
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
 * 2. Tabla: búsqueda + slide-over de proveedor
 * ------------------------------------------------------------------ */
function initProveedoresTable() {
    var table = document.getElementById('proveedoresTable');
    if (!table) {
        return;
    }

    var searchInput = document.getElementById('proveedoresSearch');
    var emptyState = document.getElementById('proveedoresEmpty');

    function render() {
        var term = normalizarTexto(searchInput.value.trim());
        var visibleCount = 0;

        table.querySelectorAll('.data-table__row').forEach(function (row) {
            var id = parseInt(row.getAttribute('data-proveedor-id'), 10);
            var proveedor = proveedoresById[id];
            var matches = !term
                || normalizarTexto(proveedor.nombre).indexOf(term) !== -1
                || (proveedor.nit && proveedor.nit.indexOf(term) !== -1);
            row.hidden = !matches;
            if (matches) {
                visibleCount++;
            }
        });

        emptyState.hidden = visibleCount !== 0 || Object.keys(proveedoresById).length === 0;
    }

    searchInput.addEventListener('input', render);

    wireFilasProveedor(table);
}

function wireFilasProveedor(table) {
    table.querySelectorAll('.data-table__row').forEach(function (row) {
        var id = parseInt(row.getAttribute('data-proveedor-id'), 10);
        row.addEventListener('click', function () { abrirProveedorSlideOver(id); });
        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                abrirProveedorSlideOver(id);
            }
        });
    });
}

/* --------------------------------------------------------------------
 * Slide-over de proveedor: datos fiscales + historial de compras
 * ------------------------------------------------------------------ */
function abrirProveedorSlideOver(id) {
    var proveedor = proveedoresById[id];
    var overlay = document.getElementById('proveedorSlideOverOverlay');
    var slideOver = document.getElementById('proveedorSlideOver');
    if (!proveedor || !overlay || !slideOver) {
        return;
    }

    slideOver.dataset.proveedorId = String(id);

    document.getElementById('proveedorSlideOverNombre').textContent = proveedor.nombre;
    document.getElementById('proveedorSlideOverTipo').textContent = proveedor.tipoPersona === 'juridica' ? 'Persona jurídica' : 'Persona natural';
    document.getElementById('proveedorSlideOverNit').textContent = proveedor.nit + (proveedor.dv ? '-' + proveedor.dv : '');
    document.getElementById('proveedorSlideOverRegimen').textContent = proveedor.regimenFiscal || '—';
    document.getElementById('proveedorSlideOverTelefono').textContent = proveedor.telefono || '—';
    document.getElementById('proveedorSlideOverCorreo').textContent = proveedor.correo || '—';
    document.getElementById('proveedorSlideOverDireccion').textContent = proveedor.direccion || '—';
    document.getElementById('proveedorSlideOverCiudad').textContent = [proveedor.ciudad, proveedor.departamento].filter(Boolean).join(', ') || '—';

    var comprasContainer = document.getElementById('proveedorSlideOverCompras');
    var sinCompras = document.getElementById('proveedorSlideOverSinCompras');
    comprasContainer.innerHTML = '';

    if (proveedor.compras.length === 0) {
        sinCompras.hidden = false;
    } else {
        sinCompras.hidden = true;
        proveedor.compras.forEach(function (compra) {
            var item = document.createElement('div');
            item.className = 'venta-detalle-item venta-detalle-item--clicable';
            item.tabIndex = 0;

            var fechaEl = document.createElement('div');
            fechaEl.className = 'venta-detalle-item__nombre';
            fechaEl.textContent = compra.fecha;

            var montoEl = document.createElement('div');
            montoEl.className = 'venta-detalle-item__monto';
            montoEl.textContent = formatCOP(compra.total);

            item.appendChild(fechaEl);
            item.appendChild(montoEl);
            item.addEventListener('click', function () { abrirCompraDetalleSlideOver(compra); });
            item.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    abrirCompraDetalleSlideOver(compra);
                }
            });
            comprasContainer.appendChild(item);
        });
    }

    var editarBtn = document.getElementById('proveedorSlideOverEditarBtn');
    editarBtn.onclick = function () {
        cerrarProveedorSlideOver();
        window.abrirEditarProveedor(id);
    };

    var eliminarBtn = document.getElementById('proveedorSlideOverEliminarBtn');
    eliminarBtn.onclick = function () { eliminarProveedor(id); };

    slideOver.classList.add('is-open');
    slideOver.setAttribute('aria-hidden', 'false');
    overlay.classList.add('is-visible');
}

function cerrarProveedorSlideOver() {
    var overlay = document.getElementById('proveedorSlideOverOverlay');
    var slideOver = document.getElementById('proveedorSlideOver');
    slideOver.classList.remove('is-open');
    slideOver.setAttribute('aria-hidden', 'true');
    overlay.classList.remove('is-visible');
}

/* --------------------------------------------------------------------
 * Slide-over de detalle de una compra (se abre encima del de proveedor)
 * -mismo contenido que el detalle de compra en Inventario.
 * ------------------------------------------------------------------ */
var COMPRA_FACTURA_LABELS = { validada: 'Validada', por_validar: 'Por validar', sin_factura: 'Compra informal' };
var COMPRA_FACTURA_PILL_CLASS = { validada: 'status-pill--facturada', por_validar: 'status-pill--pendiente', sin_factura: 'status-pill--sin-facturar' };

function abrirCompraDetalleSlideOver(compra) {
    var overlay = document.getElementById('compraDetalleOverlay');
    var slideOver = document.getElementById('compraDetalleSlideOver');
    if (!overlay || !slideOver) {
        return;
    }

    document.getElementById('compraDetalleTitulo').textContent = 'Compra #' + compra.id;

    var estadoPill = document.getElementById('compraDetalleEstado');
    estadoPill.className = 'status-pill ' + COMPRA_FACTURA_PILL_CLASS[compra.facturaEstado];
    estadoPill.textContent = COMPRA_FACTURA_LABELS[compra.facturaEstado];

    document.getElementById('compraDetalleTotal').textContent = formatCOP(compra.total);

    var cufeRow = document.getElementById('compraDetalleCufeRow');
    if (compra.cufe) {
        cufeRow.hidden = false;
        document.getElementById('compraDetalleCufe').textContent = compra.cufe;
    } else {
        cufeRow.hidden = true;
    }

    var lineasContainer = document.getElementById('compraDetalleLineas');
    lineasContainer.innerHTML = '';
    compra.lineas.forEach(function (linea) {
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

function cerrarCompraDetalleSlideOver() {
    var overlay = document.getElementById('compraDetalleOverlay');
    var slideOver = document.getElementById('compraDetalleSlideOver');
    slideOver.classList.remove('is-open');
    slideOver.setAttribute('aria-hidden', 'true');
    overlay.classList.remove('is-visible');
}

(function wireCompraDetalleSlideOverClose() {
    var overlay = document.getElementById('compraDetalleOverlay');
    var slideOver = document.getElementById('compraDetalleSlideOver');
    var closeBtn = document.getElementById('compraDetalleClose');
    if (!overlay || !slideOver || !closeBtn) {
        return;
    }
    closeBtn.addEventListener('click', cerrarCompraDetalleSlideOver);
    overlay.addEventListener('click', cerrarCompraDetalleSlideOver);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && slideOver.classList.contains('is-open')) {
            cerrarCompraDetalleSlideOver();
        }
    });
})();

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

(function wireProveedorSlideOverClose() {
    var overlay = document.getElementById('proveedorSlideOverOverlay');
    var slideOver = document.getElementById('proveedorSlideOver');
    var closeBtn = document.getElementById('proveedorSlideOverClose');
    if (!overlay || !slideOver || !closeBtn) {
        return;
    }
    closeBtn.addEventListener('click', cerrarProveedorSlideOver);
    overlay.addEventListener('click', cerrarProveedorSlideOver);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && slideOver.classList.contains('is-open')) {
            cerrarProveedorSlideOver();
        }
    });
})();

/**
 * No se puede borrar un proveedor con compras registradas -el backend ya
 * lo valida, acá solo se muestra el mensaje de error si pasa.
 */
function eliminarProveedor(id) {
    var proveedor = proveedoresById[id];
    if (!proveedor) {
        return;
    }

    confirmarAccion({
        titulo: '¿Eliminar este proveedor?',
        texto: '"' + proveedor.nombre + '" se quitará de tu lista. Esta acción no se puede deshacer.',
        textoConfirmar: 'Sí, eliminar',
        peligro: true
    }).then(function (confirmado) {
        if (!confirmado) {
            return;
        }

        proveedoresApiRequest('DELETE', '/cliente/proveedores/' + id)
            .then(function () {
                var row = document.querySelector('#proveedoresTable .data-table__row[data-proveedor-id="' + id + '"]');
                if (row) {
                    row.remove();
                }

                var table = document.getElementById('proveedoresTable');
                var emptyEl = document.getElementById('proveedoresEmpty');
                if (table && emptyEl) {
                    emptyEl.hidden = table.querySelectorAll('.data-table__row:not([hidden])').length !== 0;
                }

                delete proveedoresById[id];

                cerrarProveedorSlideOver();
                actualizarStatsProveedores();
            })
            .catch(function (error) {
                mostrarError(error.message);
            });
    });
}

/* --------------------------------------------------------------------
 * Fila nueva de la tabla (mismo markup que el Blade) -usada al crear un
 * proveedor nuevo desde el modal. Todo va por textContent, nunca por
 * innerHTML con datos escritos por el negocio (nombre, NIT...).
 * ------------------------------------------------------------------ */
function crearFilaProveedor(proveedor) {
    var row = document.createElement('tr');
    row.className = 'data-table__row';
    row.setAttribute('data-proveedor-id', proveedor.id);
    row.tabIndex = 0;

    var celdaNombre = row.insertCell();
    var titulo = document.createElement('div');
    titulo.className = 'data-table__title';
    titulo.textContent = proveedor.nombre;
    var meta = document.createElement('div');
    meta.className = 'data-table__meta';
    meta.textContent = proveedor.tipoPersona === 'juridica' ? 'Persona jurídica' : 'Persona natural';
    celdaNombre.appendChild(titulo);
    celdaNombre.appendChild(meta);

    var celdaNit = row.insertCell();
    celdaNit.className = 'data-table__meta';
    celdaNit.textContent = proveedor.nit + (proveedor.dv ? '-' + proveedor.dv : '');

    var celdaTelefono = row.insertCell();
    celdaTelefono.className = 'data-table__meta';
    celdaTelefono.textContent = proveedor.telefono || '—';

    var celdaCompras = row.insertCell();
    celdaCompras.className = 'data-table__meta';
    celdaCompras.textContent = proveedor.comprasCount;

    var celdaTotal = row.insertCell();
    celdaTotal.className = 'data-table__title';
    celdaTotal.textContent = formatCOP(proveedor.totalComprado);

    row.addEventListener('click', function () { abrirProveedorSlideOver(proveedor.id); });
    row.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            abrirProveedorSlideOver(proveedor.id);
        }
    });

    return row;
}

function actualizarFilaProveedor(proveedor) {
    var row = document.querySelector('#proveedoresTable .data-table__row[data-proveedor-id="' + proveedor.id + '"]');
    if (row) {
        row.cells[0].querySelector('.data-table__title').textContent = proveedor.nombre;
        row.cells[0].querySelector('.data-table__meta').textContent = proveedor.tipoPersona === 'juridica' ? 'Persona jurídica' : 'Persona natural';
        row.cells[1].textContent = proveedor.nit + (proveedor.dv ? '-' + proveedor.dv : '');
        row.cells[2].textContent = proveedor.telefono || '—';
    }
}

function actualizarStatsProveedores() {
    var statProveedores = document.getElementById('statProveedores');
    if (statProveedores) {
        statProveedores.textContent = formatNumber(Object.keys(proveedoresById).length, 0);
    }
}

/* --------------------------------------------------------------------
 * 3. Modal "Nuevo/Editar proveedor"
 * ------------------------------------------------------------------ */
function initProveedorModal() {
    var openBtn = document.getElementById('nuevoProveedorBtn');
    var modal = document.getElementById('proveedorModal');
    var overlay = document.getElementById('proveedorModalOverlay');
    if (!openBtn || !modal || !overlay) {
        return;
    }

    var closeBtn = document.getElementById('proveedorModalClose');
    var titleEl = document.getElementById('proveedorModalTitle');
    var nombreInput = document.getElementById('provNombre');
    var nitInput = document.getElementById('provNit');
    var dvInput = document.getElementById('provDv');
    var tipoPersonaSelect = document.getElementById('provTipoPersona');
    var regimenInput = document.getElementById('provRegimenFiscal');
    var telefonoInput = document.getElementById('provTelefono');
    var correoInput = document.getElementById('provCorreo');
    var direccionInput = document.getElementById('provDireccion');
    var departamentoSelect = document.getElementById('provDepartamento');
    var ciudadSelect = document.getElementById('provCiudad');
    var guardarBtn = document.getElementById('provGuardarBtn');

    // null = modo "crear"; con un id = modo "editar" ese proveedor.
    var proveedorEditandoId = null;

    /* ---------- Departamento -> Ciudad (selects dependientes) ----------
     * Mismo dataset real que ya usa el registro (window.COLOMBIA_LOCATIONS,
     * de colombia-locations.js) -no se escribe la lista a mano acá, y así
     * los dos formularios quedan consistentes con la misma fuente. */
    var locations = window.COLOMBIA_LOCATIONS || [];

    locations.forEach(function (loc) {
        var option = document.createElement('option');
        option.value = loc.departamento;
        option.textContent = loc.departamento;
        departamentoSelect.appendChild(option);
    });

    function fillCiudades(departamentoNombre, preseleccionar) {
        var match = locations.filter(function (loc) { return loc.departamento === departamentoNombre; })[0];

        ciudadSelect.innerHTML = '';

        if (!match) {
            var placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.disabled = true;
            placeholder.selected = true;
            placeholder.textContent = 'Elige un departamento';
            ciudadSelect.appendChild(placeholder);
            ciudadSelect.disabled = true;
            return;
        }

        ciudadSelect.disabled = false;

        var elegir = document.createElement('option');
        elegir.value = '';
        elegir.disabled = true;
        elegir.textContent = 'Selecciona';
        ciudadSelect.appendChild(elegir);

        var tienePreseleccion = false;
        match.ciudades.forEach(function (nombreCiudad) {
            var option = document.createElement('option');
            option.value = nombreCiudad;
            option.textContent = nombreCiudad;
            if (nombreCiudad === preseleccionar) {
                option.selected = true;
                tienePreseleccion = true;
            }
            ciudadSelect.appendChild(option);
        });

        elegir.selected = !tienePreseleccion;
    }

    departamentoSelect.addEventListener('change', function () {
        fillCiudades(departamentoSelect.value, '');
    });

    function updateGuardarState() {
        guardarBtn.disabled = nombreInput.value.trim() === '' || nitInput.value.trim() === '';
    }

    [nombreInput, nitInput].forEach(function (input) {
        input.addEventListener('input', updateGuardarState);
    });

    function resetModalVacio() {
        nombreInput.value = '';
        nitInput.value = '';
        dvInput.value = '';
        tipoPersonaSelect.value = 'juridica';
        regimenInput.value = '';
        telefonoInput.value = '';
        correoInput.value = '';
        direccionInput.value = '';
        departamentoSelect.value = '';
        fillCiudades('', '');
        updateGuardarState();
    }

    function llenarFormulario(proveedor) {
        nombreInput.value = proveedor.nombre;
        nitInput.value = proveedor.nit || '';
        dvInput.value = proveedor.dv || '';
        tipoPersonaSelect.value = proveedor.tipoPersona || 'juridica';
        regimenInput.value = proveedor.regimenFiscal || '';
        telefonoInput.value = proveedor.telefono || '';
        correoInput.value = proveedor.correo || '';
        direccionInput.value = proveedor.direccion || '';
        departamentoSelect.value = proveedor.departamento || '';
        fillCiudades(proveedor.departamento || '', proveedor.ciudad || '');
        updateGuardarState();
    }

    function openModal(proveedor) {
        proveedorEditandoId = proveedor ? proveedor.id : null;

        if (proveedor) {
            titleEl.textContent = 'Editar proveedor';
            guardarBtn.textContent = 'Guardar cambios';
            llenarFormulario(proveedor);
        } else {
            titleEl.textContent = 'Nuevo proveedor';
            guardarBtn.textContent = 'Guardar proveedor';
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
    window.abrirEditarProveedor = function (id) {
        var proveedor = proveedoresById[id];
        if (proveedor) {
            openModal(proveedor);
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

        var originalText = guardarBtn.textContent;
        guardarBtn.disabled = true;
        guardarBtn.textContent = proveedorEditandoId ? 'Guardando cambios...' : 'Guardando...';

        var payload = {
            nombre: nombreInput.value.trim(),
            nit: nitInput.value.trim(),
            dv: dvInput.value.trim() || null,
            tipo_persona: tipoPersonaSelect.value,
            regimen_fiscal: regimenInput.value.trim() || null,
            telefono: telefonoInput.value.trim() || null,
            correo: correoInput.value.trim() || null,
            direccion: direccionInput.value.trim() || null,
            departamento: departamentoSelect.value || null,
            ciudad: ciudadSelect.value || null
        };

        var url = proveedorEditandoId ? '/cliente/proveedores/' + proveedorEditandoId : '/cliente/proveedores';
        var method = proveedorEditandoId ? 'PUT' : 'POST';

        proveedoresApiRequest(method, url, payload)
            .then(function (json) {
                var proveedor = json.proveedor;
                proveedoresById[proveedor.id] = proveedor;

                if (proveedorEditandoId) {
                    actualizarFilaProveedor(proveedor);
                } else {
                    var tbody = document.querySelector('#proveedoresTable tbody');
                    if (tbody) {
                        tbody.appendChild(crearFilaProveedor(proveedor));
                    }
                    var emptyEl = document.getElementById('proveedoresEmpty');
                    if (emptyEl) {
                        emptyEl.hidden = true;
                    }
                    actualizarStatsProveedores();
                }

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
