/**
 * Stockly — Panel de Super Admin: vista Mi perfil (vanilla JS)
 *
 * La foto sí se previsualiza de verdad (FileReader, queda en memoria del
 * navegador) -pero no se sube a ningún lado porque no hay backend para
 * eso todavía. Los formularios de info personal y contraseña en sí ya
 * son reales (van a Admin\ProfileController); acá solo queda una
 * validación rápida de la confirmación de contraseña antes de mandar.
 */

document.addEventListener('DOMContentLoaded', function () {
    initPerfilPage();
});

function initPerfilPage() {
    var photoBtn = document.getElementById('profilePhotoBtn');
    if (!photoBtn) {
        return;
    }

    var photoInput = document.getElementById('profilePhotoInput');
    var avatar = document.getElementById('profileAvatar');

    photoBtn.addEventListener('click', function () {
        photoInput.click();
    });

    photoInput.addEventListener('change', function () {
        var file = photoInput.files && photoInput.files[0];
        if (!file) {
            return;
        }

        var reader = new FileReader();
        reader.onload = function (event) {
            avatar.style.backgroundImage = 'url(' + event.target.result + ')';
            avatar.textContent = '';
        };
        reader.readAsDataURL(file);
    });

    var passwordForm = document.getElementById('profilePasswordForm');
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
    document.querySelectorAll('.admin-form-toggle-password').forEach(function (toggle) {
        var field = toggle.closest('.admin-form-field');
        var input = field ? field.querySelector('.admin-form-input') : null;
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
