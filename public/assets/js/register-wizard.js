/**
 * Stockly — Wizard de registro (3 pasos)
 *
 * Este archivo es específico de la vista de registro (no lo usan login ni
 * "olvidé mi contraseña"); reutiliza los estilos de auth.css y el toggle
 * de contraseña de auth.js, que se cargan aparte.
 *
 * Módulos:
 *   1. Navegación entre pasos (Siguiente / Atrás / click en un paso ya
 *      completado) con transición de salida-entrada.
 *   2. Validación por paso: no se avanza si el paso actual tiene campos
 *      obligatorios vacíos, inválidos, o contraseñas que no coinciden.
 *   3. Campo "Otro" del tipo de negocio: aparece/desaparece según el select.
 *   4. Sincronización de Nombres+Apellidos hacia el campo "name" real que
 *      espera el backend actual (que todavía no tiene columnas separadas).
 *   5. Resumen del paso 3, armado leyendo los valores ya escritos en los
 *      pasos 1 y 2 (nunca se vuelve a pedir nada, y la contraseña no se
 *      muestra).
 *   6. Departamento -> Ciudad/Municipio: selects dependientes, llenados
 *      desde window.COLOMBIA_LOCATIONS (colombia-locations.js).
 */

document.addEventListener('DOMContentLoaded', function () {
    initRegisterWizard();
});

