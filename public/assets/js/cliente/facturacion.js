/* ==========================================================================
   Stockly — Facturación electrónica
   Módulos: countUp, tabla con filtros y paginación, slide-over de detalle,
   modal de nueva factura (tipo + comprador + ventas seleccionadas).
   Emitir y Anular: POST al backend real; recarga la página al confirmar.
   ========================================================================== */

'use strict';

const ROWS_PER_PAGE = 10;

let facturacionDocs = [];
let facturacionById = {};
let filteredDocs    = [];
let currentPage     = 1;
let docAbiertoId    = null; // id del documento abierto en el slide-over

let gastosDocs      = [];
let gastosDocsById  = {};
let docGastoAbiertoId = null; // id del documento de gasto abierto en el slide-over

/* --------------------------------------------------------------------------
   Helpers
   -------------------------------------------------------------------------- */
function formatMoney(n) {
    return '$' + Number(n).toLocaleString('es-CO');
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function storeUrl() {
    return document.querySelector('meta[name="facturacion-store-url"]')?.content || '/cliente/facturacion';
}

function anularUrl(id) {
    return `/cliente/facturacion/${id}/anular`;
}

function gastosStoreUrl() {
    return '/cliente/facturacion/gastos';
}

function anularGastoUrl(id) {
    return `/cliente/facturacion/gastos/${id}/anular`;
}

async function postJSON(url, body) {
    const res = await fetch(url, {
        method:  'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'Accept':       'application/json',
        },
        body: JSON.stringify(body),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Error inesperado');
    return data;
}

/* --------------------------------------------------------------------------
   Carga de datos desde las islas JSON
   -------------------------------------------------------------------------- */
function cargarFacturacionData() {
    const el = document.getElementById('facturacionData');
    if (!el) return;
    facturacionDocs = JSON.parse(el.textContent);
    facturacionDocs.forEach(d => { facturacionById[d.id] = d; });
    filteredDocs = [...facturacionDocs];
}

function cargarGastosData() {
    const el = document.getElementById('facturacionGastosData');
    if (!el) return;
    gastosDocs = JSON.parse(el.textContent);
    gastosDocs.forEach(d => { gastosDocsById[d.id] = d; });
}

/* --------------------------------------------------------------------------
   Pestañas principales: Ventas / Gastos
   -------------------------------------------------------------------------- */
function initMainTabs() {
    const btnVentas = document.getElementById('tabBtnVentas');
    const btnGastos = document.getElementById('tabBtnGastos');
    const panelVentas = document.getElementById('tabPanelVentas');
    const panelGastos = document.getElementById('tabPanelGastos');
    if (!btnVentas || !btnGastos || !panelVentas || !panelGastos) return;

    function activar(destino) {
        const esVentas = destino === 'ventas';
        btnVentas.classList.toggle('is-active', esVentas);
        btnGastos.classList.toggle('is-active', !esVentas);
        btnVentas.setAttribute('aria-selected', esVentas ? 'true' : 'false');
        btnGastos.setAttribute('aria-selected', esVentas ? 'false' : 'true');
        panelVentas.hidden = !esVentas;
        panelGastos.hidden = esVentas;
    }

    btnVentas.addEventListener('click', () => activar('ventas'));
    btnGastos.addEventListener('click', () => activar('gastos'));
}

/* --------------------------------------------------------------------------
   CountUp animado para las stat cards
   -------------------------------------------------------------------------- */
function initCountUp() {
    document.querySelectorAll('[data-count]').forEach(el => {
        const target   = parseFloat(el.dataset.count) || 0;
        const isMoney  = el.dataset.format === 'money';
        const duration = 1100;
        const start    = performance.now();

        function tick(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased    = 1 - Math.pow(1 - progress, 5);
            const value    = Math.round(target * eased);
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
    const tbody    = document.querySelector('#facturacionTable tbody');
    const emptyEl  = document.getElementById('facturacionEmpty');
    const pageInfo = document.getElementById('facturacionPageInfo');
    const prevBtn  = document.getElementById('facturacionPrevPage');
    const nextBtn  = document.getElementById('facturacionNextPage');
    if (!tbody) return;

    const totalPages = Math.max(1, Math.ceil(filteredDocs.length / ROWS_PER_PAGE));
    currentPage = Math.min(currentPage, totalPages);

    const slice  = filteredDocs.slice((currentPage - 1) * ROWS_PER_PAGE, currentPage * ROWS_PER_PAGE);
    const docIds = new Set(slice.map(d => String(d.id)));

    tbody.querySelectorAll('tr.data-table__row').forEach(row => {
        row.style.display = docIds.has(row.dataset.docId) ? '' : 'none';
    });

    if (emptyEl) emptyEl.hidden = filteredDocs.length > 0;

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
        const haystack    = [doc.numero, doc.comprador?.nombre || '', doc.comprador?.numDoc || ''].join(' ').toLowerCase();
        const matchQuery  = !query  || haystack.includes(query);
        return matchTipo && matchEstado && matchQuery;
    });

    currentPage = 1;
    renderTabla();
}

function initDocumentosTable() {
    document.getElementById('facturacionSearch')?.addEventListener('input', applyFilters);
    document.getElementById('facturacionTipoFilter')?.addEventListener('change', applyFilters);
    document.getElementById('facturacionEstadoFilter')?.addEventListener('change', applyFilters);
    document.getElementById('facturacionPrevPage')?.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderTabla(); } });
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
    docAbiertoId = docId;

    const tipoMap = { factura_individual: 'Factura individual', factura_consolidada: 'Factura consolidada', dee_pos: 'DEE / POS' };
    document.getElementById('docSlideOverNumero').textContent = doc.numero;

    const estadoEl = document.getElementById('docSlideOverEstado');
    estadoEl.textContent = doc.estado === 'emitida' ? 'Emitida' : 'Anulada';
    estadoEl.className   = 'status-pill ' + (doc.estado === 'emitida' ? 'status-pill--pagada' : 'status-pill--error');

    document.getElementById('docSlideOverTipo').textContent  = tipoMap[doc.tipo] || doc.tipo;
    document.getElementById('docSlideOverFecha').textContent = doc.fecha;

    const compradorSection = document.getElementById('docSlideOverCompradorSection');
    const compradorEl      = document.getElementById('docSlideOverComprador');
    if (doc.comprador) {
        compradorSection.hidden = false;
        compradorEl.innerHTML   = `
            <div class="slide-over__field"><span>Nombre</span><strong>${doc.comprador.nombre}</strong></div>
            <div class="slide-over__field"><span>${doc.comprador.tipoDoc}</span><strong>${doc.comprador.numDoc}</strong></div>`;
    } else {
        compradorSection.hidden = true;
    }

    document.getElementById('docSlideOverVentas').innerHTML = doc.ventasIds.map(vid =>
        `<div class="doc-venta-row"><span class="doc-venta-row__num">Venta #${vid}</span></div>`
    ).join('');

    document.getElementById('docSlideOverTotal').textContent = formatMoney(doc.valorTotal);
    document.getElementById('docSlideOverCufe').textContent  = doc.cufe || '—';

    const descargarBtn = document.getElementById('docDescargarBtn');
    if (descargarBtn) descargarBtn.href = '/cliente/facturacion/' + doc.id + '/pdf';

    const anularSection = document.getElementById('docAnularSection');
    if (anularSection) anularSection.hidden = doc.estado === 'anulada';

    document.getElementById('docSlideOverOverlay').classList.add('is-visible');
    document.getElementById('docSlideOver').classList.add('is-open');
    document.getElementById('docSlideOver').removeAttribute('aria-hidden');
    document.body.style.overflow = 'hidden';
}

