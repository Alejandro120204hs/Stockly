{{-- Modal "Registrar compra" — compartido entre Inventario y el Dashboard
     (@include en ambos), igual patrón que nueva-venta-modal. $productosCompra
     y $proveedores los entrega tanto InventarioController como
     DashboardController. Toda compra SIEMPRE entra a bodega, nunca a
     vitrina directamente -eso es una acción manual aparte ("Transferir"). --}}
<div class="modal-overlay" id="registrarCompraOverlay"></div>

<div class="modal" id="registrarCompraModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="registrarCompraTitle">
    <div class="modal__header">
        <h2 class="modal__title" id="registrarCompraTitle">Registrar compra</h2>
        <button type="button" class="modal__close" id="registrarCompraClose" aria-label="Cerrar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 6l12 12M18 6 6 18"/>
            </svg>
        </button>
    </div>

    <div class="modal__body">
        <div class="compra-tipo-toggle">
            <button type="button" class="compra-tipo-btn is-active" id="compraTipoProveedorBtn">Con proveedor</button>
            <button type="button" class="compra-tipo-btn" id="compraTipoInformalBtn">Compra informal</button>
        </div>

        <div id="compraProveedorFields">
            <label for="compraProveedorSelect" class="cliente-label">Proveedor</label>
            <select id="compraProveedorSelect" class="cliente-toolbar__select" style="width:100%; margin-bottom:6px;">
                @if (count($proveedores) === 0)
                    <option value="" disabled selected>No tienes proveedores todavía</option>
                @else
                    @foreach ($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                    @endforeach
                @endif
            </select>
            <p class="compra-proveedor-hint">
                ¿No está en la lista? <a href="{{ url('/cliente/proveedores') }}">Regístralo en Proveedores</a> con sus datos fiscales.
            </p>

            <label for="compraCufeInput" class="cliente-label">CUFE o código de la factura</label>
            <div style="display:flex; gap:8px; margin-bottom:6px;">
                <input type="text" id="compraCufeInput" class="cliente-input" placeholder="Escanea el QR o pega el CUFE" style="flex:1;">
                <button type="button" class="cliente-btn-ghost" id="compraValidarBtn">Validar</button>
            </div>
            <p class="compra-validar-status" id="compraValidarStatus">Sin validar todavía. El QR solo confirma que la factura existe ante la DIAN -los productos se agregan abajo, a mano.</p>
        </div>

        <p class="compra-informal-hint" id="compraInformalHint" hidden>
            Compra sin factura formal -no se valida ante la DIAN, solo queda registrada internamente.
        </p>

        <div class="venta-product-search" style="margin-top:16px;">
            <label for="compraProductoSearch" class="cliente-label">Buscar producto del catálogo</label>
            <input type="text" id="compraProductoSearch" class="cliente-input" placeholder="Ej: aguardiente, ron, cerveza..." autocomplete="off">
            <div class="venta-product-results" id="compraProductoResults" hidden></div>
        </div>

        <div class="venta-lines" id="compraLines">
            <p class="venta-lines__empty" id="compraLinesEmpty">Todavía no has agregado productos.</p>
        </div>

        <div class="venta-total-row">
            <span>Total de la compra</span>
            <strong id="compraTotal">$0</strong>
        </div>

        <label class="cliente-label">Método de pago</label>
        <div class="venta-payment-toggle">
            <button type="button" class="venta-payment-btn is-active" data-valor="efectivo" id="compraBtnEfectivo">Efectivo</button>
            <button type="button" class="venta-payment-btn" data-valor="digital" id="compraBtnDigital">Digital</button>
        </div>

        <label class="cliente-label">¿De dónde salió esa plata?</label>
        <div class="venta-payment-toggle">
            <button type="button" class="venta-payment-btn is-active" data-valor="hoy" id="compraBtnOrigenHoy">De caja</button>
            <button type="button" class="venta-payment-btn" data-valor="externo" id="compraBtnOrigenExterno">Fuera de caja</button>
        </div>
        <p class="compra-metodo-hint" id="compraMetodoHint">Pagaste con la plata física que está en la caja del negocio -se descuenta del cierre de caja de hoy.</p>
    </div>

    <div class="modal__footer">
        <button type="button" class="cliente-btn-primary" id="compraRegistrarBtn" disabled>Registrar compra</button>
    </div>
</div>

<script id="compraProductosData" type="application/json">{!! json_encode($productosCompra) !!}</script>
