/**
 * Stockly — Panel del negocio cliente: modal "Nueva venta" (vanilla JS)
 * Compartido entre el Dashboard y la vista Ventas -ambas páginas incluyen
 * el partial cliente/partials/nueva-venta-modal.blade.php y cargan este
 * mismo script. Depende de cliente/layout.js (formatCOP, normalizarTexto)
 * ya cargado antes que este.
 *
 * Todo demo: arma el total solo, calcula el cambio en efectivo, y simula
 * la confirmación de un pago digital (así es como funcionaría de verdad
 * con el webhook de Wompi, según el modelo de negocio -acá solo con un
 * setTimeout). No hay backend: "Registrar venta" no persiste nada todavía.
 */

document.addEventListener('DOMContentLoaded', function () {
    initNuevaVentaModal();
});

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

    var lineas = [];
    var metodoPago = 'efectivo';
    var digitalConfirmado = false;
    var digitalTimeout = null;

    function getTotal() {
        return lineas.reduce(function (sum, linea) {
            return sum + linea.cantidad * linea.precio;
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
                        '<div class="venta-line__precio">' + formatCOP(linea.precio) + ' c/u</div>' +
                    '</div>' +
                    '<div class="venta-line__qty">' +
                        '<button type="button" class="venta-line__qty-btn" data-action="dec">−</button>' +
                        '<span class="venta-line__qty-value">' + linea.cantidad + '</span>' +
                        '<button type="button" class="venta-line__qty-btn" data-action="inc">+</button>' +
                    '</div>' +
                    '<div class="venta-line__subtotal">' + formatCOP(linea.cantidad * linea.precio) + '</div>' +
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
        var recibido = parseFloat(montoRecibidoInput.value) || 0;
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

        if (metodoPago === 'efectivo') {
            var recibido = parseFloat(montoRecibidoInput.value) || 0;
            registrarBtn.disabled = recibido < total;
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
                item.innerHTML =
                    '<span>' + producto.nombre + '</span>' +
                    '<span class="venta-product-result__precio">' + formatCOP(producto.precio) + '</span>';

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

    montoRecibidoInput.addEventListener('input', updateTotalsAndState);

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

        window.setTimeout(function () {
            registrarBtn.textContent = originalText;
            closeModal();
        }, 700);
    });
}
