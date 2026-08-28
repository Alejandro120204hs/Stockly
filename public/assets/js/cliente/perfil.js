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
    var claveMismatch = document.getElementById('perfilClaveMismatch');

    passwordForm.addEventListener('submit', function (event) {
        if (claveNueva.value !== claveConfirmar.value) {
            event.preventDefault();
            claveMismatch.hidden = false;
            claveConfirmar.style.borderColor = 'var(--color-error)';
            return;
        }

        claveMismatch.hidden = true;
        claveConfirmar.style.borderColor = '';
    });

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