function initRegisterWizard() {
    var wizard = document.querySelector('.wizard');
    if (!wizard) {
        return;
    }

    var form = wizard.querySelector('form');
    var steps = Array.prototype.slice.call(wizard.querySelectorAll('.wizard-step'));
    var progressSteps = Array.prototype.slice.call(wizard.querySelectorAll('.wizard-progress__step'));
    var connectors = Array.prototype.slice.call(wizard.querySelectorAll('.wizard-progress__connector'));

    var currentIndex = 0;
    var isAnimating = false;
    var TRANSITION_MS = 220; // debe coincidir con la duración en auth.css

    /* ------------------------------------------------------------------
     * 1. Navegación entre pasos
     * ------------------------------------------------------------------ */

    wizard.querySelectorAll('[data-wizard-next]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!validateStep(currentIndex)) {
                return;
            }

            // Al pasar del paso 2 al 3, se arma el resumen con lo ya escrito
            if (currentIndex === 1) {
                populateSummary();
            }

            goToStep(currentIndex + 1, 'forward');
        });
    });

    wizard.querySelectorAll('[data-wizard-back]').forEach(function (button) {
        button.addEventListener('click', function () {
            goToStep(currentIndex - 1, 'backward');
        });
    });

    // Un paso ya completado se puede clickear para volver a revisarlo;
    // no se puede saltar hacia adelante sin pasar por "Siguiente".
    progressSteps.forEach(function (stepEl, index) {
        stepEl.addEventListener('click', function () {
            if (index < currentIndex) {
                goToStep(index, 'backward');
            }
        });
    });

    // Enter dentro de un campo de un paso intermedio avanza en vez de
    // enviar el formulario completo (que solo debe pasar en el paso final).
    form.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter') {
            return;
        }
        if (currentIndex < steps.length - 1) {
            event.preventDefault();
            var nextButton = steps[currentIndex].querySelector('[data-wizard-next]');
            if (nextButton) {
                nextButton.click();
            }
        }
    });

    // Envío final: revalida el último paso (checkbox de términos incluido)
    // como red de seguridad, por si el usuario llegó ahí sin usar "Siguiente".
    form.addEventListener('submit', function (event) {
        syncFullName();
        if (!validateStep(steps.length - 1)) {
            event.preventDefault();
        }
    });

    /**
     * Anima la salida del paso actual y la entrada del paso destino.
     * direction: 'forward' | 'backward', decide de qué lado entra/sale cada uno.
     */
    function goToStep(targetIndex, direction) {
        if (isAnimating || targetIndex < 0 || targetIndex >= steps.length || targetIndex === currentIndex) {
            return;
        }

        isAnimating = true;

        var outgoing = steps[currentIndex];
        var incoming = steps[targetIndex];
        var exitClass = direction === 'forward' ? 'wizard-step--exit-forward' : 'wizard-step--exit-backward';
        var enterClass = direction === 'forward' ? 'wizard-step--enter-forward' : 'wizard-step--enter-backward';

        outgoing.classList.add(exitClass);

        window.setTimeout(function () {
            outgoing.classList.remove('is-active', exitClass);
            outgoing.setAttribute('hidden', '');

            incoming.removeAttribute('hidden');
            incoming.classList.add(enterClass);

            // Fuerza al navegador a "asentar" ese estado inicial antes de
            // quitarlo; si no, no habría nada desde donde transicionar.
            void incoming.offsetWidth;

            incoming.classList.add('is-active');
            incoming.classList.remove(enterClass);

            currentIndex = targetIndex;
            updateProgress();
            isAnimating = false;

            var firstField = incoming.querySelector('input, select');
            if (firstField) {
                firstField.focus({ preventScroll: true });
            }
        }, TRANSITION_MS);
    }

    function updateProgress() {
        progressSteps.forEach(function (stepEl, index) {
            stepEl.classList.toggle('is-active', index === currentIndex);
            stepEl.classList.toggle('is-complete', index < currentIndex);
        });

        connectors.forEach(function (connector, index) {
            connector.classList.toggle('is-filled', index < currentIndex);
        });
    }

    /* ------------------------------------------------------------------
     * 2. Validación por paso
     * ------------------------------------------------------------------ */

    function validateStep(index) {
        var step = steps[index];
        var fields = Array.prototype.slice.call(step.querySelectorAll('.form-input[required], .form-select[required]'));
        var firstInvalid = null;

        // Limpiar cualquier "Las contraseñas no coinciden" de un intento
        // anterior ANTES de revisar validez -si no, checkValidity() del
        // campo sigue viendo ese mensaje viejo como error aunque el
        // usuario ya haya corregido la contraseña para que coincida.
        var staleConfirmation = step.querySelector('#password_confirmation');
        if (staleConfirmation) {
            staleConfirmation.setCustomValidity('');
        }

        fields.forEach(function (field) {
            var isValid = field.checkValidity();
            setFieldError(field, !isValid);
            if (!isValid && !firstInvalid) {
                firstInvalid = field;
            }
        });

        // Paso 1: además de lo anterior, la confirmación debe coincidir
        if (!firstInvalid) {
            var password = step.querySelector('#password');
            var confirmation = step.querySelector('#password_confirmation');

            if (password && confirmation) {
                var matches = password.value === confirmation.value;
                confirmation.setCustomValidity(matches ? '' : 'Las contraseñas no coinciden');
                setFieldError(confirmation, !matches);
                if (!matches) {
                    firstInvalid = confirmation;
                }
            }
        }

        // Paso 3: el checkbox de términos es obligatorio para poder enviar
        if (!firstInvalid) {
            var terms = step.querySelector('#terms');
            if (terms) {
                var termsWrap = terms.closest('.wizard-terms');
                var accepted = terms.checked;
                if (termsWrap) {
                    termsWrap.classList.toggle('has-error', !accepted);
                }
                if (!accepted) {
                    firstInvalid = terms;
                }
            }
        }

        if (firstInvalid) {
            shake(firstInvalid);
            if (typeof firstInvalid.reportValidity === 'function') {
                firstInvalid.reportValidity();
            } else {
                firstInvalid.focus();
            }
            return false;
        }

        return true;
    }

    function setFieldError(field, hasError) {
        var wrap = field.closest('.form-field');
        if (wrap) {
            wrap.classList.toggle('has-error', hasError);
        }
    }

    /** Reinicia y vuelve a disparar la animación de sacudida en un campo. */
    function shake(field) {
        var wrap = field.closest('.form-field') || field.closest('.wizard-terms');
        if (!wrap) {
            return;
        }
        wrap.classList.remove('is-shaking');
        void wrap.offsetWidth; // fuerza reflow para poder repetir la animación
        wrap.classList.add('is-shaking');
    }

    /* ------------------------------------------------------------------
     * 3. Campo "Otro" del tipo de negocio
     * ------------------------------------------------------------------ */

    var businessType = wizard.querySelector('#business_type');
    var businessTypeOther = wizard.querySelector('#business_type_other');

    if (businessType && businessTypeOther) {
        var otherField = businessTypeOther.closest('.form-field');

        businessType.addEventListener('change', function () {
            var isOther = businessType.value === 'Otro';
            otherField.style.display = isOther ? '' : 'none';
            businessTypeOther.required = isOther;
            if (!isOther) {
                businessTypeOther.value = '';
                setFieldError(businessTypeOther, false);
            }
        });
    }

    /* ------------------------------------------------------------------
     * 4. Nombres y apellidos van al backend como campos separados
     *    (first_name / last_name), así que acá solo se leen para el
     *    resumen del paso 3 -no hace falta combinarlos.
     * ------------------------------------------------------------------ */

    var firstNameInput = wizard.querySelector('#first_name');
    var lastNameInput = wizard.querySelector('#last_name');

    /* ------------------------------------------------------------------
     * 5. Resumen del paso 3
     * ------------------------------------------------------------------ */

    function populateSummary() {
        setSummaryText('summary-full-name', [
            firstNameInput ? firstNameInput.value : '',
            lastNameInput ? lastNameInput.value : ''
        ].filter(Boolean).join(' '));

        setSummaryText('summary-email', fieldValue('#email'));
        setSummaryText('summary-phone', fieldValue('#phone'));

        var typeValue = fieldValue('#business_type');
        if (typeValue === 'Otro') {
            typeValue = fieldValue('#business_type_other') || 'Otro';
        }
        setSummaryText('summary-business-type', typeValue);

        setSummaryText('summary-company', fieldValue('#company_name'));
        setSummaryText('summary-nit', fieldValue('#nit'));
        setSummaryText('summary-location', [fieldValue('#city'), fieldValue('#department')]
            .filter(Boolean)
            .join(', '));
    }

    function fieldValue(selector) {
        var field = wizard.querySelector(selector);
        return field ? field.value.trim() : '';
    }

    function setSummaryText(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.textContent = value || '—';
        }
    }

    /* ------------------------------------------------------------------
     * 6. Departamento -> Ciudad/Municipio (selects dependientes)
     *
     * window.COLOMBIA_LOCATIONS (colombia-locations.js) trae la lista real
     * de departamentos y sus municipios. El select de ciudad empieza
     * deshabilitado y solo se llena/habilita una vez elegido el departamento,
     * para no dejar elegir una ciudad "suelta" que no le corresponda.
     * ------------------------------------------------------------------ */

    var departmentSelect = wizard.querySelector('#department');
    var citySelect = wizard.querySelector('#city');
    var locationsRow = wizard.querySelector('.form-row[data-old-department]');
    var locations = window.COLOMBIA_LOCATIONS || [];

    if (departmentSelect && citySelect && locations.length) {
        var oldDepartment = locationsRow ? locationsRow.getAttribute('data-old-department') : '';
        var oldCity = locationsRow ? locationsRow.getAttribute('data-old-city') : '';

        // Llena el select de departamentos (ordenados alfabéticamente,
        // que es como ya viene el dataset)
        locations.forEach(function (loc) {
            var option = document.createElement('option');
            option.value = loc.departamento;
            option.textContent = loc.departamento;
            departmentSelect.appendChild(option);
        });

        departmentSelect.addEventListener('change', function () {
            fillCities(departmentSelect.value, '');
        });

        function fillCities(departmentName, preselectCity) {
            var match = locations.filter(function (loc) {
                return loc.departamento === departmentName;
            })[0];

            citySelect.innerHTML = '';

            if (!match) {
                var placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.disabled = true;
                placeholder.selected = true;
                placeholder.textContent = 'Elige un departamento';
                citySelect.appendChild(placeholder);
                citySelect.disabled = true;
                return;
            }

            citySelect.disabled = false;

            var choose = document.createElement('option');
            choose.value = '';
            choose.disabled = true;
            choose.textContent = 'Selecciona';
            citySelect.appendChild(choose);

            var hasPreselect = false;
            match.ciudades.forEach(function (cityName) {
                var option = document.createElement('option');
                option.value = cityName;
                option.textContent = cityName;
                if (cityName === preselectCity) {
                    option.selected = true;
                    hasPreselect = true;
                }
                citySelect.appendChild(option);
            });

            choose.selected = !hasPreselect;
        }

        // Si la página volvió a cargar con datos previos (old()), se
        // reconstruye la selección de departamento + ciudad automáticamente.
        if (oldDepartment) {
            departmentSelect.value = oldDepartment;
            fillCities(oldDepartment, oldCity);
        }
    }

    // Estado inicial del indicador de progreso
    updateProgress();
}
