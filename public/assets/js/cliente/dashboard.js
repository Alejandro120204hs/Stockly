/**
 * Stockly — Panel del negocio cliente: vista Dashboard (vanilla JS)
 * Depende de cliente/layout.js (formatNumber) ya cargado antes que este.
 *
 * Módulos:
 *   1. initCountUp    -> anima los números de las stat cards desde 0
 *   2. initCajaAction -> demo de "Abrir caja" (cambia el estado en
 *      pantalla, no persiste -no hay backend conectado todavía)
 *   3. initBarChart   -> anima el crecimiento de las barras del gráfico
 *      de ventas de la semana
 */

document.addEventListener('DOMContentLoaded', function () {
    initCountUp();
    initCajaAction();
    initBarChart();
});

/* --------------------------------------------------------------------
 * 1. Contador animado en las stat cards
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
 * 2. Demo de "Abrir caja"
 *
 * Cambia la stat card de estado de caja y el acceso rápido a "Abierta",
 * como feedback visual -no hay backend de caja todavía, así que no
 * persiste al recargar la página.
 * ------------------------------------------------------------------ */
function initCajaAction() {
    var button = document.getElementById('abrirCajaAction');
    var cajaValue = document.getElementById('cajaEstadoValor');
    var cajaMeta = document.getElementById('cajaEstadoMeta');
    var cajaIcon = document.getElementById('cajaEstadoIcono');

    if (!button || !cajaValue) {
        return;
    }

    button.addEventListener('click', function () {
        if (button.disabled) {
            return;
        }

        var label = button.querySelector('.quick-action__label');
        var hint = button.querySelector('.quick-action__hint');
        button.disabled = true;

        if (label) {
            label.textContent = 'Abriendo caja...';
        }

        window.setTimeout(function () {
            cajaValue.textContent = 'Abierta';
            if (cajaMeta) {
                cajaMeta.textContent = 'Base inicial: $150.000 · desde ahora';
            }
            if (cajaIcon) {
                cajaIcon.closest('.stat-card').classList.remove('stat-card--mist');
                cajaIcon.closest('.stat-card').classList.add('stat-card--sage');
            }
            if (label) {
                label.textContent = 'Caja abierta';
            }
            if (hint) {
                hint.textContent = 'Ya puedes empezar a vender';
            }
        }, 700);
    });
}

/* --------------------------------------------------------------------
 * 3. Gráfico de barras (ventas de la semana)
 *
 * Igual patrón que el panel admin: crece con transform en vez de height
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
            }, index * 60);
        });
    }, 150);
}
