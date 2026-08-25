/**
 * Stockly — Panel de Super Admin: vista Empresas (vanilla JS)
 * Depende de admin/layout.js (formatNumber, normalizarTexto).
 *
 * Los datos (incluyendo módulos por empresa) vienen embebidos en un
 * <script type="application/json"> en la vista -acá solo se leen y se
 * usan para pintar el panel al hacer clic en una fila. Todo lo que cambia
 * acá (activar, suspender, prender/apagar un módulo) es un demo visual
 * del lado del cliente: no hay backend conectado todavía, así que no
 * persiste al recargar la página.
 */

document.addEventListener('DOMContentLoaded', function () {
    initEmpresasPanel();
});

function initEmpresasPanel() {
    var table = document.getElementById('empresasTable');
    var dataScript = document.getElementById('empresasData');

    if (!table || !dataScript) {
        return;
    }

    var empresas = JSON.parse(dataScript.textContent);
    var empresasById = {};
    empresas.forEach(function (empresa) {
        empresasById[empresa.id] = empresa;
    });

    var estadoLabels = {
        activo: 'Activo',
        por_vencer: 'Por vencer',
        vencido: 'Vencido',
        suspendido: 'Suspendido'
    };

    var overlay = document.getElementById('slideOverOverlay');
    var slideOver = document.getElementById('empresaSlideOver');
    var closeBtn = document.getElementById('slideOverClose');
    var activarBtn = document.getElementById('slideOverActivar');
    var suspenderBtn = document.getElementById('slideOverSuspender');

    var currentId = null;

    function pillMarkup(estado) {
        return {
            className: 'status-pill status-pill--' + estado.replace('_', '-'),
            label: estadoLabels[estado] || estado
        };
    }

    function renderModulos(empresa) {
        var container = document.getElementById('slideOverModulos');
        container.innerHTML = '';

        empresa.modulos.forEach(function (modulo) {
            var row = document.createElement('div');
            row.className = 'module-toggle-row';

            var name = document.createElement('span');
            name.className = 'module-toggle-row__name';
            name.textContent = modulo.nombre;

            var label = document.createElement('label');
            label.className = 'module-toggle';

            var input = document.createElement('input');
            input.type = 'checkbox';
            input.checked = modulo.activo;
            input.setAttribute('aria-label', modulo.nombre);
            input.addEventListener('change', function () {
                modulo.activo = input.checked;
                syncModulosCount(empresa);
            });

            var track = document.createElement('span');
            track.className = 'module-toggle__track';

            label.appendChild(input);
            label.appendChild(track);
            row.appendChild(name);
            row.appendChild(label);
            container.appendChild(row);
        });
    }

    function syncModulosCount(empresa) {
        var row = table.querySelector('tr[data-empresa-id="' + empresa.id + '"]');
        if (!row) {
            return;
        }
        var activos = empresa.modulos.filter(function (m) { return m.activo; }).length;
        var cell = row.querySelector('[data-modulos-cell]');
        if (cell) {
            cell.textContent = activos + '/' + empresa.modulos.length;
        }
    }

    function syncEstadoPill(empresa) {
        var row = table.querySelector('tr[data-empresa-id="' + empresa.id + '"]');
        var pill = pillMarkup(empresa.estado);

        if (row) {
            var rowPill = row.querySelector('.status-pill');
            if (rowPill) {
                rowPill.className = pill.className;
                rowPill.textContent = pill.label;
            }
        }

        var slideOverPill = document.getElementById('slideOverEstado');
        slideOverPill.className = pill.className;
        slideOverPill.textContent = pill.label;

        updateActionButtons(empresa);
    }

    function updateActionButtons(empresa) {
        var estaSuspendida = empresa.estado === 'suspendido';
        var estaVencida = empresa.estado === 'vencido';
        activarBtn.disabled = !estaSuspendida && !estaVencida;
        suspenderBtn.disabled = estaSuspendida;
    }

    function openEmpresa(id) {
        var empresa = empresasById[id];
        if (!empresa) {
            return;
        }
        currentId = id;

        document.getElementById('slideOverNombre').textContent = empresa.nombre;
        document.getElementById('slideOverCorreo').textContent = empresa.correo;
        document.getElementById('slideOverTelefono').textContent = empresa.telefono;
        document.getElementById('slideOverDireccion').textContent = empresa.direccion;
        document.getElementById('slideOverUbicacion').textContent = empresa.ciudad + ', ' + empresa.departamento;
        document.getElementById('slideOverNit').textContent = empresa.nit;
        document.getElementById('slideOverTipoPersona').textContent = empresa.tipoPersona;
        document.getElementById('slideOverRegimen').textContent = empresa.regimen;
        document.getElementById('slideOverVencimiento').textContent = empresa.vencimiento;

        syncEstadoPill(empresa);
        renderModulos(empresa);

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
        var id = parseInt(row.getAttribute('data-empresa-id'), 10);

        row.addEventListener('click', function () {
            openEmpresa(id);
        });

        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openEmpresa(id);
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

    function runAction(button, otherButton, nuevoEstado, loadingText) {
        var empresa = empresasById[currentId];
        if (!empresa || button.disabled) {
            return;
        }

        var originalText = button.textContent;
        button.disabled = true;
        otherButton.disabled = true;
        button.textContent = loadingText;

        window.setTimeout(function () {
            empresa.estado = nuevoEstado;
            button.textContent = originalText;
            syncEstadoPill(empresa);
        }, 600);
    }

    activarBtn.addEventListener('click', function () {
        runAction(activarBtn, suspenderBtn, 'activo', 'Activando...');
    });

    suspenderBtn.addEventListener('click', function () {
        runAction(suspenderBtn, activarBtn, 'suspendido', 'Suspendiendo...');
    });

    /* ---------- Búsqueda + filtro por estado ---------- */
    var searchInput = document.getElementById('empresasSearch');
    var estadoFilter = document.getElementById('empresasEstadoFilter');
    var emptyState = document.getElementById('empresasEmpty');

    function applyFilters() {
        var term = normalizarTexto(searchInput.value.trim());
        var estado = estadoFilter.value;
        var visibleCount = 0;

        table.querySelectorAll('.data-table__row').forEach(function (row) {
            var id = parseInt(row.getAttribute('data-empresa-id'), 10);
            var empresa = empresasById[id];
            var matchesTerm = !term
                || normalizarTexto(empresa.nombre).indexOf(term) !== -1
                || normalizarTexto(empresa.nit).indexOf(term) !== -1;
            var matchesEstado = !estado || empresa.estado === estado;
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
