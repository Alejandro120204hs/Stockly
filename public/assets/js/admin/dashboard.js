/**
 * Stockly — Panel de Super Admin: vista Dashboard (vanilla JS)
 * Depende de admin/layout.js (formatNumber) ya cargado antes que este.
 *
 * Módulos:
 *   1. initCountUp        -> anima los números de las stat cards desde 0
 *   2. initBarChart        -> anima el crecimiento de las barras del gráfico
 *   3. initActivarButtons -> feedback visual del botón "Activar" (demo,
 *      no persiste nada todavía: no hay backend conectado)
 */

document.addEventListener('DOMContentLoaded', function () {
    initCountUp();
    initBarChart();
    initActivarButtons();
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

/* --------------------------------------------------------------------
 * 3. Botón "Activar" en la cola de pagos pendientes
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