function closeDocSlideOver() {
    document.getElementById('docSlideOverOverlay').classList.remove('is-visible');
    document.getElementById('docSlideOver').classList.remove('is-open');
    document.getElementById('docSlideOver').setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    docAbiertoId = null;
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

    document.getElementById('docAnularBtn')?.addEventListener('click', async () => {
        if (!docAbiertoId) return;

        const confirmar = typeof Swal !== 'undefined'
            ? (await Swal.fire({
                title: '¿Anular documento?',
                text: 'El documento quedará marcado como anulado y las ventas incluidas volverán al estado sin facturar.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#B3473C',
            })).isConfirmed
            : confirm('¿Anular este documento?');

        if (!confirmar) return;

        try {
            await postJSON(anularUrl(docAbiertoId), {});
            closeDocSlideOver();
            if (typeof Swal !== 'undefined') {
                await Swal.fire({ icon: 'success', title: 'Documento anulado', timer: 1800, showConfirmButton: false });
            }
            window.location.reload();
        } catch (err) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Error al anular', text: err.message });
            } else {
                alert('Error: ' + err.message);
            }
        }
    });
}

/* --------------------------------------------------------------------------
   Slide-over: documento de gasto (soporte / nómina)
   -------------------------------------------------------------------------- */
function openDocGastoSlideOver(docId) {
    const doc = gastosDocsById[docId];
    if (!doc) return;
    docGastoAbiertoId = docId;

    const tipoMap = { documento_soporte: 'Documento soporte', nomina_electronica: 'Nómina electrónica' };
    document.getElementById('docGastoSlideOverNumero').textContent = doc.numero;

    const estadoEl = document.getElementById('docGastoSlideOverEstado');
    estadoEl.textContent = doc.estado === 'emitida' ? 'Emitida' : 'Anulada';
    estadoEl.className   = 'status-pill ' + (doc.estado === 'emitida' ? 'status-pill--pagada' : 'status-pill--error');

    document.getElementById('docGastoSlideOverTipo').textContent  = tipoMap[doc.tipo] || doc.tipo;
    document.getElementById('docGastoSlideOverFecha').textContent = doc.fecha;

    document.getElementById('docGastoSlideOverBeneficiario').innerHTML = `
        <div class="slide-over__field"><span>Nombre</span><strong>${doc.beneficiario.nombre}</strong></div>
        ${doc.beneficiario.numDoc ? `<div class="slide-over__field"><span>${doc.beneficiario.tipoDoc}</span><strong>${doc.beneficiario.numDoc}</strong></div>` : ''}`;

    document.getElementById('docGastoSlideOverGastos').innerHTML = doc.gastosIds.map(gid =>
        `<div class="doc-venta-row"><span class="doc-venta-row__num">Gasto #${gid}</span></div>`
    ).join('');

    document.getElementById('docGastoSlideOverTotal').textContent = formatMoney(doc.valorTotal);
    document.getElementById('docGastoSlideOverCufe').textContent  = doc.cufe || '—';

    const descargarBtn = document.getElementById('docGastoDescargarBtn');
    if (descargarBtn) descargarBtn.href = '/cliente/facturacion/gastos/' + doc.id + '/pdf';

    const anularSection = document.getElementById('docGastoAnularSection');
    if (anularSection) anularSection.hidden = doc.estado === 'anulada';

    document.getElementById('docGastoSlideOverOverlay').classList.add('is-visible');
    document.getElementById('docGastoSlideOver').classList.add('is-open');
    document.getElementById('docGastoSlideOver').removeAttribute('aria-hidden');
    document.body.style.overflow = 'hidden';
}

