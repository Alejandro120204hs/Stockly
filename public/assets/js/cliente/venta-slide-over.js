/**
 * Stockly — Panel del negocio cliente: panel de detalle de una venta
 * Compartido entre Ventas y el Dashboard -ambas páginas incluyen el
 * partial cliente/partials/venta-slide-over.blade.php y cargan este mismo
 * script, igual que ya pasa con nueva-venta-modal.js. Depende de
 * cliente/layout.js (formatCOP) ya cargado antes que este.
 *
 * window.abrirVentaSlideOver(venta) recibe un objeto con la misma forma
 * que App\Models\Cliente\Venta::toResumenArray().
 */

document.addEventListener('DOMContentLoaded', function () {
    initVentaSlideOver();
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

function initVentaSlideOver() {
    var overlay = document.getElementById('ventaSlideOverOverlay');
    var slideOver = document.getElementById('ventaSlideOver');
    var closeBtn = document.getElementById('ventaSlideOverClose');
    var anularBtn = document.getElementById('ventaSlideOverAnularBtn');
    var anuladaBanner = document.getElementById('ventaSlideOverAnuladaBanner');

    if (!overlay || !slideOver || !closeBtn) {
        return;
    }

    var facturacionLabels = {
        sin_facturar: 'Sin facturar',
        facturada_individual: 'Facturada',
        incluida_en_consolidado: 'En consolidado'
    };

    var ventaActual = null;

    function cerrar() {
        slideOver.classList.remove('is-open');
        slideOver.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-visible');
    }

    window.abrirVentaSlideOver = function (venta) {
        if (!venta) {
            return;
        }

        ventaActual = venta;

        document.getElementById('ventaSlideOverTitulo').textContent = 'Venta #' + venta.id;
        document.getElementById('ventaSlideOverReciboBtn').href = '/cliente/ventas/' + venta.id + '/recibo';

        if (anuladaBanner) {
            anuladaBanner.hidden = !venta.anulada;
        }
        if (anularBtn) {
            anularBtn.hidden = !!venta.anulada;
        }

        var estadoPill = document.getElementById('ventaSlideOverEstadoPago');
        if (venta.anulada) {
            estadoPill.className = 'status-pill status-pill--sin-facturar';
            estadoPill.textContent = 'Anulada';
        } else {
            estadoPill.className = 'status-pill status-pill--' + venta.estadoPago;
            estadoPill.textContent = venta.estadoPago === 'pagada' ? 'Pagada' : 'Pendiente';
        }

        document.getElementById('ventaSlideOverMetodo').textContent = venta.metodo === 'efectivo' ? 'Efectivo' : 'Digital (Wompi)';
        document.getElementById('ventaSlideOverTotal').textContent = formatCOP(venta.total);
        document.getElementById('ventaSlideOverGanancia').textContent = formatCOP(venta.ganancia);
        document.getElementById('ventaSlideOverFacturacion').textContent = facturacionLabels[venta.estadoFacturacion];

        // "comprador" solo existe si pidieron factura a su nombre al
        // registrar la venta -no significa que ya se haya facturado
        // (eso lo hará Facturación más adelante), solo que hay a quién.
        var compradorRow = document.getElementById('ventaSlideOverCompradorRow');
        if (venta.comprador) {
            document.getElementById('ventaSlideOverComprador').textContent =
                venta.comprador.nombre + ' (' + venta.comprador.tipoDocumento + ' ' + venta.comprador.numeroDocumento + ')';
            compradorRow.hidden = false;
        } else {
            compradorRow.hidden = true;
        }

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
    };

    window.cerrarVentaSlideOver = cerrar;

    closeBtn.addEventListener('click', cerrar);
    overlay.addEventListener('click', cerrar);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && slideOver.classList.contains('is-open')) {
            cerrar();
        }
    });

    // No existe "editar" una venta a propósito -si el cajero se
    // equivocó, se anula esta y se registra una nueva correcta (igual
    // que la mayoría de sistemas de punto de venta reales).
    if (anularBtn) {
        anularBtn.addEventListener('click', function () {
            if (!ventaActual) {
                return;
            }

            confirmarAccion({
                titulo: '¿Anular esta venta?',
                texto: 'El stock vendido vuelve a vitrina y la venta deja de contar en las ventas y la ganancia del negocio. Esta acción no se puede deshacer.',
                textoConfirmar: 'Sí, anular',
                peligro: true
            }).then(function (confirmado) {
                if (!confirmado) {
                    return;
                }

                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                var idAnterior = ventaActual.id;

                fetch('/cliente/ventas/' + idAnterior + '/anular', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : ''
                    }
                }).then(function (response) {
                    return response.json().catch(function () { return {}; }).then(function (json) {
                        if (!response.ok) {
                            throw new Error(json.message || 'Ocurrió un error inesperado.');
                        }
                        return json;
                    });
                }).then(function (json) {
                    window.abrirVentaSlideOver(json.venta);

                    // Cada página (Ventas, Dashboard) define su propia
                    // versión de este hook si le interesa reflejar la
                    // anulación en su tabla/stats -acá no se sabe cuál
                    // página es esta, por eso se comprueba antes de usarlo.
                    if (window.marcarVentaAnulada) {
                        window.marcarVentaAnulada(json.venta, json.productosActualizados || []);
                    }
                }).catch(function (error) {
                    mostrarError(error.message);
                });
            });
        });
    }
}
