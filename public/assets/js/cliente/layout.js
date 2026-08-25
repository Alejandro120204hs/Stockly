/**
 * Stockly — Panel del negocio cliente: LAYOUT compartido (vanilla JS)
 * Esto corre en TODAS las páginas del panel cliente (lo carga el layout
 * compartido). Cada página trae aparte solo lo suyo, y puede usar
 * formatNumber/formatCOP/normalizarTexto de acá porque este script se
 * carga primero.
 *
 * Módulos:
 *   1. initSidebarToggle     -> abrir/cerrar el sidebar en móvil
 *   2. initComingSoonActions -> aviso "disponible pronto" en accesos del
 *      sidebar y accesos rápidos que todavía no tienen vista construida
 */

document.addEventListener('DOMContentLoaded', function () {
    initSidebarToggle();
    initComingSoonActions();
});

/* --------------------------------------------------------------------
 * Utilidades compartidas por las páginas del panel cliente
 * ------------------------------------------------------------------ */
function formatNumber(value, decimals) {
    return value.toLocaleString('es-CO', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

function formatCOP(value) {
    return '$' + Math.round(value).toLocaleString('es-CO');
}

// Quita tildes para que buscar "ferreteria" también encuentre "Ferretería"
// -es normal que alguien escriba rápido sin acentos.
function normalizarTexto(texto) {
    return texto.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
}

/**
 * Formatea un input de dinero con puntos de miles MIENTRAS se escribe
 * (1.000, 10.000...). El input debe ser type="text" -un type="number"
 * nunca deja escribir el punto de miles, y si se le pone ahí el valor
 * numérico se rompe (el navegador cree que es un decimal).
 *
 * Guarda la posición del cursor contando cuántos dígitos había antes de
 * él, y la recalcula después de reformatear -si no, el cursor saltaría
 * siempre al final cada vez que se agrega un punto.
 */
function formatearInputDinero(input) {
    input.setAttribute('inputmode', 'numeric');
    input.setAttribute('autocomplete', 'off');

    input.addEventListener('input', function () {
        var valorAnterior = input.value;
        var cursorPos = input.selectionStart;
        var digitosAntesDelCursor = valorAnterior.slice(0, cursorPos).replace(/\D/g, '').length;

        var soloDigitos = valorAnterior.replace(/\D/g, '');
        input.value = soloDigitos ? parseInt(soloDigitos, 10).toLocaleString('es-CO') : '';

        var nuevaPos = 0;
        var digitosContados = 0;
        while (nuevaPos < input.value.length && digitosContados < digitosAntesDelCursor) {
            if (/\d/.test(input.value[nuevaPos])) {
                digitosContados++;
            }
            nuevaPos++;
        }
        input.setSelectionRange(nuevaPos, nuevaPos);
    });
}

/** Valor numérico real de un input formateado con formatearInputDinero -para
 * mandar al servidor o hacer cuentas, nunca parseFloat() directo (rompería
 * "6.000" convirtiéndolo en 6). */
function valorDineroInput(input) {
    return parseInt(input.value.replace(/\D/g, ''), 10) || 0;
}

/* --------------------------------------------------------------------
 * 1. Sidebar en móvil
 * ------------------------------------------------------------------ */
function initSidebarToggle() {
    var toggle = document.querySelector('.cliente-topbar__menu-toggle');
    var sidebar = document.querySelector('.cliente-sidebar');
    var overlay = document.querySelector('.cliente-sidebar-overlay');

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

    window.addEventListener('resize', function () {
        if (window.innerWidth > 900) {
            close();
        }
    });
}

/* --------------------------------------------------------------------
 * 2. Aviso "disponible pronto" para accesos sin vista construida
 * ------------------------------------------------------------------ */
function initComingSoonActions() {
    var buttons = document.querySelectorAll('[data-coming-soon]');
    if (buttons.length === 0) {
        return;
    }

    var toast = document.createElement('div');
    toast.className = 'cliente-toast';
    toast.setAttribute('role', 'status');
    document.body.appendChild(toast);

    var hideTimeout;

    buttons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            var mensaje = button.getAttribute('data-coming-soon') || 'Esta sección estará disponible pronto.';
            toast.textContent = mensaje;
            toast.classList.add('is-visible');

            window.clearTimeout(hideTimeout);
            hideTimeout = window.setTimeout(function () {
                toast.classList.remove('is-visible');
            }, 2400);
        });
    });
}