function closeDocGastoSlideOver() {
    document.getElementById('docGastoSlideOverOverlay').classList.remove('is-visible');
    document.getElementById('docGastoSlideOver').classList.remove('is-open');
    document.getElementById('docGastoSlideOver').setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    docGastoAbiertoId = null;
}

function initDocGastoSlideOver() {
    document.getElementById('docGastoSlideOverClose')?.addEventListener('click', closeDocGastoSlideOver);
    document.getElementById('docGastoSlideOverOverlay')?.addEventListener('click', closeDocGastoSlideOver);

    document.getElementById('facturacionGastosTable')?.addEventListener('click', e => {
        const row = e.target.closest('[data-doc-gasto-id]');
        if (row) openDocGastoSlideOver(Number(row.dataset.docGastoId));
    });
    document.getElementById('facturacionGastosTable')?.addEventListener('keydown', e => {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.dataset.docGastoId) {
            e.preventDefault();
            openDocGastoSlideOver(Number(e.target.dataset.docGastoId));
        }
    });

    document.getElementById('docGastoAnularBtn')?.addEventListener('click', async () => {
        if (!docGastoAbiertoId) return;

        const confirmar = typeof Swal !== 'undefined'
            ? (await Swal.fire({
                title: '¿Anular documento?',
                text: 'El documento quedará marcado como anulado y los gastos incluidos volverán al estado sin reportar.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#B3473C',
            })).isConfirmed
            : confirm('¿Anular este documento?');

        if (!confirmar) return;

        try {
            await postJSON(anularGastoUrl(docGastoAbiertoId), {});
            closeDocGastoSlideOver();
            if (typeof Swal !== 'undefined') {
                await Swal.fire({ icon: 'success', title: 'Documento anulado', timer: 1800, showConfirmButton: false });
            }
            window.location.reload();
        } catch (err) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Error al anular', text: err.message });
            } else {
                alert('Error: ' + err.message);
            }
        }
    });
}

