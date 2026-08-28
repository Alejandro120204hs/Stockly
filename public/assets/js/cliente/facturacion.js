/* ==========================================================================
   Stockly — Facturación electrónica
   Módulos: countUp, tabla con filtros y paginación, slide-over de detalle,
   modal de nueva factura (tipo + comprador + ventas seleccionadas).
   ========================================================================== */

'use strict';

const ROWS_PER_PAGE = 10;

let facturacionDocs   = [];
let facturacionById   = {};
let filteredDocs      = [];
let currentPage       = 1;

/* --------------------------------------------------------------------------
   Helpers de formato
   -------------------------------------------------------------------------- */
function formatMoney(n) {
    return '$' + Number(n).toLocaleString('es-CO');
}

/* --------------------------------------------------------------------------
   Carga de datos desde la isla JSON
   -------------------------------------------------------------------------- */
function cargarFacturacionData() {
    const el = document.getElementById('facturacionData');
    if (!el) return;
    facturacionDocs = JSON.parse(el.textContent);
    facturacionDocs.forEach(d => { facturacionById[d.id] = d; });
    filteredDocs = [...facturacionDocs];
}

/* --------------------------------------------------------------------------
   CountUp animado para las stat cards
   -------------------------------------------------------------------------- */
function initCountUp() {
    document.querySelectorAll('[data-count]').forEach(el => {
        const target = parseFloat(el.dataset.count) || 0;
        const isMoney = el.dataset.format === 'money';
        const duration = 1100;
        const start = performance.now();

        function tick(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 5);
            const value = Math.round(target * eased);

            el.textContent = isMoney ? formatMoney(value) : String(value);

            if (progress < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    });
}

/* --------------------------------------------------------------------------
   Tabla: renderizar página actual
   -------------------------------------------------------------------------- */
function renderTabla() {
    const tbody = document.querySelector('#facturacionTable tbody');
    const emptyEl = document.getElementById('facturacionEmpty');
    const pageInfo = document.getElementById('facturacionPageInfo');
    const prevBtn  = document.getElementById('facturacionPrevPage');
    const nextBtn  = document.getElementById('facturacionNextPage');

    if (!tbody) return;

    const totalPages = Math.max(1, Math.ceil(filteredDocs.length / ROWS_PER_PAGE));
    currentPage = Math.min(currentPage, totalPages);

    const slice = filteredDocs.slice(
        (currentPage - 1) * ROWS_PER_PAGE,
        currentPage * ROWS_PER_PAGE
    );

    const allRows = tbody.querySelectorAll('tr.data-table__row');
    const docIds  = new Set(slice.map(d => String(d.id)));

    allRows.forEach(row => {
        row.style.display = docIds.has(row.dataset.docId) ? '' : 'none';
    });

    const hasResults = filteredDocs.length > 0;
    emptyEl.hidden = hasResults;

    pageInfo.textContent = `Página ${currentPage} de ${totalPages}`;
    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;
}

/* --------------------------------------------------------------------------
   Tabla: filtrado
   -------------------------------------------------------------------------- */
function applyFilters() {
    const query  = (document.getElementById('facturacionSearch')?.value || '').toLowerCase();
    const tipo   = document.getElementById('facturacionTipoFilter')?.value || '';
    const estado = document.getElementById('facturacionEstadoFilter')?.value || '';

    filteredDocs = facturacionDocs.filter(doc => {
        const matchTipo   = !tipo   || doc.tipo   === tipo;
        const matchEstado = !estado || doc.estado === estado;
        const haystack    = [
            doc.numero,
            doc.comprador?.nombre  || '',
            doc.comprador?.numDoc  || '',
            doc.comprador?.tipoDoc || '',
        ].join(' ').toLowerCase();
        const matchQuery  = !query  || haystack.includes(query);
        return matchTipo && matchEstado && matchQuery;
    });

    currentPage = 1;
    renderTabla();
}

/* --------------------------------------------------------------------------
   Tabla: paginación y eventos de filtro
   -------------------------------------------------------------------------- */
function initDocumentosTable() {
    document.getElementById('facturacionSearch')?.addEventListener('input', applyFilters);
    document.getElementById('facturacionTipoFilter')?.addEventListener('change', applyFilters);
    document.getElementById('facturacionEstadoFilter')?.addEventListener('change', applyFilters);

    document.getElementById('facturacionPrevPage')?.addEventListener('click', () => {
        if (currentPage > 1) { currentPage--; renderTabla(); }
    });
    document.getElementById('facturacionNextPage')?.addEventListener('click', () => {
        const totalPages = Math.ceil(filteredDocs.length / ROWS_PER_PAGE);
        if (currentPage < totalPages) { currentPage++; renderTabla(); }
    });

    renderTabla();
}

/* --------------------------------------------------------------------------
   Slide-over: abrir y cerrar
   -------------------------------------------------------------------------- */
function openDocSlideOver(docId) {
    const doc = facturacionById[docId];
    if (!doc) return;

    const overlay   = document.getElementById('docSlideOverOverlay');
    const slideOver = document.getElementById('docSlideOver');

    // Número y estado
    document.getElementById('docSlideOverNumero').textContent = doc.numero;
    const estadoEl = document.getElementById('docSlideOverEstado');
    estadoEl.textContent  = doc.estado === 'emitida' ? 'Emitida' : 'Anulada';
    estadoEl.className    = 'status-pill ' + (doc.estado === 'emitida' ? 'status-pill--pagada' : 'status-pill--error');

    // Tipo y fecha
    const tipoMap = { factura_individual: 'Factura individual', factura_consolidada: 'Factura consolidada', dee_pos: 'DEE / POS' };
    document.getElementById('docSlideOverTipo').textContent  = tipoMap[doc.tipo] || doc.tipo;
    document.getElementById('docSlideOverFecha').textContent = doc.fecha;

    // Comprador
    const compradorSection = document.getElementById('docSlideOverCompradorSection');
    const compradorEl      = document.getElementById('docSlideOverComprador');
    if (doc.comprador) {
        compradorSection.hidden = false;
        compradorEl.innerHTML   = `
            <div class="slide-over__field"><span>Nombre</span><strong>${doc.comprador.nombre}</strong></div>
            <div class="slide-over__field"><span>${doc.comprador.tipoDoc}</span><strong>${doc.comprador.numDoc}</strong></div>
        `;
    } else {
        compradorSection.hidden = true;
    }

    // Ventas incluidas
    const ventasEl = document.getElementById('docSlideOverVentas');
    ventasEl.innerHTML = doc.ventasIds.map(vid => `
        <div class="doc-venta-row">
            <span class="doc-venta-row__num">Venta #${vid}</span>
        </div>
    `).join('');

    document.getElementById('docSlideOverTotal').textContent = formatMoney(doc.valorTotal);

    // CUFE
    document.getElementById('docSlideOverCufe').textContent = doc.cufe;

    // Botón anular — ocultar si ya está anulada
    const anularSection = document.getElementById('docAnularSection');
    if (anularSection) anularSection.hidden = doc.estado === 'anulada';

    overlay.classList.add('is-visible');
    slideOver.classList.add('is-open');
    slideOver.removeAttribute('aria-hidden');
    document.body.style.overflow = 'hidden';
}

function closeDocSlideOver() {
    const overlay   = document.getElementById('docSlideOverOverlay');
    const slideOver = document.getElementById('docSlideOver');
    overlay.classList.remove('is-visible');
    slideOver.classList.remove('is-open');
    slideOver.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

function initDocSlideOver() {
    document.getElementById('docSlideOverClose')?.addEventListener('click', closeDocSlideOver);
    document.getElementById('docSlideOverOverlay')?.addEventListener('click', closeDocSlideOver);

    document.getElementById('facturacionTable')?.addEventListener('click', e => {
        const row = e.target.closest('[data-doc-id]');
        if (row) openDocSlideOver(Number(row.dataset.docId));
    });

    document.getElementById('facturacionTable')?.addEventListener('keydown', e => {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.dataset.docId) {
            e.preventDefault();
            openDocSlideOver(Number(e.target.dataset.docId));
        }
    });

    document.getElementById('docAnularBtn')?.addEventListener('click', () => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Anular documento?',
                text: 'Esta acción no se puede deshacer. El documento quedará marcado como anulado ante la DIAN.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#B3473C',
            }).then(result => {
                if (result.isConfirmed) {
                    Swal.fire('Anulado', 'El documento ha sido anulado (demo).',  'success');
                    closeDocSlideOver();
                }
            });
        } else {
            if (confirm('¿Anular este documento?')) closeDocSlideOver();
        }
    });
}

