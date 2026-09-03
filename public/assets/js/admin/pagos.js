/**
 * Stockly — Panel de Super Admin: vista Pagos y suscripciones (vanilla JS)
 * Depende de admin/layout.js (formatNumber, normalizarTexto).
 *
 * Mismo patrón que el panel de Empresas (empresasApiRequest/Confirmar).
 * Un pago 'pago_recibido' se puede aprobar (activa la empresa de una) o
 * rechazar (pide motivo antes de confirmar, igual que rechazar en el
 * flujo viejo de Empresas).
 */

document.addEventListener('DOMContentLoaded', function () {
    initPagosPanel();
});

function pagosApiRequest(method, url, data) {
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

function pagosMostrarError(mensaje) {
    if (typeof Swal === 'undefined') {
        window.alert(mensaje);
        return;
    }
    Swal.fire({
        icon: 'error',
        title: 'No se pudo completar',
        text: mensaje,
        confirmButtonText: 'Entendido',
        customClass: { popup: 'stockly-swal', container: 'stockly-swal-backdrop' }
    });
}

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

    var estadoPillClass = {
        pago_recibido: 'por-vencer',
        activado: 'activo',
        rechazado: 'vencido'
    };

    var overlay = document.getElementById('pagoSlideOverOverlay');
    var slideOver = document.getElementById('pagoSlideOver');
    var closeBtn = document.getElementById('pagoSlideOverClose');
    var aprobarBtn = document.getElementById('pagoAprobarBtn');
    var rechazarBtn = document.getElementById('pagoRechazarBtn');
    var reasonBox = document.getElementById('pagoRechazarReasonBox');
    var reasonInput = document.getElementById('pagoRechazarMotivo');
    var reasonConfirm = document.getElementById('pagoRechazarConfirmar');
    var reasonCancel = document.getElementById('pagoRechazarCancelar');

    var seccionAcciones = document.getElementById('pagoSeccionAcciones');
    var seccionComprobante = document.getElementById('pagoSlideOverComprobanteSection');
    var seccionRechazo = document.getElementById('pagoSlideOverRechazoSection');
    var seccionVencimiento = document.getElementById('pagoSlideOverVencimientoSection');
    var fechaActivacionRow = document.getElementById('pagoSlideOverFechaActivacionRow');
    var activadoPorRow = document.getElementById('pagoSlideOverActivadoPorRow');
    var comprobanteLink = document.getElementById('pagoSlideOverComprobanteLink');

    var currentId = null;

    function pillMarkup(pago) {
        return {
            className: 'status-pill status-pill--' + estadoPillClass[pago.estado],
            label: pago.estadoLabel
        };
    }

    function aplicarFilasYSecciones(pago) {
        var row = table.querySelector('tr[data-pago-id="' + pago.id + '"]');
        var pill = pillMarkup(pago);

        if (row) {
            var rowPill = row.querySelector('.status-pill');
            if (rowPill) {
                rowPill.className = pill.className;
                rowPill.textContent = pill.label;
            }
        }

        if (currentId === pago.id) {
            document.getElementById('pagoSlideOverEmpresa').textContent = pago.empresa;
            document.getElementById('pagoSlideOverPlan').textContent = pago.plan;
            document.getElementById('pagoSlideOverMonto').textContent = pago.monto !== null ? '$' + formatNumber(pago.monto, 0) : '—';
            document.getElementById('pagoSlideOverMetodo').textContent = pago.metodo || '—';
            document.getElementById('pagoSlideOverFechaPago').textContent = pago.fechaPago || '—';
            document.getElementById('pagoSlideOverFechaActivacion').textContent = pago.fechaActivacion || '—';
            document.getElementById('pagoSlideOverActivadoPor').textContent = pago.activadoPor;
            document.getElementById('pagoSlideOverVencimientoAnterior').textContent = pago.vencimientoAnterior;
            document.getElementById('pagoSlideOverVencimientoNuevo').textContent = pago.vencimientoNuevo || '—';

            var slideOverPill = document.getElementById('pagoSlideOverEstado');
            slideOverPill.className = pill.className;
            slideOverPill.textContent = pill.label;

            seccionAcciones.hidden = pago.estado !== 'pago_recibido';
            seccionComprobante.hidden = !pago.comprobanteUrl;
            seccionRechazo.hidden = pago.estado !== 'rechazado';
            seccionVencimiento.hidden = pago.estado === 'pago_recibido';
            fechaActivacionRow.hidden = pago.estado === 'pago_recibido';
            activadoPorRow.hidden = pago.estado === 'pago_recibido';

            if (pago.comprobanteUrl) {
                comprobanteLink.href = pago.comprobanteUrl;
            }
            if (pago.estado === 'rechazado') {
                document.getElementById('pagoSlideOverMotivoRechazo').textContent = pago.motivoRechazo || '—';
            }
            if (pago.estado === 'pago_recibido') {
                reasonBox.hidden = true;
                reasonInput.value = '';
            }
        }
    }

    function openPago(id) {
        var pago = pagosById[id];
        if (!pago) {
            return;
        }
        currentId = id;

        aplicarFilasYSecciones(pago);

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

    /* ---------- Aprobar ---------- */
    aprobarBtn.addEventListener('click', function () {
        if (!currentId) return;

        var originalText = aprobarBtn.textContent;
        aprobarBtn.disabled = true;
        rechazarBtn.disabled = true;
        aprobarBtn.textContent = 'Aprobando...';

        pagosApiRequest('POST', '/admin/pagos/' + currentId + '/aprobar')
            .then(function (json) {
                pagosById[json.pago.id] = json.pago;
                aplicarFilasYSecciones(json.pago);
            })
            .catch(function (error) {
                pagosMostrarError(error.message);
            })
            .finally(function () {
                aprobarBtn.disabled = false;
                rechazarBtn.disabled = false;
                aprobarBtn.textContent = originalText;
            });
    });

    /* ---------- Rechazar (pide motivo antes de confirmar) ---------- */
    rechazarBtn.addEventListener('click', function () {
        reasonBox.hidden = false;
        reasonInput.focus();
    });

    reasonCancel.addEventListener('click', function () {
        reasonBox.hidden = true;
        reasonInput.value = '';
        reasonInput.style.borderColor = '';
    });

    reasonConfirm.addEventListener('click', function () {
        if (!currentId) return;

        var motivo = reasonInput.value.trim();
        if (!motivo) {
            reasonInput.style.borderColor = 'var(--color-error)';
            reasonInput.focus();
            return;
        }
        reasonInput.style.borderColor = '';

        var originalText = reasonConfirm.textContent;
        reasonConfirm.disabled = true;
        reasonConfirm.textContent = 'Rechazando...';

        pagosApiRequest('POST', '/admin/pagos/' + currentId + '/rechazar', { motivo: motivo })
            .then(function (json) {
                pagosById[json.pago.id] = json.pago;
                aplicarFilasYSecciones(json.pago);
            })
            .catch(function (error) {
                pagosMostrarError(error.message);
            })
            .finally(function () {
                reasonConfirm.disabled = false;
                reasonConfirm.textContent = originalText;
            });
    });

    /* ---------- Búsqueda + filtros ---------- */
    var searchInput = document.getElementById('pagosSearch');
    var estadoFilter = document.getElementById('pagosEstadoFilter');
    var planFilter = document.getElementById('pagosPlanFilter');
    var emptyState = document.getElementById('pagosEmpty');

    function applyFilters() {
        var term = normalizarTexto(searchInput.value.trim());
        var estado = estadoFilter.value;
        var plan = planFilter.value;
        var visibleCount = 0;

        table.querySelectorAll('.data-table__row').forEach(function (row) {
            var id = parseInt(row.getAttribute('data-pago-id'), 10);
            var pago = pagosById[id];
            var matchesTerm = !term || normalizarTexto(pago.empresa).indexOf(term) !== -1;
            var matchesEstado = !estado || pago.estado === estado;
            var matchesPlan = !plan || pago.plan === plan;
            var visible = matchesTerm && matchesEstado && matchesPlan;

            row.hidden = !visible;
            if (visible) {
                visibleCount++;
            }
        });

        emptyState.hidden = visibleCount !== 0;
    }

    searchInput.addEventListener('input', applyFilters);
    estadoFilter.addEventListener('change', applyFilters);
    planFilter.addEventListener('change', applyFilters);
}
