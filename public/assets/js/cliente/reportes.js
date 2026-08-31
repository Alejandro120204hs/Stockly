/* ==========================================================================
   Stockly — Reportes JS
   Maneja: cambio de período, count-up de stats, animaciones de barras,
   donut de métodos de pago, top productos, gastos por categoría y el
   href dinámico del botón de descarga PDF.
   ========================================================================== */

(function () {
    'use strict';

    const CIRC = 2 * Math.PI * 25; // circunferencia del donut (r=25)

    let periodoActual = 'semana';
    let allData       = {};
    let countUpTimers = [];

    /* ------------------------------------------------------------------ */
    /* Inicialización                                                       */
    /* ------------------------------------------------------------------ */

    document.addEventListener('DOMContentLoaded', function () {
        const island = document.getElementById('reportesData');
        if (!island) return;

        try {
            allData = JSON.parse(island.textContent);
        } catch (e) {
            console.error('Reportes: error al parsear datos', e);
            return;
        }

        initTabs();
        initDiaTab();
        renderPeriodo(periodoActual);
    });

    /* ------------------------------------------------------------------ */
    /* Tabs (Esta semana / Este mes / Este año)                             */
    /* ------------------------------------------------------------------ */

    function initTabs() {
        // [data-periodo] excluye la pestaña de calendario (#reporteDiaTab),
        // que no es un período fijo y tiene su propio manejador abajo.
        document.querySelectorAll('.reporte-tab[data-periodo]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const periodo = btn.dataset.periodo;
                if (periodo === periodoActual) return;

                desactivarTodasLasPestanas();
                btn.classList.add('is-active');
                btn.setAttribute('aria-selected', 'true');

                periodoActual = periodo;
                renderPeriodo(periodo);
                actualizarPdfHref({ periodo: periodo });
            });
        });
    }

    function desactivarTodasLasPestanas() {
        document.querySelectorAll('.reporte-tab').forEach(function (t) {
            t.classList.remove('is-active');
            t.setAttribute('aria-selected', 'false');
        });
    }

    function actualizarPdfHref(params) {
        const btn = document.getElementById('reportePdfBtn');
        if (!btn) return;
        const url = new URL(btn.href);
        url.search = '';
        Object.keys(params).forEach(function (key) { url.searchParams.set(key, params[key]); });
        btn.href = url.toString();
    }

    /* ------------------------------------------------------------------ */
    /* Selector de un día puntual (calendario)                              */
    /* ------------------------------------------------------------------ */

    function initDiaTab() {
        const tab = document.getElementById('reporteDiaTab');
        const input = document.getElementById('reporteDiaInput');
        if (!tab || !input) return;

        function activarDia(fecha) {
            if (!fecha) return;

            desactivarTodasLasPestanas();
            tab.classList.add('is-active');
            tab.setAttribute('aria-selected', 'true');
            periodoActual = 'dia';

            cancelCountUps();
            tab.classList.add('is-cargando');

            fetch('/cliente/reportes/dia?fecha=' + encodeURIComponent(fecha), {
                headers: { 'Accept': 'application/json' },
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('No se pudo cargar el reporte de ese día.');
                    return res.json();
                })
                .then(function (d) {
                    renderData(d);
                    actualizarPdfHref({ fecha: fecha });
                })
                .catch(function (err) {
                    console.error('Reportes:', err);
                })
                .finally(function () {
                    tab.classList.remove('is-cargando');
                });
        }

        input.addEventListener('change', function () {
            activarDia(input.value);
        });

        // El navegador NO dispara "change" si la fecha elegida en el
        // selector nativo es la misma que ya tenía el input -ej. vienes
        // de "Este mes", abres el calendario y le das clic a "Hoy", pero
        // el input ya tenía hoy como valor por defecto desde que cargó la
        // página, así que técnicamente "no cambió" nada para el navegador.
        // Por eso también se activa al abrir el selector, si todavía no
        // estábamos en modo "día" -sin esto, el panel se quedaba en el
        // período anterior aunque el usuario sí haya elegido un día.
        tab.addEventListener('click', function () {
            if (periodoActual === 'dia') return;
            activarDia(input.value);
        });
    }

    /* ------------------------------------------------------------------ */
    /* Render completo de un período                                        */
    /* ------------------------------------------------------------------ */

    function renderPeriodo(key) {
        const d = allData[key];
        if (!d) return;

        renderData(d);
    }

    function renderData(d) {
        cancelCountUps();
        renderStats(d);
        renderMetodos(d.pagoEfectivo, d.pagoDigital);
        renderGastosCat(d.gastosCategorias);
        renderTopProductos(d.topProductos);
    }

    /* ------------------------------------------------------------------ */
    /* Stats + Count-Up                                                    */
    /* ------------------------------------------------------------------ */

    function renderStats(d) {
        countUp(document.getElementById('statIngresos'),    0, d.ingresos,     true);
        countUp(document.getElementById('statGastos'),      0, d.gastos,       true);
        countUp(document.getElementById('statGanancia'),    0, d.gananciaNeta, true);
        countUp(document.getElementById('statVentas'),      0, d.cantidadVentas, false);

        const cardGan = document.getElementById('statGananciaNeta');
        if (cardGan) {
            cardGan.classList.toggle('is-positivo', d.gananciaNeta >= 0);
            cardGan.classList.toggle('is-negativo', d.gananciaNeta <  0);
        }
    }

    function countUp(el, from, to, esMoney) {
        if (!el) return;
        const dur = 700;
        const start = performance.now();
        const prefix = esMoney ? '$' : '';

        function tick(now) {
            const t = Math.min((now - start) / dur, 1);
            const ease = 1 - Math.pow(1 - t, 3);
            const val = from + (to - from) * ease;
            el.textContent = prefix + formatNum(val, esMoney);
            if (t < 1) {
                const id = requestAnimationFrame(tick);
                countUpTimers.push(id);
            } else {
                el.textContent = prefix + formatNum(to, esMoney);
            }
        }
        const id = requestAnimationFrame(tick);
        countUpTimers.push(id);
    }

    function cancelCountUps() {
        countUpTimers.forEach(cancelAnimationFrame);
        countUpTimers = [];
    }

    function formatNum(n, esMoney) {
        if (esMoney) {
            return Math.abs(Math.round(n)).toLocaleString('es-CO');
        }
        return Math.round(n).toString();
    }

    /* ------------------------------------------------------------------ */
    /* Métodos de pago (donut + barras)                                    */
    /* ------------------------------------------------------------------ */

    function renderMetodos(efectivo, digital) {
        const total = efectivo + digital;
        const pctE  = total > 0 ? Math.round((efectivo / total) * 100) : 0;
        const pctD  = 100 - pctE;

        // Donut
        const circE = (pctE / 100) * CIRC;
        const circD = (pctD / 100) * CIRC;
        const dE = document.getElementById('donutEfectivo');
        const dD = document.getElementById('donutDigital');

        if (dE) {
            dE.setAttribute('stroke-dashoffset', CIRC * 0.25);
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    dE.setAttribute('stroke-dasharray', circE + ' ' + (CIRC - circE));
                    dE.setAttribute('stroke-dashoffset', CIRC * 0.25);
                });
            });
        }
        if (dD) {
            const offset = CIRC * 0.25 - circE;
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    dD.setAttribute('stroke-dasharray', circD + ' ' + (CIRC - circD));
                    dD.setAttribute('stroke-dashoffset', offset);
                });
            });
        }

        const pE = document.getElementById('pctEfectivo');
        const pD = document.getElementById('pctDigital');
        if (pE) pE.textContent = total > 0 ? pctE + '%' : '—';
        if (pD) pD.textContent = total > 0 ? pctD + '%' : '—';

        // Barras
        const lista = document.getElementById('reporteMetodosList');
        if (!lista) return;
        lista.innerHTML = '';

        [
            { label: 'Efectivo', monto: efectivo, pct: pctE, cls: 'efectivo' },
            { label: 'Digital',  monto: digital,  pct: pctD, cls: 'digital'  },
        ].forEach(function (m) {
            const row = document.createElement('div');
            row.className = 'reporte-metodo-row';

            row.innerHTML = '<div class="reporte-metodo-row__head">' +
                '<span>' + m.label + '</span>' +
                '<span>$' + formatNum(m.monto, true) + (total > 0 ? ' · ' + m.pct + '%' : '') + '</span>' +
                '</div>' +
                '<div class="reporte-metodo-row__track">' +
                '<div class="reporte-metodo-row__fill reporte-metodo-row__fill--' + m.cls + '" style="width:0"></div>' +
                '</div>';

            lista.appendChild(row);
        });

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                lista.querySelectorAll('.reporte-metodo-row__fill').forEach(function (fill) {
                    const cls = fill.classList.contains('reporte-metodo-row__fill--efectivo') ? 'efectivo' : 'digital';
                    const pct = cls === 'efectivo' ? pctE : pctD;
                    fill.style.width = (total > 0 ? pct : 0) + '%';
                });
            });
        });
    }

    /* ------------------------------------------------------------------ */
    /* Gastos por categoría                                                 */
    /* ------------------------------------------------------------------ */

    function renderGastosCat(cats) {
        const wrap = document.getElementById('reporteGastosCat');
        if (!wrap) return;
        wrap.innerHTML = '';

        const LABELS = {
            nomina:    'Nómina',
            arriendo:  'Arriendo',
            servicios: 'Servicios',
            otros:     'Otros',
        };

        const valores = Object.values(cats);
        const maxVal  = Math.max.apply(null, valores.concat([1]));
        const total   = valores.reduce(function (s, v) { return s + v; }, 0);

        let hayAlgo = false;
        Object.keys(LABELS).forEach(function (key) {
            const val = cats[key] || 0;
            if (val > 0) hayAlgo = true;
            const pct = Math.round((val / maxVal) * 100);
            const pctDel = total > 0 ? Math.round((val / total) * 100) + '%' : '0%';

            const row = document.createElement('div');
            row.className = 'reporte-cat-row';
            row.innerHTML = '<div class="reporte-cat-row__head">' +
                '<span>' + LABELS[key] + '</span>' +
                '<span>$' + formatNum(val, true) + ' · ' + pctDel + '</span>' +
                '</div>' +
                '<div class="reporte-cat-row__track">' +
                '<div class="reporte-cat-row__fill reporte-cat-row__fill--' + key + '" style="width:0"></div>' +
                '</div>';
            wrap.appendChild(row);
        });

        if (!hayAlgo) {
            wrap.innerHTML = '<p class="reporte-empty">Sin gastos en este período.</p>';
            return;
        }

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                wrap.querySelectorAll('.reporte-cat-row__fill').forEach(function (fill) {
                    const key = fill.className.match(/reporte-cat-row__fill--(\w+)/)?.[1];
                    const val = cats[key] || 0;
                    const pct = Math.round((val / maxVal) * 100);
                    fill.style.width = pct + '%';
                });
            });
        });
    }

    /* ------------------------------------------------------------------ */
    /* Top productos                                                        */
    /* ------------------------------------------------------------------ */

    function renderTopProductos(top) {
        const wrap = document.getElementById('reporteTopProductos');
        if (!wrap) return;
        wrap.innerHTML = '';

        if (!top || top.length === 0) {
            wrap.innerHTML = '<p class="reporte-empty">Sin ventas en este período.</p>';
            return;
        }

        const maxCant = top[0].cantidad || 1;

        top.forEach(function (prod, i) {
            const pct = Math.round((prod.cantidad / maxCant) * 100);

            const row = document.createElement('div');
            row.className = 'reporte-top-row';
            row.innerHTML = '<div class="reporte-top-row__num">' + (i + 1) + '</div>' +
                '<div class="reporte-top-row__info">' +
                '<div class="reporte-top-row__name">' + escHtml(prod.nombre) + '</div>' +
                '<div class="reporte-top-row__track">' +
                '<div class="reporte-top-row__fill" style="width:0"></div>' +
                '</div>' +
                '</div>' +
                '<div class="reporte-top-row__qty">' + prod.cantidad + ' und.</div>';

            wrap.appendChild(row);
        });

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                wrap.querySelectorAll('.reporte-top-row__fill').forEach(function (fill, i) {
                    const prod = top[i];
                    const pct  = Math.round(((prod ? prod.cantidad : 0) / maxCant) * 100);
                    fill.style.width = pct + '%';
                });
            });
        });
    }

    /* ------------------------------------------------------------------ */
    /* Utilidades                                                           */
    /* ------------------------------------------------------------------ */

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

}());