/* --------------------------------------------------------------------------
   Modal: nueva factura
   -------------------------------------------------------------------------- */
function openNuevaFacturaModal(tipoInicial) {
    const overlay = document.getElementById('nuevaFacturaOverlay');
    const modal   = document.getElementById('nuevaFacturaModal');
    if (!overlay || !modal) return;

    // Seleccionar el tipo inicial
    selectTipo(tipoInicial || 'factura_individual');

    overlay.classList.add('is-visible');
    modal.classList.add('is-open');
    modal.removeAttribute('aria-hidden');
    document.body.style.overflow = 'hidden';
}

function closeNuevaFacturaModal() {
    document.getElementById('nuevaFacturaOverlay')?.classList.remove('is-visible');
    document.getElementById('nuevaFacturaModal')?.classList.remove('is-open');
    document.getElementById('nuevaFacturaModal')?.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

function selectTipo(tipo) {
    document.querySelectorAll('.factura-tipo-card').forEach(card => {
        const isThis = card.dataset.tipo === tipo;
        card.classList.toggle('is-selected', isThis);
        const radio = card.querySelector('input[type="radio"]');
        if (radio) radio.checked = isThis;
    });

    // Mostrar/ocultar sección comprador para DEE/POS
    const compradorSection = document.getElementById('compradorSection');
    if (compradorSection) compradorSection.style.display = tipo === 'dee_pos' ? 'none' : '';

    // Para individual: solo una venta seleccionable
    if (tipo === 'factura_individual') {
        document.querySelectorAll('.venta-check').forEach(chk => {
            chk.type = 'radio';
            chk.name = 'ventaIndividual';
        });
    } else {
        document.querySelectorAll('.venta-check').forEach(chk => {
            chk.type = 'checkbox';
        });
    }

    recalcularTotal();
}

const VENTA_MONTOS = { 128: 85000, 127: 124000, 126: 45000, 124: 68000, 123: 156000, 122: 32000, 120: 178000, 118: 23000, 117: 116000 };

function recalcularTotal() {
    let total = 0;
    document.querySelectorAll('.venta-check:checked').forEach(chk => {
        total += VENTA_MONTOS[parseInt(chk.value)] || 0;
    });
    const totalEl = document.getElementById('facturaTotalSeleccionado');
    if (totalEl) totalEl.textContent = formatMoney(total);
}

function initNuevaFacturaModal() {
    document.getElementById('nuevaIndividualBtn')?.addEventListener('click',   () => openNuevaFacturaModal('factura_individual'));
    document.getElementById('nuevaConsolidadaBtn')?.addEventListener('click',  () => openNuevaFacturaModal('factura_consolidada'));
    document.getElementById('nuevaDeePosBtn')?.addEventListener('click',       () => openNuevaFacturaModal('dee_pos'));

    document.getElementById('nuevaFacturaClose')?.addEventListener('click',    closeNuevaFacturaModal);
    document.getElementById('nuevaFacturaCancelar')?.addEventListener('click', closeNuevaFacturaModal);
    document.getElementById('nuevaFacturaOverlay')?.addEventListener('click',  closeNuevaFacturaModal);

    // Selección de tipo via cards
    document.querySelectorAll('.factura-tipo-card').forEach(card => {
        card.addEventListener('click', () => selectTipo(card.dataset.tipo));
    });

    // Recalcular total al marcar/desmarcar ventas
    document.getElementById('ventasPendientesList')?.addEventListener('change', recalcularTotal);

    // Emitir (demo)
    document.getElementById('nuevaFacturaEmitir')?.addEventListener('click', () => {
        const checks = document.querySelectorAll('.venta-check:checked');
        if (checks.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Selecciona al menos una venta', timer: 2000, showConfirmButton: false });
            } else {
                alert('Selecciona al menos una venta.');
            }
            return;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Documento emitido',
                text: 'La factura fue enviada a la DIAN (demo).',
                timer: 2200,
                showConfirmButton: false,
            }).then(() => closeNuevaFacturaModal());
        } else {
            alert('Factura emitida (demo).');
            closeNuevaFacturaModal();
        }
    });

    // Cerrar con Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            if (document.getElementById('nuevaFacturaModal')?.classList.contains('is-open')) closeNuevaFacturaModal();
            if (document.getElementById('docSlideOver')?.classList.contains('is-open'))      closeDocSlideOver();
        }
    });
}

/* --------------------------------------------------------------------------
   Boot
   -------------------------------------------------------------------------- */
document.addEventListener('DOMContentLoaded', () => {
    cargarFacturacionData();
    initCountUp();
    initDocumentosTable();
    initDocSlideOver();
    initNuevaFacturaModal();
});
