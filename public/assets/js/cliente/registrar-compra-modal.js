/**
 * Stockly — Panel del negocio cliente: modal "Registrar compra"
 * Compartido entre Inventario y el Dashboard -ambas páginas incluyen el
 * partial cliente/partials/registrar-compra-modal.blade.php y cargan este
 * mismo script, igual patrón que nueva-venta-modal.js. Depende de
 * cliente/layout.js (formatCOP, formatNumber, normalizarTexto,
 * formatearInputDinero, valorDineroInput, mostrarError) ya cargado antes
 * que este.
 *
 * Self-contained: tiene su propio helper de API y su propia lista local
 * de productos (no depende de las variables globales de inventario.js,
 * que en el Dashboard no existen). Si la página SÍ es Inventario, avisa
 * al terminar vía window.actualizarInventarioTrasCompra(json) -guardado
 * si existe- para que esa página actualice su propia tabla de compras y
 * el stock en vitrina/bodega; en el Dashboard simplemente no existe ese
 * hook y no pasa nada más que cerrar el modal.
 */

document.addEventListener('DOMContentLoaded', function () {
    initRegistrarCompraModal();
});

function compraApiRequest(method, url, data) {
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

function initRegistrarCompraModal() {
    var openBtn = document.getElementById('registrarCompraBtn');
    var modal = document.getElementById('registrarCompraModal');
    var overlay = document.getElementById('registrarCompraOverlay');
    var dataScript = document.getElementById('compraProductosData');
    if (!openBtn || !modal || !overlay || !dataScript) {
        return;
    }

    var productos = JSON.parse(dataScript.textContent);

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
    var btnEfectivo = document.getElementById('compraBtnEfectivo');
    var btnDigital = document.getElementById('compraBtnDigital');
    var btnOrigenHoy = document.getElementById('compraBtnOrigenHoy');
    var btnOrigenExterno = document.getElementById('compraBtnOrigenExterno');
    var metodoHint = document.getElementById('compraMetodoHint');

    var tipo = 'proveedor';
    // Dos elecciones independientes que se combinan en un solo valor para
    // el backend: metodo (efectivo/digital) + origen (hoy/externo) ->
    // "efectivo", "efectivo_externo", "digital" o "digital_externo".
    var metodo = 'efectivo';
    var origen = 'hoy';
    var lineas = [];
    var validarTimeout = null;

    var METODO_HINTS = {
        efectivo_hoy: 'Pagaste con la plata física que está en la caja del negocio -se descuenta del cierre de caja de hoy.',
        efectivo_externo: 'Pagaste con plata que no estaba en la caja del negocio (ahorros, otro momento) -no se descuenta de ningún cierre de caja.',
        digital_hoy: 'Pagaste con la plata digital que recibiste hoy (Wompi/transferencia) -se descuenta del total esperado en digital de hoy.',
        digital_externo: 'Pagaste con plata digital que no era de hoy (ahorros, otro momento) -no se descuenta de ningún cierre de caja.'
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

        var matches = productos.filter(function (producto) {
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
        setMetodo('efectivo');
        setOrigen('hoy');
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
            metodo_pago: metodoPagoFinal(),
            lineas: lineas.map(function (l) {
                return { producto_id: l.id, cantidad: l.cantidad, costo: l.costo };
            })
        };

        compraApiRequest('POST', '/cliente/inventario/compras', payload)
            .then(function (json) {
                if (typeof window.actualizarInventarioTrasCompra === 'function') {
                    window.actualizarInventarioTrasCompra(json);
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
