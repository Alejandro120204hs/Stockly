{{-- Panel lateral de detalle de una venta — compartido entre Ventas y el
     Dashboard (@include en ambos), igual que nueva-venta-modal. Lo abre
     window.abrirVentaSlideOver(venta) en cliente/venta-slide-over.js,
     con un objeto venta con la misma forma que Venta::toResumenArray(). --}}
<div class="slide-over-overlay" id="ventaSlideOverOverlay"></div>

<aside class="slide-over" id="ventaSlideOver" aria-hidden="true">
    <div class="slide-over__header">
        <div>
            <h2 class="slide-over__title" id="ventaSlideOverTitulo">—</h2>
            <span class="status-pill" id="ventaSlideOverEstadoPago">—</span>
        </div>
        <button type="button" class="slide-over__close" id="ventaSlideOverClose" aria-label="Cerrar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 6l12 12M18 6 6 18"/>
            </svg>
        </button>
    </div>

    <div class="slide-over__body">
        <div class="venta-anulada-banner" id="ventaSlideOverAnuladaBanner" hidden>
            Esta venta está anulada -no cuenta en las ventas ni en la ganancia del negocio.
        </div>

        <section class="slide-over__section">
            <h3 class="slide-over__section-title">Productos vendidos</h3>
            <div id="ventaSlideOverLineas"></div>
        </section>

        <section class="slide-over__section">
            <h3 class="slide-over__section-title">Pago</h3>
            <div class="slide-over__field"><span>Método</span><strong id="ventaSlideOverMetodo">—</strong></div>
            <div class="slide-over__field"><span>Total</span><strong id="ventaSlideOverTotal">—</strong></div>
            <div class="slide-over__field"><span>Ganancia bruta</span><strong id="ventaSlideOverGanancia">—</strong></div>
        </section>

        <section class="slide-over__section">
            <h3 class="slide-over__section-title">Facturación</h3>
            <div class="slide-over__field"><span>Estado</span><strong id="ventaSlideOverFacturacion">—</strong></div>
            <div class="slide-over__field" id="ventaSlideOverCompradorRow" hidden><span>Factura a nombre de</span><strong id="ventaSlideOverComprador">—</strong></div>
        </section>

        <a href="#" class="cliente-btn-ghost" id="ventaSlideOverReciboBtn" style="width:100%; display:block; text-align:center; box-sizing:border-box; margin-bottom:10px;" target="_blank" rel="noopener">
            Descargar recibo (PDF)
        </a>

        <button type="button" class="cliente-btn-ghost cliente-btn-ghost--peligro" id="ventaSlideOverAnularBtn" style="width:100%;">
            Anular venta
        </button>
    </div>
</aside>
