{{-- Modal "Abrir caja" — solo se usa en el Dashboard, como acceso rápido
     (igual que "Nueva venta"/"Registrar compra" ahí mismo). La página de
     Caja tiene su propio flujo completo (hero + recibo en vivo), no
     necesita este modal -por eso vive acá y no en cliente/partials
     compartido con esa vista. --}}
<div class="modal-overlay" id="abrirCajaOverlay"></div>

<div class="modal" id="abrirCajaModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="abrirCajaTitle" style="width:min(380px, calc(100% - 32px));">
    <div class="modal__header">
        <h2 class="modal__title" id="abrirCajaTitle">Abrir caja</h2>
        <button type="button" class="modal__close" id="abrirCajaClose" aria-label="Cerrar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 6l12 12M18 6 6 18"/>
            </svg>
        </button>
    </div>

    <div class="modal__body">
        <label for="dashboardCajaBaseInicial" class="cliente-label">Base de efectivo inicial</label>
        <input type="text" id="dashboardCajaBaseInicial" class="cliente-input" placeholder="Ej: 150.000">
    </div>

    <div class="modal__footer">
        <button type="button" class="cliente-btn-primary" id="dashboardAbrirCajaConfirmarBtn">Abrir caja</button>
    </div>
</div>
