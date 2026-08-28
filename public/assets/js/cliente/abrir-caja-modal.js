/**
 * Stockly — Dashboard: acceso rápido "Abrir caja"
 * Antes esto era pura simulación (cambiaba la stat card a "Abierta" con un
 * setTimeout y una base fija de $150.000, sin backend real). Ahora llama a
 * App\Http\Controllers\Cliente\CajaController::abrir de verdad.
 *
 * Si la caja YA está abierta (data-caja-abierta="1" en el botón, calculado
 * server-side), el botón no tiene nada que abrir -en vez de eso lleva
 * directo a /cliente/caja. Depende de cliente/layout.js (formatCOP,
 * formatearInputDinero, valorDineroInput, mostrarError) ya cargado antes.
 */

document.addEventListener('DOMContentLoaded', function () {
    initAbrirCajaAction();
});

function initAbrirCajaAction() {
    var actionBtn = document.getElementById('abrirCajaAction');
    var modal = document.getElementById('abrirCajaModal');
    var overlay = document.getElementById('abrirCajaOverlay');
    if (!actionBtn || !modal || !overlay) {
        return;
    }

    var closeBtn = document.getElementById('abrirCajaClose');
    var baseInput = document.getElementById('dashboardCajaBaseInicial');
    var confirmarBtn = document.getElementById('dashboardAbrirCajaConfirmarBtn');
    var cajaYaAbierta = actionBtn.getAttribute('data-caja-abierta') === '1';

    formatearInputDinero(baseInput);

    function openModal() {
        baseInput.value = '';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-visible');
        window.setTimeout(function () { baseInput.focus(); }, 250);
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-visible');
    }

    actionBtn.addEventListener('click', function () {
        if (cajaYaAbierta) {
            window.location.href = '/cliente/caja';
            return;
        }
        openModal();
    });

    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    confirmarBtn.addEventListener('click', function () {
        var base = valorDineroInput(baseInput);
        if (!base) {
            baseInput.focus();
            return;
        }

        var originalText = confirmarBtn.textContent;
        confirmarBtn.disabled = true;
        confirmarBtn.textContent = 'Abriendo...';

        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        fetch('/cliente/caja/abrir', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : ''
            },
            body: JSON.stringify({ base_inicial: base })
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (json) {
                if (!response.ok) {
                    throw new Error(json.message || 'Ocurrió un error inesperado.');
                }
                return json;
            });
        }).then(function (json) {
            cajaYaAbierta = true;
            actionBtn.setAttribute('data-caja-abierta', '1');

            var label = actionBtn.querySelector('.quick-action__label');
            var hint = actionBtn.querySelector('.quick-action__hint');
            if (label) {
                label.textContent = 'Caja abierta';
            }
            if (hint) {
                hint.textContent = 'Ir a Caja';
            }

            var estadoValor = document.getElementById('cajaEstadoValor');
            var estadoMeta = document.getElementById('cajaEstadoMeta');
            var estadoCard = document.getElementById('cajaEstadoCard');
            if (estadoValor) {
                estadoValor.textContent = 'Abierta';
            }
            if (estadoMeta) {
                estadoMeta.textContent = 'Base ' + formatCOP(json.caja.baseInicial) + ' · ' + json.caja.horaApertura;
            }
            if (estadoCard) {
                estadoCard.classList.remove('stat-card--mist');
                estadoCard.classList.add('stat-card--sage');
            }

            closeModal();
        }).catch(function (error) {
            mostrarError(error.message);
        }).finally(function () {
            confirmarBtn.disabled = false;
            confirmarBtn.textContent = originalText;
        });
    });
}
