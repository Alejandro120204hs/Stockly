/**
 * Stockly — Suscripción (rol Cliente)
 *   1. Nombre del archivo elegido junto al botón de subir comprobante (el
 *      input real va oculto, el label estilizado lo dispara -mismo patrón
 *      que perfil.js con el logo, pero acá no se auto-envía: falta elegir
 *      el plan primero).
 *   2. Copiar el número de Nequi / la llave con un clic -evita que alguien
 *      tenga que transcribirlo a mano desde el celular.
 *   3. "Renovar antes de tiempo" -cuando la suscripción sigue activa, el
 *      formulario de pago existe en el HTML pero arranca oculto (atributo
 *      hidden); este botón solo lo destapa, no hay nada que pedirle al
 *      servidor todavía.
 */
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('suscripcionComprobante');
    var nombre = document.getElementById('suscripcionComprobanteNombre');

    if (input && nombre) {
        input.addEventListener('change', function () {
            nombre.textContent = input.files.length > 0 ? input.files[0].name : 'Ningún archivo elegido';
        });
    }

    var renovarBtn = document.getElementById('renovarAntesBtn');
    var formPanel = document.getElementById('suscripcionFormPanel');

    if (renovarBtn && formPanel) {
        renovarBtn.addEventListener('click', function () {
            formPanel.hidden = false;
            formPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    initHistorialPagos();

    /**
     * document.execCommand primero -es síncrono y funciona en más casos
     * (no depende de permisos del navegador ni de que el documento tenga
     * foco); navigator.clipboard.writeText como respaldo si no existe.
     */
    function copiarTexto(valor) {
        var textarea = document.createElement('textarea');
        textarea.value = valor;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();

        var copiado = false;
        try {
            copiado = document.execCommand('copy');
        } catch (e) {
            copiado = false;
        }
        document.body.removeChild(textarea);

        if (copiado) {
            return Promise.resolve();
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(valor);
        }
        return Promise.reject(new Error('No se pudo copiar'));
    }

    document.querySelectorAll('.pago-metodo-card__copy').forEach(function (btn) {
        var iconoOriginal = btn.innerHTML;

        btn.addEventListener('click', function () {
            copiarTexto(btn.getAttribute('data-copy')).then(function () {
                btn.classList.add('is-copied');
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';

                window.setTimeout(function () {
                    btn.classList.remove('is-copied');
                    btn.innerHTML = iconoOriginal;
                }, 1500);
            }).catch(function () {
                // Sin retroalimentación visual si de plano no se pudo -el
                // valor sigue visible y seleccionable a mano en la tarjeta.
            });
        });
    });
});

/* ------------------------------------------------------------------
 * Historial de pagos: paginación (4 por página) -mismo patrón exacto
 * que el historial de cierres de Caja (ver caja.js
 * initHistorialCierres()/renderHistorialCierres()), simplificado acá
 * porque no hay filtro de mes: todo el historial ya vino en el HTML,
 * el JS solo decide qué filas mostrar.
 * ------------------------------------------------------------------ */
var SUSCRIPCION_HISTORIAL_PAGE_SIZE = 4;
var suscripcionHistorialPagina = 1;

function initHistorialPagos() {
    var table = document.getElementById('suscripcionHistorialTable');
    if (!table) {
        return;
    }

    document.getElementById('suscripcionHistorialPrevPage')?.addEventListener('click', function () {
        if (suscripcionHistorialPagina > 1) {
            suscripcionHistorialPagina--;
            renderHistorialPagos();
        }
    });
    document.getElementById('suscripcionHistorialNextPage')?.addEventListener('click', function () {
        suscripcionHistorialPagina++;
        renderHistorialPagos();
    });

    renderHistorialPagos();
}

function renderHistorialPagos() {
    var table = document.getElementById('suscripcionHistorialTable');
    var pageInfoEl = document.getElementById('suscripcionHistorialPageInfo');
    var prevBtn = document.getElementById('suscripcionHistorialPrevPage');
    var nextBtn = document.getElementById('suscripcionHistorialNextPage');
    if (!table) {
        return;
    }

    var filas = table.querySelectorAll('.data-table__row');
    var totalPaginas = Math.max(1, Math.ceil(filas.length / SUSCRIPCION_HISTORIAL_PAGE_SIZE));
    suscripcionHistorialPagina = Math.min(suscripcionHistorialPagina, totalPaginas);
    var desde = (suscripcionHistorialPagina - 1) * SUSCRIPCION_HISTORIAL_PAGE_SIZE;
    var hasta = desde + SUSCRIPCION_HISTORIAL_PAGE_SIZE;

    filas.forEach(function (row, indice) {
        row.hidden = indice < desde || indice >= hasta;
    });

    if (pageInfoEl) {
        pageInfoEl.textContent = 'Página ' + suscripcionHistorialPagina + ' de ' + totalPaginas;
    }
    if (prevBtn) {
        prevBtn.disabled = suscripcionHistorialPagina <= 1;
    }
    if (nextBtn) {
        nextBtn.disabled = suscripcionHistorialPagina >= totalPaginas;
    }
}
