/**
 * Stockly — Panel de Super Admin: vista Pagos y suscripciones (vanilla JS)
 * Depende de admin/layout.js (formatNumber, normalizarTexto).
 *
 * Mismo patrón que el panel de Empresas. Diferencia principal: acá hay un
 * tercer estado ("rechazado") con un motivo de texto, y el flujo de
 * rechazo pide ese motivo antes de confirmar. Todo es demo visual -no hay
 * backend conectado, nada persiste al recargar.
 */

document.addEventListener('DOMContentLoaded', function () {
    initPagosPanel();
});

function initPagosPanel() {
    var table = document.getElementById('pagosTable');
    var dataScript = document.getElementById('pagosData');

    if (!table || !dataScript) {
        return;
    }

    var pagos = JSON.parse(dataScript.textContent);
    var pagosById = {};
    pagos.forEach(function (pago) {
        pagosById[pago.id] = pago;
    });

    var estadoLabels = {
        pendiente: 'Pendiente',
        activado: 'Activado',
        rechazado: 'Rechazado'
    };

    var estadoPillClass = {
        pendiente: 'por-vencer',
        activado: 'activo',
        rechazado: 'vencido'
    };

    var overlay = document.getElementById('pagoSlideOverOverlay');
    var slideOver = document.getElementById('pagoSlideOver');
    var closeBtn = document.getElementById('pagoSlideOverClose');
    var activarBtn = document.getElementById('pagoActivarBtn');
    var rechazarBtn = document.getElementById('pagoRechazarBtn');
    var reasonBox = document.getElementById('pagoRechazarReasonBox');
    var reasonInput = document.getElementById('pagoRechazarMotivo');
    var reasonConfirm = document.getElementById('pagoRechazarConfirmar');
    var reasonCancel = document.getElementById('pagoRechazarCancelar');

    var seccionAcciones = document.getElementById('pagoSeccionAcciones');
    var seccionActivado = document.getElementById('pagoSeccionActivado');
    var seccionRechazado = document.getElementById('pagoSeccionRechazado');

    var currentId = null;

    function pillMarkup(estado) {
        return {
            className: 'status-pill status-pill--' + estadoPillClass[estado],
            label: estadoLabels[estado] || estado
        };
    }

    function syncEstadoPill(pago) {
        var row = table.querySelector('tr[data-pago-id="' + pago.id + '"]');
        var pill = pillMarkup(pago.estado);

        if (row) {
            var rowPill = row.querySelector('.status-pill');
            if (rowPill) {
                rowPill.className = pill.className;
                rowPill.textContent = pill.label;
            }
        }

        var slideOverPill = document.getElementById('pagoSlideOverEstado');
        slideOverPill.className = pill.className;
        slideOverPill.textContent = pill.label;

        updateSections(pago);
    }

    function updateSections(pago) {
        seccionAcciones.hidden = pago.estado !== 'pendiente';
        seccionActivado.hidden = pago.estado !== 'activado';
        seccionRechazado.hidden = pago.estado !== 'rechazado';

        if (pago.estado === 'activado') {
            document.getElementById('pagoSlideOverActivadoPor').textContent = pago.activadoPor || '—';
            document.getElementById('pagoSlideOverFechaActivacion').textContent = pago.fechaActivacion || '—';
        }

        if (pago.estado === 'rechazado') {
            document.getElementById('pagoSlideOverMotivo').textContent = pago.motivoRechazo || '—';
        }

        if (pago.estado === 'pendiente') {
            reasonBox.hidden = true;
            reasonInput.value = '';
        }
    }

    function openPago(id) {
        var pago = pagosById[id];
        if (!pago) {
            return;
        }
        currentId = id;

        document.getElementById('pagoSlideOverEmpresa').textContent = pago.empresa;
        document.getElementById('pagoSlideOverModulo').textContent = pago.modulo;
        document.getElementById('pagoSlideOverMonto').textContent = '$' + formatNumber(pago.monto, 0);
        document.getElementById('pagoSlideOverMetodo').textContent = pago.metodo;
        document.getElementById('pagoSlideOverReferencia').textContent = pago.referencia;
        document.getElementById('pagoSlideOverFecha').textContent = pago.fechaPago;

        syncEstadoPill(pago);

        slideOver.classList.add('is-open');
        slideOver.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-visible');
    }

    function closeSlideOver() {
        slideOver.classList.remove('is-open');
        slideOver.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-visible');
        currentId = null;
    }

    table.querySelectorAll('.data-table__row').forEach(function (row) {
        var id = parseInt(row.getAttribute('data-pago-id'), 10);

        row.addEventListener('click', function () {
            openPago(id);
        });

        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openPago(id);
            }
        });
    });

    closeBtn.addEventListener('click', closeSlideOver);
    overlay.addEventListener('click', closeSlideOver);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && slideOver.classList.contains('is-open')) {
            closeSlideOver();
        }
    });

    /* ---------- Activar ---------- */
    activarBtn.addEventListener('click', function () {
        var pago = pagosById[currentId];
        if (!pago || activarBtn.disabled) {
            return;
        }

        var originalText = activarBtn.textContent;
        activarBtn.disabled = true;
        rechazarBtn.disabled = true;
        activarBtn.textContent = 'Activando...';

        window.setTimeout(function () {
            pago.estado = 'activado';
            pago.activadoPor = 'Alejandro Hernández';
            pago.fechaActivacion = 'Ahora mismo';
            activarBtn.textContent = originalText;
            activarBtn.disabled = false;
            rechazarBtn.disabled = false;
            syncEstadoPill(pago);
        }, 600);
    });

    /* ---------- Rechazar (pide motivo antes de confirmar) ---------- */
    rechazarBtn.addEventListener('click', function () {
        reasonBox.hidden = false;
        reasonInput.focus();
    });

    reasonCancel.addEventListener('click', function () {
        reasonBox.hidden = true;
        reasonInput.value = '';
        setFieldError(reasonInput, false);
    });

    reasonConfirm.addEventListener('click', function () {
        var pago = pagosById[currentId];
        if (!pago) {
            return;
        }

        var motivo = reasonInput.value.trim();
        if (!motivo) {
            setFieldError(reasonInput, true);
            reasonInput.focus();
            return;
        }
        setFieldError(reasonInput, false);

        var originalText = reasonConfirm.textContent;
        reasonConfirm.disabled = true;
        reasonConfirm.textContent = 'Rechazando...';

        window.setTimeout(function () {
            pago.estado = 'rechazado';
            pago.motivoRechazo = motivo;
            reasonConfirm.textContent = originalText;
            reasonConfirm.disabled = false;
            syncEstadoPill(pago);
        }, 600);
    });

    function setFieldError(el, hasError) {
        el.style.borderColor = hasError ? 'var(--color-error)' : '';
    }

    /* ---------- Búsqueda + filtro por estado ---------- */
    var searchInput = document.getElementById('pagosSearch');
    var estadoFilter = document.getElementById('pagosEstadoFilter');
    var emptyState = document.getElementById('pagosEmpty');

    function applyFilters() {
        var term = normalizarTexto(searchInput.value.trim());
        var estado = estadoFilter.value;
        var visibleCount = 0;

        table.querySelectorAll('.data-table__row').forEach(function (row) {
            var id = parseInt(row.getAttribute('data-pago-id'), 10);
            var pago = pagosById[id];
            var matchesTerm = !term
                || normalizarTexto(pago.empresa).indexOf(term) !== -1
                || normalizarTexto(pago.referencia).indexOf(term) !== -1;
            var matchesEstado = !estado || pago.estado === estado;
            var visible = matchesTerm && matchesEstado;

            row.hidden = !visible;
            if (visible) {
                visibleCount++;
            }
        });

        emptyState.hidden = visibleCount !== 0;
    }

    searchInput.addEventListener('input', applyFilters);
    estadoFilter.addEventListener('change', applyFilters);
}
