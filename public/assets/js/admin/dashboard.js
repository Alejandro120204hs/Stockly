/**
 * Stockly — Panel de Super Admin: vista Dashboard (vanilla JS)
 * Depende de admin/layout.js (formatNumber) ya cargado antes que este.
 * Datos reales (App\Http\Controllers\Admin\DashboardController) -acá solo
 * se animan, no se calculan.
 *
 * Módulos:
 *   1. initCountUp  -> anima los números de las stat cards desde 0
 *   2. initBarChart -> anima el crecimiento de las barras del gráfico
 */

document.addEventListener('DOMContentLoaded', function () {
    initCountUp();
    initBarChart();
});

/* --------------------------------------------------------------------
 * 1. Contador animado en las stat cards
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

/* --------------------------------------------------------------------
 * 2. Gráfico de barras (ingresos)
 *
 * Cada barra trae su altura final en data-pct (0-100). Arrancan en
 * scaleY(0) y la transición CSS (ver admin/dashboard.css, .bar-chart__fill)
 * hace que "crezcan" al cargar la página -con transform, no con height,
 * para no forzar layout thrash en cada frame.
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
