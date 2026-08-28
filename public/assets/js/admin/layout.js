/**
 * Stockly — Panel de Super Admin: LAYOUT compartido (vanilla JS)
 * Esto corre en TODAS las páginas del panel admin (lo carga el layout
 * compartido). Cada página trae aparte solo lo suyo (ver admin/dashboard.js,
 * admin/empresas.js, etc.), que puede usar formatNumber/normalizarTexto de
 * acá porque este script se carga primero.
 *
 * Módulos:
 *   1. initSidebarToggle -> abrir/cerrar el sidebar en móvil
 *   2. initModuleBars    -> anima las barras de desglose de módulos
 *      (las usan tanto el Dashboard como Módulos)
 *   3. initFlashAlerts   -> SweetAlert2 con la paleta de Stockly, para el
 *      mensaje de bienvenida al iniciar sesión
 */

document.addEventListener('DOMContentLoaded', function () {
    initSidebarToggle();
    initModuleBars();
    initFlashAlerts();
});

/* --------------------------------------------------------------------
 * Utilidades compartidas por las páginas del panel admin
 * ------------------------------------------------------------------ */
function formatNumber(value, decimals) {
    return value.toLocaleString('es-CO', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

// Quita tildes para que buscar "ferreteria" también encuentre "Ferretería"
// -es normal que alguien escriba rápido sin acentos. Usado por los
// buscadores de las vistas de Empresas y Pagos.
function normalizarTexto(texto) {
    return texto.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
}

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
 * 2. Barras de desglose de módulos (Dashboard y Módulos)
 *
 * Cada barra trae su altura final en data-pct (0-100). Arrancan en
 * scaleX(0) y la transición CSS (ver admin/layout.css, .module-row__fill)
 * hace que "crezcan" al cargar -con transform, no con width, para no
 * forzar layout thrash en cada frame.
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
 * 3. Alertas de éxito (SweetAlert2)
 *
 * El backend deja el "tipo" de mensaje en session('status') (ver
 * AuthenticatedSessionController y Admin\ProfileController), y el layout
 * lo pasa al HTML como data-flash-status en el <body>. Acá solo se lee
 * una vez al cargar la página y se muestra la alerta correspondiente.
 * ------------------------------------------------------------------ */
function initFlashAlerts() {
    var status = document.body.getAttribute('data-flash-status');
    if (!status || typeof Swal === 'undefined') {
        return;
    }

    var mensajes = {};

    var mensaje = mensajes[status];
    if (!mensaje) {
        return;
    }

    Swal.fire({
        icon: 'success',
        title: mensaje.title,
        text: mensaje.text,
        showConfirmButton: false,
        timer: 2400,
        timerProgressBar: true,
        customClass: {
            popup: 'stockly-swal',
            container: 'stockly-swal-backdrop'
        }
    });
}
