/**
 * Stockly — Panel del negocio cliente: vista Dashboard (vanilla JS)
 * Depende de cliente/layout.js (formatNumber) ya cargado antes que este.
 *
 * Módulos:
 *   1. initCountUp        -> anima los números de las stat cards desde 0
 *   2. initBarChart       -> anima el crecimiento de las barras del gráfico
 *      de ventas de la semana
 *   3. initVentasRecientes -> al hacer click en una fila abre el panel de
 *      detalle compartido con Ventas (cliente/venta-slide-over.js); expone
 *      window.agregarVentaALaTabla para que "Nueva venta" actualice las
 *      stat cards, el gráfico y la lista sin recargar la página -mismo
 *      hook que ya usa la vista Ventas, pero acá actualiza el Dashboard.
 *
 * El acceso rápido "Abrir caja" vive aparte en cliente/abrir-caja-modal.js
 * -llama al backend real de Caja, ya no es una simulación.
 */

document.addEventListener('DOMContentLoaded', function () {
    initCountUp();
    initBarChart();
    initVentasRecientes();
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
 * 2. Gráfico de barras (ventas de la semana)
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

/* --------------------------------------------------------------------
 * 3. Ventas recientes -> abre el mismo panel de detalle que Ventas
 * ------------------------------------------------------------------ */
function initVentasRecientes() {
    var dataScript = document.getElementById('ventasRecientesData');
    var saleList = document.querySelector('.sale-list');
    if (!dataScript || !saleList) {
        return;
    }

    var ventasById = {};
    JSON.parse(dataScript.textContent).forEach(function (venta) {
        ventasById[venta.id] = venta;
    });

    // window.abrirVentaSlideOver la define venta-slide-over.js en su
    // propio DOMContentLoaded -se comprueba acá adentro (al hacer click),
    // no arriba al inicializar, porque el orden en que corren los
    // distintos <script defer> de la página no está garantizado.
    function wireFilaVenta(row, id) {
        row.addEventListener('click', function () {
            if (window.abrirVentaSlideOver) {
                window.abrirVentaSlideOver(ventasById[id]);
            }
        });

        row.addEventListener('keydown', function (event) {
            if ((event.key === 'Enter' || event.key === ' ') && window.abrirVentaSlideOver) {
                event.preventDefault();
                window.abrirVentaSlideOver(ventasById[id]);
            }
        });
    }

    document.querySelectorAll('.sale-row[data-venta-id]').forEach(function (row) {
        wireFilaVenta(row, parseInt(row.getAttribute('data-venta-id'), 10));
    });

    // Expuesto para que "Nueva venta" (nueva-venta-modal.js) agregue su
    // fila acá y refresque las stat cards + el gráfico de la semana, sin
    // recargar la página -mismo nombre de hook que ya usa la vista
    // Ventas, pero cada página define su propia versión (nunca cargan
    // los dos scripts juntos).
    window.agregarVentaALaTabla = function (venta) {
        ventasById[venta.id] = venta;

        var vacio = saleList.querySelector('.sale-list__empty');
        if (vacio) {
            vacio.remove();
        }

        var fila = crearFilaVentaReciente(venta);
        wireFilaVenta(fila, venta.id);
        saleList.insertBefore(fila, saleList.firstChild);

        ajustarStatsHoy(venta.total, 1, venta.ganancia);
        ajustarBarraHoy(venta.total);
    };

    // Expuesto para que venta-slide-over.js avise cuando se anula una
    // venta desde acá -si esa venta es de hoy, se le resta a las stat
    // cards y al gráfico exactamente lo que se le había sumado al
    // registrarla (mismo cálculo, signo contrario). La fila NO se quita
    // de la lista, se marca -para que quede el rastro visible.
    window.marcarVentaAnulada = function (venta) {
        ventasById[venta.id] = venta;

        var row = saleList.querySelector('.sale-row[data-venta-id="' + venta.id + '"]');
        if (row) {
            row.classList.add('venta-fila-anulada');
        }

        ajustarStatsHoy(-venta.total, -1, -venta.ganancia);
        ajustarBarraHoy(-venta.total);
    };
}

/** Misma fila que ya arma dashboard-cliente.blade.php -id/hora/método/
 * monto son datos del sistema (no texto libre de un usuario), por eso acá
 * sí arma el ícono con innerHTML directo. */
function crearFilaVentaReciente(venta) {
    var row = document.createElement('div');
    row.className = 'sale-row' + (venta.metodo === 'efectivo' ? ' sale-row--efectivo' : '');
    row.setAttribute('data-venta-id', venta.id);
    row.tabIndex = 0;

    var icono = document.createElement('div');
    icono.className = 'sale-row__icon';
    icono.innerHTML = venta.metodo === 'efectivo'
        ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="6" width="19" height="12" rx="2.5"/><circle cx="12" cy="12" r="3"/></svg>'
        : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19M6 15h4"/></svg>';

    var info = document.createElement('div');
    info.className = 'sale-row__info';

    var idEl = document.createElement('div');
    idEl.className = 'sale-row__id';
    idEl.textContent = 'Venta #' + venta.id;

    var metaEl = document.createElement('div');
    metaEl.className = 'sale-row__meta';
    // venta.hora llega como "Hoy, 3:06 p.m." -acá alcanza con la hora
    // sola, "Hoy" ya lo dice el subtítulo del panel.
    metaEl.textContent = venta.hora.replace(/^[^,]+,\s*/, '') + ' · ' + (venta.metodo === 'efectivo' ? 'Efectivo' : 'Wompi');

    info.appendChild(idEl);
    info.appendChild(metaEl);

    var monto = document.createElement('div');
    monto.className = 'sale-row__monto';
    monto.textContent = formatCOP(venta.total);

    row.appendChild(icono);
    row.appendChild(info);
    row.appendChild(monto);

    return row;
}

/** Ventas de hoy (total + transacciones) y ganancia bruta/neta -"Nueva
 * venta" suma (deltaCantidad=1) y anular una venta de hoy resta
 * (deltaCantidad=-1); una venta nunca cambia los gastos del día, así que
 * sube o baja ambas ganancias exactamente lo mismo. */
function ajustarStatsHoy(deltaTotal, deltaCantidad, deltaGanancia) {
    var ventasHoyValor = document.getElementById('ventasHoyValor');
    if (ventasHoyValor) {
        var nuevoTotal = parseFloat(ventasHoyValor.getAttribute('data-count')) + deltaTotal;
        ventasHoyValor.setAttribute('data-count', nuevoTotal);
        ventasHoyValor.textContent = '$' + formatNumber(nuevoTotal, 0);
    }

    var ventasHoyMeta = document.getElementById('ventasHoyMeta');
    if (ventasHoyMeta) {
        var nuevaCantidad = Math.max(0, parseInt(ventasHoyMeta.getAttribute('data-cantidad'), 10) + deltaCantidad);
        ventasHoyMeta.setAttribute('data-cantidad', nuevaCantidad);
        ventasHoyMeta.textContent = nuevaCantidad + ' transacci' + (nuevaCantidad === 1 ? 'ón' : 'ones');
    }

    ['gananciaBrutaValor', 'gananciaNetaValor'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) {
            var nuevoValor = parseFloat(el.getAttribute('data-count')) + deltaGanancia;
            el.setAttribute('data-count', nuevoValor);
            el.textContent = '$' + formatNumber(nuevoValor, 0);
        }
    });
}

/** Sube o baja el total de HOY en el gráfico de la semana y recalcula la
 * altura de las 7 barras -si eso cambia cuál es el día más fuerte de la
 * semana, las demás barras se reajustan relativas a la nueva más alta,
 * igual que hace el backend. */
function ajustarBarraHoy(deltaTotal) {
    var fills = document.querySelectorAll('.bar-chart__fill[data-total]');
    if (fills.length === 0) {
        return;
    }

    var datos = [];
    var maxTotal = 0;

    fills.forEach(function (fill) {
        var total = parseFloat(fill.getAttribute('data-total'));
        if (fill.getAttribute('data-es-hoy') === '1') {
            total += deltaTotal;
            fill.setAttribute('data-total', total);
        }
        datos.push({ fill: fill, total: total });
        maxTotal = Math.max(maxTotal, total);
    });

    maxTotal = maxTotal || 1;

    datos.forEach(function (dato) {
        var pct = Math.round((dato.total / maxTotal) * 100);
        dato.fill.setAttribute('data-pct', pct);
        dato.fill.setAttribute('data-value', '$' + formatNumber(dato.total, 0));
        dato.fill.style.transform = 'scaleY(' + (pct / 100) + ')';
    });
}
