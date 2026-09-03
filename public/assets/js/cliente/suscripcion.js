/**
 * Stockly — Suscripción (rol Cliente)
 *   1. Nombre del archivo elegido junto al botón de subir comprobante (el
 *      input real va oculto, el label estilizado lo dispara -mismo patrón
 *      que perfil.js con el logo, pero acá no se auto-envía: falta elegir
 *      el plan primero).
 *   2. Copiar el número de Nequi / la llave con un clic -evita que alguien
 *      tenga que transcribirlo a mano desde el celular.
 */
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('suscripcionComprobante');
    var nombre = document.getElementById('suscripcionComprobanteNombre');

    if (input && nombre) {
        input.addEventListener('change', function () {
            nombre.textContent = input.files.length > 0 ? input.files[0].name : 'Ningún archivo elegido';
        });
    }

    /**
     * document.execCommand primero -es síncrono y funciona en más casos
     * (no depende de permisos del navegador ni de que el documento tenga
     * foco); navigator.clipboard.writeText como respaldo si no existe.
     */
    function copiarTexto(valor) {
        var textarea = document.createElement('textarea');
        textarea.value = valor;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();

        var copiado = false;
        try {
            copiado = document.execCommand('copy');
        } catch (e) {
            copiado = false;
        }
        document.body.removeChild(textarea);

        if (copiado) {
            return Promise.resolve();
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(valor);
        }
        return Promise.reject(new Error('No se pudo copiar'));
    }

    document.querySelectorAll('.pago-metodo-card__copy').forEach(function (btn) {
        var iconoOriginal = btn.innerHTML;

        btn.addEventListener('click', function () {
            copiarTexto(btn.getAttribute('data-copy')).then(function () {
                btn.classList.add('is-copied');
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';

                window.setTimeout(function () {
                    btn.classList.remove('is-copied');
                    btn.innerHTML = iconoOriginal;
                }, 1500);
            }).catch(function () {
                // Sin retroalimentación visual si de plano no se pudo -el
                // valor sigue visible y seleccionable a mano en la tarjeta.
            });
        });
    });
});