/* --------------------------------------------------------------------------
   Modal: nuevo documento de gasto
   -------------------------------------------------------------------------- */
function recalcularTotalGasto() {
    let total = 0;
    const seleccionados = document.querySelectorAll('.gasto-check:checked');
    seleccionados.forEach(chk => {
        total += parseFloat(chk.dataset.total) || 0;
    });
    const totalEl = document.getElementById('docGastoTotalSeleccionado');
    if (totalEl) totalEl.textContent = formatMoney(total);

    const emitirBtn = document.getElementById('nuevoDocGastoEmitir');
    if (emitirBtn) emitirBtn.disabled = !seleccionados.length;
}

function openNuevoDocGastoModal() {
    ['beneficiarioNumDoc', 'beneficiarioNombre'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    document.querySelectorAll('.gasto-check').forEach(chk => { chk.checked = false; });
    recalcularTotalGasto();

    document.getElementById('nuevoDocGastoOverlay').classList.add('is-visible');
    document.getElementById('nuevoDocGastoModal').classList.add('is-open');
    document.getElementById('nuevoDocGastoModal').removeAttribute('aria-hidden');
    document.body.style.overflow = 'hidden';
}

function closeNuevoDocGastoModal() {
    document.getElementById('nuevoDocGastoOverlay')?.classList.remove('is-visible');
    document.getElementById('nuevoDocGastoModal')?.classList.remove('is-open');
    document.getElementById('nuevoDocGastoModal')?.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

function initNuevoDocGastoModal() {
    document.getElementById('nuevoDocGastoBtn')?.addEventListener('click', openNuevoDocGastoModal);
    document.getElementById('nuevoDocGastoClose')?.addEventListener('click', closeNuevoDocGastoModal);
    document.getElementById('nuevoDocGastoCancelar')?.addEventListener('click', closeNuevoDocGastoModal);
    document.getElementById('nuevoDocGastoOverlay')?.addEventListener('click', closeNuevoDocGastoModal);

    document.getElementById('gastosPendientesList')?.addEventListener('change', recalcularTotalGasto);

    document.getElementById('nuevoDocGastoEmitir')?.addEventListener('click', async () => {
        const tipo = document.getElementById('docGastoTipoInput')?.value || 'documento_soporte';
        const gastosIds = [...document.querySelectorAll('.gasto-check:checked')].map(c => parseInt(c.value));
        const beneficiarioNombre = document.getElementById('beneficiarioNombre')?.value?.trim();

        if (gastosIds.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Selecciona al menos un gasto', timer: 2000, showConfirmButton: false });
            } else {
                alert('Selecciona al menos un gasto.');
            }
            return;
        }

        if (!beneficiarioNombre) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Escribe el nombre del beneficiario', timer: 2000, showConfirmButton: false });
            } else {
                alert('Escribe el nombre del beneficiario.');
            }
            return;
        }

        const body = {
            tipo,
            gastos_ids: gastosIds,
            beneficiario_nombre: beneficiarioNombre,
            beneficiario_tipo_documento: document.getElementById('beneficiarioTipoDoc')?.value,
            beneficiario_numero_documento: document.getElementById('beneficiarioNumDoc')?.value?.trim() || null,
        };

        const emitirBtn = document.getElementById('nuevoDocGastoEmitir');
        emitirBtn.disabled = true;
        emitirBtn.textContent = 'Emitiendo…';

        try {
            await postJSON(gastosStoreUrl(), body);
            closeNuevoDocGastoModal();
            if (typeof Swal !== 'undefined') {
                await Swal.fire({ icon: 'success', title: 'Documento emitido', text: 'El documento fue registrado correctamente.', timer: 2000, showConfirmButton: false });
            }
            window.location.reload();
        } catch (err) {
            emitirBtn.disabled = false;
            emitirBtn.textContent = 'Emitir a la DIAN';
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'No se pudo emitir', text: err.message });
            } else {
                alert('Error: ' + err.message);
            }
        }
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            if (document.getElementById('nuevoDocGastoModal')?.classList.contains('is-open')) closeNuevoDocGastoModal();
            if (document.getElementById('docGastoSlideOver')?.classList.contains('is-open'))  closeDocGastoSlideOver();
        }
    });
}

