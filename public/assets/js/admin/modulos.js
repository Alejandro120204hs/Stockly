/**
 * Stockly — Panel de Super Admin: vista Módulos (vanilla JS)
 * Depende de admin/layout.js (formatNumber, e initModuleBars ya anima las
 * barras internas de cada tarjeta con solo que existan en el DOM).
 */

document.addEventListener('DOMContentLoaded', function () {
    initModulosPanel();
});

function initModulosPanel() {
    var cards = document.querySelectorAll('.modulo-card');
    var dataScript = document.getElementById('modulosData');

    if (cards.length === 0 || !dataScript) {
        return;
    }

    var modulos = JSON.parse(dataScript.textContent);
    var modulosById = {};
    modulos.forEach(function (modulo) {
        modulosById[modulo.id] = modulo;
    });

    var overlay = document.getElementById('moduloSlideOverOverlay');
    var slideOver = document.getElementById('moduloSlideOver');
    var closeBtn = document.getElementById('moduloSlideOverClose');

    function renderLista(container, empresas, inactiva) {
        container.innerHTML = '';

        if (empresas.length === 0) {
            var empty = document.createElement('p');
            empty.className = 'modulo-empresa-list__empty';
            empty.textContent = inactiva ? 'Todas las empresas lo tienen activo.' : 'Ninguna empresa lo tiene activo todavía.';
            container.appendChild(empty);
            return;
        }

        empresas.forEach(function (empresa) {
            var row = document.createElement('div');
            row.className = 'modulo-empresa-row' + (inactiva ? ' modulo-empresa-row--inactiva' : '');
            row.textContent = empresa.nombre;
            container.appendChild(row);
        });
    }

    function openModulo(id) {
        var modulo = modulosById[id];
        if (!modulo) {
            return;
        }

        document.getElementById('moduloSlideOverNombre').textContent = modulo.nombre;
        document.getElementById('moduloSlideOverPrecio').innerHTML = '$' + formatNumber(modulo.precio, 0) + '<small>/mes</small>';
        document.getElementById('moduloSlideOverActivas').textContent = modulo.activas + '/' + modulo.total + ' · ' + modulo.pct + '%';
        document.getElementById('moduloSlideOverIngreso').textContent = '$' + formatNumber(modulo.ingreso, 0);

        var activas = modulo.empresas.filter(function (e) { return e.activo; });
        var inactivas = modulo.empresas.filter(function (e) { return !e.activo; });

        renderLista(document.getElementById('moduloSlideOverActivasList'), activas, false);
        renderLista(document.getElementById('moduloSlideOverInactivasList'), inactivas, true);

        slideOver.classList.add('is-open');
        slideOver.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-visible');
    }

    function closeSlideOver() {
        slideOver.classList.remove('is-open');
        slideOver.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-visible');
    }

    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            openModulo(card.getAttribute('data-modulo-id'));
        });
    });

    closeBtn.addEventListener('click', closeSlideOver);
    overlay.addEventListener('click', closeSlideOver);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && slideOver.classList.contains('is-open')) {
            closeSlideOver();
        }
    });
}
