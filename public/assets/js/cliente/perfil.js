/**
 * Stockly — Panel del negocio cliente: vista Mi perfil (vanilla JS)
 *
 * A diferencia del perfil de Super Admin, acá el logo SÍ se sube de
 * verdad -apenas se elige un archivo, se manda solo (el propio <label>
 * ya hace de botón, no hace falta un botón "Guardar" aparte para esto).
 * Los formularios de info personal y contraseña van a
 * Cliente\ProfileController; acá solo queda la validación rápida de la
 * confirmación de contraseña antes de mandar.
 */

document.addEventListener('DOMContentLoaded', function () {
    initPerfilPage();
});

function initPerfilPage() {
    var logoInput = document.getElementById('perfilLogoInput');
    var logoForm = document.getElementById('perfilLogoForm');

    if (logoInput && logoForm) {
        logoInput.addEventListener('change', function () {
            if (logoInput.files.length > 0) {
                logoForm.submit();
            }
        });
    }

    var passwordForm = document.getElementById('perfilPasswordForm');
    if (!passwordForm) {
        return;
    }

    var claveNueva = document.getElementById('perfilClaveNueva');
    var claveConfirmar = document.getElementById('perfilClaveConfirmar');

    passwordForm.addEventListener('submit', function (event) {
        if (claveNueva.value !== claveConfirmar.value) {
            event.preventDefault();
            claveConfirmar.style.borderColor = 'var(--color-error)';
            return;
        }

        claveConfirmar.style.borderColor = '';
    });

    /* ---------- Tarjeta de seguridad: fortaleza + checklist en vivo ----------
       Solo "mínimo 8 caracteres" es un requisito real (ver
       Rules\Password::defaults() en ProfileController) -la fortaleza es una
       guía informativa aparte, no bloquea el envío. */
    var strengthWrap = document.querySelector('.perfil-strength');
    var strengthLabel = document.getElementById('perfilStrengthLabel');
    var checkLength = document.getElementById('perfilCheckLength');
    var checkMatch = document.getElementById('perfilCheckMatch');

    function calcularFortaleza(valor) {
        if (!valor) {
            return 0;
        }

        var puntos = 0;
        if (valor.length >= 8) puntos++;
        if (valor.length >= 12) puntos++;
        if (/[a-z]/.test(valor) && /[A-Z]/.test(valor)) puntos++;
        if (/[0-9]/.test(valor)) puntos++;
        if (/[^a-zA-Z0-9]/.test(valor)) puntos++;

        return puntos;
    }

    function actualizarSeguridad() {
        if (!strengthWrap) {
            return;
        }

        var valor = claveNueva.value;
        var puntos = calcularFortaleza(valor);

        if (!valor) {
            strengthWrap.removeAttribute('data-level');
            strengthLabel.textContent = 'Sin escribir';
        } else if (puntos <= 1) {
            strengthWrap.setAttribute('data-level', 'debil');
            strengthLabel.textContent = 'Débil';
        } else if (puntos <= 3) {
            strengthWrap.setAttribute('data-level', 'media');
            strengthLabel.textContent = 'Media';
        } else {
            strengthWrap.setAttribute('data-level', 'fuerte');
            strengthLabel.textContent = 'Fuerte';
        }

        checkLength.classList.toggle('is-met', valor.length >= 8);
        checkMatch.classList.toggle('is-met', valor.length > 0 && valor === claveConfirmar.value);
    }

    claveNueva.addEventListener('input', actualizarSeguridad);
    claveConfirmar.addEventListener('input', actualizarSeguridad);

    /* ---------- Mostrar/ocultar contraseña ---------- */
    document.querySelectorAll('.cliente-form-toggle-password').forEach(function (toggle) {
        var field = toggle.closest('.cliente-form-field');
        var input = field ? field.querySelector('.cliente-input') : null;
        if (!input) {
            return;
        }

        toggle.addEventListener('click', function () {
            var isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            toggle.setAttribute('aria-pressed', String(!isVisible));
        });
    });
}
