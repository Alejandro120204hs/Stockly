/**
 * Stockly — Panel de Super Admin (vanilla JS)
 * Módulos:
 *   1. initSidebarToggle -> abrir/cerrar el sidebar en móvil
 *   2. initCountUp       -> anima los números de las stat cards desde 0
 *   3. initBarChart      -> anima el crecimiento de las barras del gráfico
 *   4. initModuleBars    -> anima las barras de desglose de módulos
 *   5. initActivarButtons -> feedback visual del botón "Activar" (demo,
 *      no persiste nada todavía: no hay backend conectado)
 *   6. initEmpresasPanel  -> tabla + panel lateral de la vista Empresas
 *      (búsqueda, filtro por estado, activar/suspender, módulos - demo)
 */

document.addEventListener('DOMContentLoaded', function () {
    initSidebarToggle();
    initCountUp();
    initBarChart();
    initModuleBars();
    initActivarButtons();
    initEmpresasPanel();
});

/* --------------------------------------------------------------------
 * 1. Sidebar en móvil
 * ------------------------------------------------------------------ */
function initSidebarToggle() {
    var toggle = document.querySelector('.admin-topbar__menu-toggle');
    var sidebar = document.querySelector('.admin-sidebar');
    var overlay = document.querySelector('.admin-sidebar-overlay');

    if (!toggle || !sidebar || !overlay) {
        return;
    }

    function open() {
        sidebar.classList.add('is-open');
        overlay.classList.add('is-visible');
    }

    function close() {
        sidebar.classList.remove('is-open');
        overlay.classList.remove('is-visible');
    }

    toggle.addEventListener('click', function () {
        if (sidebar.classList.contains('is-open')) {
            close();
        } else {
            open();
        }
    });

    overlay.addEventListener('click', close);

    // Si cambian a una pantalla grande con el menú móvil abierto, se cierra
    // solo (si no, quedaría "abierto" escondido y confundiría al volver a chico)
    window.addEventListener('resize', function () {
        if (window.innerWidth > 900) {
            close();
        }
    });
}

/* --------------------------------------------------------------------
 * 2. Contador animado en las stat cards
 *
 * Cada <span data-count="128"> empieza en 0 y sube hasta el valor real
 * con una curva de desaceleración, en vez de aparecer el número de golpe.
 * ------------------------------------------------------------------ */
function initCountUp() {
    var counters = document.querySelectorAll('[data-count]');
    if (counters.length === 0) {
        return;
    }

    var DURATION = 1100;

    counters.forEach(function (el) {
        var target = parseFloat(el.getAttribute('data-count'));
        var prefix = el.getAttribute('data-prefix') || '';
        var suffix = el.getAttribute('data-suffix') || '';
        var decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
        var start = null;

        function frame(timestamp) {
            if (start === null) {
                start = timestamp;
            }
            var progress = Math.min(1, (timestamp - start) / DURATION);
            // easeOutQuint: arranca rápido y se asienta suave al final
            var eased = 1 - Math.pow(1 - progress, 5);
            var current = target * eased;

            el.textContent = prefix + formatNumber(current, decimals) + suffix;

            if (progress < 1) {
                window.requestAnimationFrame(frame);
            } else {
                el.textContent = prefix + formatNumber(target, decimals) + suffix;
            }
        }

        window.requestAnimationFrame(frame);
    });
}

function formatNumber(value, decimals) {
    return value.toLocaleString('es-CO', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

/* --------------------------------------------------------------------
 * 3. Gráfico de barras (ingresos)
 *
 * Cada barra trae su altura final en data-pct (0-100). Arrancan en
 * scaleY(0) y la transición CSS (ver admin.css, .bar-chart__fill) hace
 * que "crezcan" al cargar la página -con transform, no con height, para
 * no forzar layout thrash en cada frame. Solo hace falta setear el valor
 * con un pequeño delay para que el navegador registre el estado inicial
 * (escala 0) antes de animar al valor real.
 * ------------------------------------------------------------------ */
function initBarChart() {
    var bars = document.querySelectorAll('.bar-chart__fill[data-pct]');
    if (bars.length === 0) {
        return;
    }

    window.setTimeout(function () {
        bars.forEach(function (bar, index) {
            window.setTimeout(function () {
                var pct = parseFloat(bar.getAttribute('data-pct')) / 100;
                bar.style.transform = 'scaleY(' + pct + ')';
            }, index * 60); // escalonado, una barra tras otra
        });
    }, 150);
}

/* --------------------------------------------------------------------
 * 4. Barras de desglose de módulos (mismo patrón que el gráfico, con
 *    scaleX en vez de width por la misma razón de rendimiento)
 * ------------------------------------------------------------------ */
function initModuleBars() {
    var bars = document.querySelectorAll('.module-row__fill[data-pct]');
    if (bars.length === 0) {
        return;
    }

    window.setTimeout(function () {
        bars.forEach(function (bar, index) {
            window.setTimeout(function () {
                var pct = parseFloat(bar.getAttribute('data-pct')) / 100;
                bar.style.transform = 'scaleX(' + pct + ')';
            }, index * 100);
        });
    }, 150);
}

/* --------------------------------------------------------------------
 * 5. Botón "Activar" en la cola de pagos pendientes
 *
 * Nota: esto es solo feedback visual del lado del cliente. Todavía no
 * hay backend conectado, así que no activa nada de verdad ni persiste
 * al recargar la página -es una demostración de la interacción.
 * ------------------------------------------------------------------ */
function initActivarButtons() {
    var buttons = document.querySelectorAll('.activar-btn');

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            if (button.disabled) {
                return;
            }

            var row = button.closest('.payment-row');
            var label = button.querySelector('.activar-btn__label');
            button.disabled = true;

            if (label) {
                label.textContent = 'Activando...';
            }

            window.setTimeout(function () {
                if (label) {
                    label.textContent = 'Activado';
                }
                if (row) {
                    row.classList.add('is-done');
                }
            }, 700);
        });
    });
}

/* --------------------------------------------------------------------
 * 6. Vista de Empresas: tabla + panel lateral (slide-over)
 *
 * Los datos (incluyendo módulos por empresa) vienen embebidos en un
 * <script type="application/json"> en la vista -acá solo se leen y se
 * usan para pintar el panel al hacer clic en una fila. Todo lo que
 * cambia acá (activar, suspender, prender/apagar un módulo) es un demo
 * visual del lado del cliente: no hay backend conectado todavía, así
 * que no persiste al recargar la página.
 * ------------------------------------------------------------------ */
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

    // Quita tildes para que buscar "ferreteria" también encuentre
    // "Ferretería" -es normal que alguien escriba rápido sin acentos.
    function normalizar(texto) {
        return texto.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    }

    function applyFilters() {
        var term = normalizar(searchInput.value.trim());
        var estado = estadoFilter.value;
        var visibleCount = 0;

        table.querySelectorAll('.data-table__row').forEach(function (row) {
            var id = parseInt(row.getAttribute('data-empresa-id'), 10);
            var empresa = empresasById[id];
            var matchesTerm = !term
                || normalizar(empresa.nombre).indexOf(term) !== -1
                || normalizar(empresa.nit).indexOf(term) !== -1;
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
