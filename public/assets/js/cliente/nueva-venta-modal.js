/**
 * Stockly — Panel del negocio cliente: modal "Nueva venta" (vanilla JS)
 * Compartido entre el Dashboard y la vista Ventas -ambas páginas incluyen
 * el partial cliente/partials/nueva-venta-modal.blade.php y cargan este
 * mismo script. Depende de cliente/layout.js (formatCOP, normalizarTexto)
 * ya cargado antes que este.
 *
 * El total y el cambio se calculan solo en el cliente, y el pago digital
 * se simula (así es como funcionaría de verdad con el webhook de Wompi,
 * según el modelo de negocio -acá solo con un setTimeout, no hay
 * integración real con la pasarela todavía). "Registrar venta" sí
 * persiste de verdad contra POST /cliente/ventas.
 */

document.addEventListener('DOMContentLoaded', function () {
    initNuevaVentaModal();
});

/** Mismo helper que inventarioApiRequest/proveedoresApiRequest, pero
 * self-contained acá porque este modal se incluye también en el
 * Dashboard, que no carga ventas.js. */
function ventasApiRequest(method, url, data) {
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

function initNuevaVentaModal() {
    var openButtons = document.querySelectorAll('#nuevaVentaBtn');
    var modal = document.getElementById('nuevaVentaModal');
    var overlay = document.getElementById('nuevaVentaOverlay');

    if (openButtons.length === 0 || !modal || !overlay) {
        return;
    }

    var productos = JSON.parse(document.getElementById('ventaProductosData').textContent);

    var closeBtn = document.getElementById('nuevaVentaClose');
    var searchInput = document.getElementById('ventaProductoSearch');
    var resultsBox = document.getElementById('ventaProductoResults');
    var linesContainer = document.getElementById('ventaLines');
    var linesEmpty = document.getElementById('ventaLinesEmpty');
    var totalEl = document.getElementById('ventaTotal');
    var btnEfectivo = document.getElementById('ventaBtnEfectivo');
    var btnDigital = document.getElementById('ventaBtnDigital');
    var panelEfectivo = document.getElementById('ventaPagoEfectivo');
    var panelDigital = document.getElementById('ventaPagoDigital');
    var montoRecibidoInput = document.getElementById('ventaMontoRecibido');
    var cambioEl = document.getElementById('ventaCambio');
    var qrBox = document.getElementById('ventaQrBox');
    var qrStatus = document.getElementById('ventaQrStatus');
    var registrarBtn = document.getElementById('ventaRegistrarBtn');
    var quiereFacturaCheckbox = document.getElementById('ventaQuiereFactura');
    var facturaPanel = document.getElementById('ventaFacturaPanel');
    var compradorTipoDocumentoSelect = document.getElementById('ventaCompradorTipoDocumento');
    var compradorNumeroDocumentoInput = document.getElementById('ventaCompradorNumeroDocumento');
    var compradorNombreInput = document.getElementById('ventaCompradorNombre');

    var lineas = [];
    var metodoPago = 'efectivo';
    var digitalConfirmado = false;
    var digitalTimeout = null;

    function getTotal() {
        return lineas.reduce(function (sum, linea) {
            return sum + linea.cantidad * linea.precio;
        }, 0);
    }

    /** "Monto recibido" es un input de texto (no number) justo para poder
     * mostrar puntos de miles mientras se escribe, igual que el resto de
     * la plata en la app -se guarda solo el dígito, el punto es cosmético. */
    function getMontoRecibido() {
        var digitos = montoRecibidoInput.value.replace(/\D/g, '');
        return digitos ? parseInt(digitos, 10) : 0;
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
                        '<div class="venta-line__precio">' + formatCOP(linea.precio) + ' c/u</div>' +
                    '</div>' +
                    '<div class="venta-line__qty">' +
                        '<button type="button" class="venta-line__qty-btn" data-action="dec">−</button>' +
                        '<input type="text" inputmode="numeric" class="venta-line__qty-value venta-line__qty-value--input" value="' + linea.cantidad + '">' +
                        '<button type="button" class="venta-line__qty-btn" data-action="inc">+</button>' +
                    '</div>' +
                    '<div class="venta-line__subtotal">' + formatCOP(linea.cantidad * linea.precio) + '</div>' +
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
                // fácil que darle a "+" muchas veces). Se actualiza en vivo
                // sin reconstruir la fila -si no, el cursor/foco se
                // perdería mientras escribes.
                var qtyInputLinea = row.querySelector('.venta-line__qty-value--input');
                qtyInputLinea.addEventListener('input', function (event) {
                    var digitos = event.target.value.replace(/\D/g, '');
                    event.target.value = digitos;
                    linea.cantidad = digitos ? parseInt(digitos, 10) : 0;
                    row.querySelector('.venta-line__subtotal').textContent = formatCOP(linea.cantidad * linea.precio);
                    updateTotalsAndState();
                });
                qtyInputLinea.addEventListener('blur', function () {
                    if (!linea.cantidad || linea.cantidad < 1) {
                        linea.cantidad = 1;
                        qtyInputLinea.value = '1';
                        row.querySelector('.venta-line__subtotal').textContent = formatCOP(linea.cantidad * linea.precio);
                        updateTotalsAndState();
                    }
                });

                linesContainer.appendChild(row);
            });
        }

        updateTotalsAndState();
    }

    function updateTotalsAndState() {
        var total = getTotal();
        totalEl.textContent = formatCOP(total);
        updateCambio();
        updateRegistrarState();
    }

    function updateCambio() {
        var total = getTotal();
        var recibido = getMontoRecibido();
        var cambio = recibido - total;
        cambioEl.textContent = formatCOP(Math.max(0, cambio));
        cambioEl.classList.toggle('is-negative', cambio < 0);
    }

    function updateRegistrarState() {
        var total = getTotal();
        var hayLineas = lineas.length > 0;

        if (!hayLineas) {
            registrarBtn.disabled = true;
            return;
        }

        if (quiereFacturaCheckbox.checked && (!compradorNombreInput.value.trim() || !compradorNumeroDocumentoInput.value.trim())) {
            registrarBtn.disabled = true;
            return;
        }

        if (metodoPago === 'efectivo') {
            registrarBtn.disabled = getMontoRecibido() < total;
        } else {
            registrarBtn.disabled = !digitalConfirmado;
        }
    }

    /* ---------- Buscar y agregar producto ---------- */
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
            empty.textContent = 'No se encontró ningún producto.';
            resultsBox.appendChild(empty);
        } else {
            matches.forEach(function (producto) {
                var item = document.createElement('div');
                item.className = 'venta-product-result';
                // producto.nombre es texto libre -va por textContent más
                // abajo, nunca directo en el innerHTML.
                item.innerHTML =
                    '<span class="venta-product-result__nombre"></span>' +
                    '<span class="venta-product-result__precio">' + formatCOP(producto.precio) + '</span>';
                item.querySelector('.venta-product-result__nombre').textContent = producto.nombre;

                item.addEventListener('click', function () {
                    var existente = lineas.find(function (l) { return l.id === producto.id; });
                    if (existente) {
                        existente.cantidad++;
                    } else {
                        lineas.push({ id: producto.id, nombre: producto.nombre, precio: producto.precio, cantidad: 1 });
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

    /* ---------- ¿Necesita factura a su nombre? ----------
     * Solo guarda quién la pidió (para que Facturación la recoja más
     * adelante) -no genera ningún documento DIAN acá todavía. */
    quiereFacturaCheckbox.addEventListener('change', function () {
        facturaPanel.hidden = !quiereFacturaCheckbox.checked;
        updateRegistrarState();
    });

    compradorNombreInput.addEventListener('input', updateRegistrarState);
    compradorNumeroDocumentoInput.addEventListener('input', updateRegistrarState);

    /* ---------- Método de pago ---------- */
    function setMetodo(nuevo) {
        metodoPago = nuevo;
        btnEfectivo.classList.toggle('is-active', nuevo === 'efectivo');
        btnDigital.classList.toggle('is-active', nuevo === 'digital');
        panelEfectivo.hidden = nuevo !== 'efectivo';
        panelDigital.hidden = nuevo !== 'digital';
        updateRegistrarState();
    }

    btnEfectivo.addEventListener('click', function () { setMetodo('efectivo'); });
    btnDigital.addEventListener('click', function () {
        setMetodo('digital');
        simularConfirmacionDigital();
    });

    montoRecibidoInput.addEventListener('input', function () {
        var valor = getMontoRecibido();
        montoRecibidoInput.value = valor ? valor.toLocaleString('es-CO') : '';
        updateTotalsAndState();
    });

    function simularConfirmacionDigital() {
        digitalConfirmado = false;
        qrBox.classList.remove('is-confirmed');
        qrStatus.textContent = 'El comprador escanea el QR de Wompi para pagar...';
        updateRegistrarState();

        window.clearTimeout(digitalTimeout);
        digitalTimeout = window.setTimeout(function () {
            digitalConfirmado = true;
            qrBox.classList.add('is-confirmed');
            qrStatus.textContent = '¡Pago confirmado por Wompi!';
            updateRegistrarState();
        }, 2200);
    }

    /* ---------- Abrir / cerrar el modal ---------- */
    function resetModal() {
        lineas = [];
        metodoPago = 'efectivo';
        digitalConfirmado = false;
        window.clearTimeout(digitalTimeout);
        searchInput.value = '';
        resultsBox.hidden = true;
        montoRecibidoInput.value = '';
        quiereFacturaCheckbox.checked = false;
        facturaPanel.hidden = true;
        compradorTipoDocumentoSelect.value = 'CC';
        compradorNumeroDocumentoInput.value = '';
        compradorNombreInput.value = '';
        setMetodo('efectivo');
        renderLineas();
    }

    function openModal() {
        resetModal();
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-visible');
        window.setTimeout(function () { searchInput.focus(); }, 250);
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-visible');
        window.clearTimeout(digitalTimeout);
    }

    openButtons.forEach(function (button) {
        button.addEventListener('click', openModal);
    });

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
            metodo_pago: metodoPago,
            monto_recibido: metodoPago === 'efectivo' ? getMontoRecibido() : null,
            pago_confirmado: metodoPago === 'digital' ? digitalConfirmado : null,
            quiere_factura: quiereFacturaCheckbox.checked,
            comprador_tipo_documento: quiereFacturaCheckbox.checked ? compradorTipoDocumentoSelect.value : null,
            comprador_numero_documento: quiereFacturaCheckbox.checked ? compradorNumeroDocumentoInput.value.trim() : null,
            comprador_nombre: quiereFacturaCheckbox.checked ? compradorNombreInput.value.trim() : null,
            lineas: lineas.map(function (linea) {
                return { producto_id: linea.id, cantidad: linea.cantidad };
            })
        };

        ventasApiRequest('POST', '/cliente/ventas', payload)
            .then(function (json) {
                // El precio "de hoy" que mostraba el buscador puede ya no
                // coincidir con el que quedó guardado en la venta (precio
                // histórico) -no se toca acá, solo se refresca el stock.
                (json.productosActualizados || []).forEach(function (actualizado) {
                    var producto = productos.find(function (p) { return p.id === actualizado.id; });
                    if (producto) {
                        producto.stockVitrina = actualizado.stockVitrina;
                    }
                });

                if (window.agregarVentaALaTabla) {
                    window.agregarVentaALaTabla(json.venta);
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