/* --------------------------------------------------------------------------
   Modal: nueva factura
   -------------------------------------------------------------------------- */
function recalcularTotal() {
    let total = 0;
    const seleccionadas = document.querySelectorAll('.venta-check:checked');
    seleccionadas.forEach(chk => {
        total += parseFloat(chk.dataset.total) || 0;
    });
    const totalEl = document.getElementById('facturaTotalSeleccionado');
    if (totalEl) totalEl.textContent = formatMoney(total);

    // Habilitar el botón solo si hay al menos una venta seleccionada
    const emitirBtn = document.getElementById('nuevaFacturaEmitir');
    if (emitirBtn) emitirBtn.disabled = !seleccionadas.length;

    autorrellenarComprador(seleccionadas);
}

/**
 * Si la venta ya tenía un comprador guardado (el cliente pidió factura al
 * momento de la venta, ver VentasController::store()), se rellenan solos
 * los campos de comprador -así no toca volver a escribir lo que ya se
 * había capturado. Solo cuando hay UNA sola venta marcada: con varias
 * (consolidada) cada una puede traer un comprador distinto o ninguno, así
 * que ahí se deja en manos del usuario.
 */
function autorrellenarComprador(seleccionadas) {
    const tipoDocEl = document.getElementById('compradorTipoDoc');
    const numDocEl = document.getElementById('compradorNumDoc');
    const nombreEl = document.getElementById('compradorNombre');
    if (!tipoDocEl || !numDocEl || !nombreEl) return;

    // Consolidada/DEE-POS no piden comprador -no tiene sentido rellenar
    // campos que ni siquiera se ven ni se van a enviar.
    const tipoActual = document.querySelector('.factura-tipo-card.is-selected')?.dataset.tipo;
    if (tipoActual !== 'factura_individual') return;

    if (seleccionadas.length !== 1) return;

    const chk = seleccionadas[0];
    const numDoc = chk.dataset.compradorNumDoc;
    if (!numDoc) return;

    tipoDocEl.value = chk.dataset.compradorTipoDoc || 'CC';
    numDocEl.value = numDoc;
    nombreEl.value = chk.dataset.compradorNombre || '';
}

function selectTipo(tipo) {
    document.querySelectorAll('.factura-tipo-card').forEach(card => {
        const isThis = card.dataset.tipo === tipo;
        card.classList.toggle('is-selected', isThis);
        const radio = card.querySelector('input[type="radio"]');
        if (radio) radio.checked = isThis;
    });

    // Solo "Individual" pide comprador identificado -"Consolidada" es
    // justo lo contrario: junta ventas de consumidor final (sin nombre)
    // en un solo reporte, igual que DEE/POS.
    const compradorSection = document.getElementById('compradorSection');
    if (compradorSection) compradorSection.style.display = tipo === 'factura_individual' ? '' : 'none';

    // Individual: una sola venta → radio; Consolidada/DEE: múltiples → checkbox
    if (tipo === 'factura_individual') {
        document.querySelectorAll('.venta-check').forEach(chk => { chk.type = 'radio'; chk.name = 'ventaIndividual'; });
    } else {
        document.querySelectorAll('.venta-check').forEach(chk => { chk.type = 'checkbox'; });
    }

    recalcularTotal();
}

