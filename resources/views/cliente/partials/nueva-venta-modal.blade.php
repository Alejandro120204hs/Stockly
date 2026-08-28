{{-- Modal "Nueva venta" — compartido entre el dashboard y la vista de
     Ventas (@include en ambos), un solo botón con id="nuevaVentaBtn" en
     cada página lo abre. Los campos siguen las columnas reales de
     ventas/venta_detalle/pagos_efectivo/pagos_pasarela. $productosVenta
     lo entregan tanto VentasController como DashboardController. --}}

<div class="modal-overlay" id="nuevaVentaOverlay"></div>

<div class="modal" id="nuevaVentaModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="nuevaVentaTitle">
    <div class="modal__header">
        <h2 class="modal__title" id="nuevaVentaTitle">Nueva venta</h2>
        <button type="button" class="modal__close" id="nuevaVentaClose" aria-label="Cerrar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 6l12 12M18 6 6 18"/>
            </svg>
        </button>
    </div>

    <div class="modal__body">
        <div class="venta-product-search">
            <label for="ventaProductoSearch" class="cliente-label">Buscar producto en vitrina</label>
            <input type="text" id="ventaProductoSearch" class="cliente-input" placeholder="Ej: aguardiente, ron, cerveza..." autocomplete="off">
            <div class="venta-product-results" id="ventaProductoResults" hidden></div>
        </div>

        <div class="venta-lines" id="ventaLines">
            <p class="venta-lines__empty" id="ventaLinesEmpty">Todavía no has agregado productos.</p>
        </div>

        <div class="venta-total-row">
            <span>Total</span>
            <strong id="ventaTotal">$0</strong>
        </div>

        <label class="venta-factura-toggle">
            <input type="checkbox" id="ventaQuiereFactura">
            <span>¿El cliente necesita factura a su nombre?</span>
        </label>

        <div class="venta-factura-panel" id="ventaFacturaPanel" hidden>
            <div class="venta-factura-panel__row">
                <div>
                    <label for="ventaCompradorTipoDocumento" class="cliente-label">Tipo de documento</label>
                    <select id="ventaCompradorTipoDocumento" class="cliente-input">
                        <option value="CC">Cédula</option>
                        <option value="NIT">NIT</option>
                    </select>
                </div>
                <div>
                    <label for="ventaCompradorNumeroDocumento" class="cliente-label">Número de documento</label>
                    <input type="text" id="ventaCompradorNumeroDocumento" class="cliente-input" placeholder="Ej: 1020304050" autocomplete="off">
                </div>
            </div>
            <label for="ventaCompradorNombre" class="cliente-label">Nombre completo</label>
            <input type="text" id="ventaCompradorNombre" class="cliente-input" placeholder="Nombre de quien recibe la factura" autocomplete="off">
        </div>

        <label class="cliente-label">Método de pago</label>
        <div class="venta-payment-toggle">
            <button type="button" class="venta-payment-btn is-active" data-metodo="efectivo" id="ventaBtnEfectivo">Efectivo</button>
            <button type="button" class="venta-payment-btn" data-metodo="digital" id="ventaBtnDigital">Wompi (digital)</button>
        </div>

        <div class="venta-payment-panel" id="ventaPagoEfectivo">
            <label for="ventaMontoRecibido" class="cliente-label">Monto recibido</label>
            <input type="text" inputmode="numeric" id="ventaMontoRecibido" class="cliente-input" placeholder="0" autocomplete="off">
            <div class="venta-cambio-row">
                <span>Cambio a devolver</span>
                <strong id="ventaCambio">$0</strong>
            </div>
        </div>

        <div class="venta-payment-panel" id="ventaPagoDigital" hidden>
            <div class="venta-qr-box" id="ventaQrBox">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <path d="M14 14h3v3h-3zM19 14h2v2h-2zM14 19h2v2h-2zM19 19h2v2h-2z"/>
                </svg>
                <span class="venta-qr-box__status" id="ventaQrStatus">El comprador escanea el QR de Wompi para pagar</span>
            </div>
        </div>
    </div>

    <div class="modal__footer">
        <button type="button" class="cliente-btn-primary" id="ventaRegistrarBtn" disabled>Registrar venta</button>
    </div>
</div>

<script id="ventaProductosData" type="application/json">{!! json_encode($productosVenta) !!}</script>