function openNuevaFacturaModal(tipoInicial) {
    selectTipo(tipoInicial || 'factura_individual');

    // Limpiar campos
    ['compradorNumDoc', 'compradorNombre'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    document.querySelectorAll('.venta-check').forEach(chk => { chk.checked = false; });
    recalcularTotal();

    document.getElementById('nuevaFacturaOverlay').classList.add('is-visible');
    document.getElementById('nuevaFacturaModal').classList.add('is-open');
    document.getElementById('nuevaFacturaModal').removeAttribute('aria-hidden');
    document.body.style.overflow = 'hidden';
}

function closeNuevaFacturaModal() {
    document.getElementById('nuevaFacturaOverlay')?.classList.remove('is-visible');
    document.getElementById('nuevaFacturaModal')?.classList.remove('is-open');
    document.getElementById('nuevaFacturaModal')?.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

function initNuevaFacturaModal() {
    document.getElementById('nuevaIndividualBtn')?.addEventListener('click',   () => openNuevaFacturaModal('factura_individual'));
    document.getElementById('nuevaConsolidadaBtn')?.addEventListener('click',  () => openNuevaFacturaModal('factura_consolidada'));
    document.getElementById('nuevaDeePosBtn')?.addEventListener('click',       () => openNuevaFacturaModal('dee_pos'));
    document.getElementById('nuevaFacturaClose')?.addEventListener('click',    closeNuevaFacturaModal);
    document.getElementById('nuevaFacturaCancelar')?.addEventListener('click', closeNuevaFacturaModal);
    document.getElementById('nuevaFacturaOverlay')?.addEventListener('click',  closeNuevaFacturaModal);

    document.querySelectorAll('.factura-tipo-card').forEach(card => {
        card.addEventListener('click', () => selectTipo(card.dataset.tipo));
    });

    document.getElementById('ventasPendientesList')?.addEventListener('change', recalcularTotal);

    document.getElementById('nuevaFacturaEmitir')?.addEventListener('click', async () => {
        const tipo = document.querySelector('.factura-tipo-card.is-selected')?.dataset.tipo;
        const ventasIds = [...document.querySelectorAll('.venta-check:checked')].map(c => parseInt(c.value));

        if (ventasIds.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Selecciona al menos una venta', timer: 2000, showConfirmButton: false });
            } else {
                alert('Selecciona al menos una venta.');
            }
            return;
        }

        const body = {
            tipo,
            ventas_ids: ventasIds,
        };

        if (tipo !== 'dee_pos') {
            body.comprador_tipo_documento   = document.getElementById('compradorTipoDoc')?.value;
            body.comprador_numero_documento = document.getElementById('compradorNumDoc')?.value?.trim();
            body.comprador_nombre           = document.getElementById('compradorNombre')?.value?.trim();
        }

        const emitirBtn = document.getElementById('nuevaFacturaEmitir');
        emitirBtn.disabled = true;
        emitirBtn.textContent = 'Emitiendo…';

        try {
            await postJSON(storeUrl(), body);
            closeNuevaFacturaModal();
            if (typeof Swal !== 'undefined') {
                await Swal.fire({ icon: 'success', title: 'Documento emitido', text: 'El documento fue registrado correctamente.', timer: 2000, showConfirmButton: false });
            }
            window.location.reload();
        } catch (err) {
            emitirBtn.disabled = false;
            emitirBtn.textContent = 'Emitir a la DIAN';
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'No se pudo emitir', text: err.message });
            } else {
                alert('Error: ' + err.message);
            }
        }
    });

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
    cargarGastosData();
    initCountUp();
    initMainTabs();
    initDocumentosTable();
    initDocSlideOver();
    initNuevaFacturaModal();
    initDocGastoSlideOver();
    initNuevoDocGastoModal();
});
