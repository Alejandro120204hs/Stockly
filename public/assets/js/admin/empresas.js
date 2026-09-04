/**
 * Stockly — Panel de Super Admin: vista Empresas (vanilla JS)
 * Depende de admin/layout.js (formatNumber, normalizarTexto).
 *
 * Los datos vienen embebidos en un <script type="application/json"> en la
 * vista -acá se leen para pintar el panel al hacer clic en una fila.
 * Activar/Suspender sí pegan al backend real
 * (App\Http\Controllers\Admin\EmpresaController) y persisten.
 */

document.addEventListener('DOMContentLoaded', function () {
    initEmpresasPanel();
});

function empresasApiRequest(method, url, data) {
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    return fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : ''
        },
        body: data !== undefined ? JSON.stringify(data) : undefined
    }).then(function (response) {
        return response.json().catch(function () { return {}; }).then(function (json) {
            if (!response.ok) {
                throw new Error(json.message || 'Ocurrió un error inesperado.');
            }
            return json;
        });
    });
}

function empresasMostrarError(mensaje) {
    if (typeof Swal === 'undefined') {
        window.alert(mensaje);
        return;
    }
    Swal.fire({
        icon: 'error',
        title: 'No se pudo completar',
        text: mensaje,
        confirmButtonText: 'Entendido',
        customClass: { popup: 'stockly-swal', container: 'stockly-swal-backdrop' }
    });
}

function empresasConfirmar(opciones) {
    if (typeof Swal === 'undefined') {
        return Promise.resolve(window.confirm(opciones.texto || opciones.titulo || ''));
    }
    return Swal.fire({
        icon: opciones.icon || 'warning',
        title: opciones.titulo || '¿Estás seguro?',
        text: opciones.texto || '',
        showCancelButton: true,
        confirmButtonText: opciones.textoConfirmar || 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        focusCancel: !!opciones.peligro,
        customClass: {
            popup: 'stockly-swal',
            container: 'stockly-swal-backdrop',
            confirmButton: opciones.peligro ? 'stockly-swal-confirm--peligro' : ''
        }
    }).then(function (r) { return r.isConfirmed; });
}

function initEmpresasPanel() {
    var table = document.getElementById('empresasTable');
    var dataScript = document.getElementById('empresasData');

    if (!table || !dataScript) {
        return;
    }

    var empresas = JSON.parse(dataScript.textContent);
    var empresasById = {};
    empresas.forEach(function (empresa) {
        empresasById[empresa.id] = empresa;
    });

    var estadoLabels = {
        activo: 'Activo',
        por_vencer: 'Por vencer',
        vencido: 'Vencido',
        suspendido: 'Suspendido'
    };

    var overlay = document.getElementById('slideOverOverlay');
    var slideOver = document.getElementById('empresaSlideOver');
    var closeBtn = document.getElementById('slideOverClose');
    var activarBtn = document.getElementById('slideOverActivar');
    var suspenderBtn = document.getElementById('slideOverSuspender');
    var activarSection = document.getElementById('activarSection');
    var activarHint = document.getElementById('activarHint');
    var planSelect = document.getElementById('activarPlanSelect');
    var montoInput = document.getElementById('activarMontoInput');
    var metodoInput = document.getElementById('activarMetodoInput');
    var moduloFacturacionCheck = document.getElementById('moduloFacturacionCheck');

    formatearInputDinero(montoInput);

    var currentId = null;

    function pillMarkup(estado) {
        return {
            className: 'status-pill status-pill--' + estado.replace('_', '-'),
            label: estadoLabels[estado] || estado
        };
    }

    function aplicarEmpresa(empresa) {
        empresasById[empresa.id] = empresa;

        var row = table.querySelector('tr[data-empresa-id="' + empresa.id + '"]');
        var pill = pillMarkup(empresa.estado);

        if (row) {
            var rowPill = row.querySelector('.status-pill');
            if (rowPill) {
                rowPill.className = pill.className;
                rowPill.textContent = pill.label;
            }
            var vencCell = row.cells[3];
            if (vencCell) {
                vencCell.textContent = empresa.vencimiento;
            }
        }

        if (currentId === empresa.id) {
            document.getElementById('slideOverVencimiento').textContent = empresa.vencimiento;

            var slideOverPill = document.getElementById('slideOverEstado');
            slideOverPill.className = pill.className;
            slideOverPill.textContent = pill.label;

            updateActionButtons(empresa);

            moduloFacturacionCheck.checked = !!empresa.tieneFacturacion;
        }
    }

    function updateActionButtons(empresa) {
        suspenderBtn.disabled = empresa.estado === 'suspendido';

        // Ya está al día -no tiene sentido "activar" una suscripción que
        // no venció ni está por vencer (el cliente ya puede renovar antes
        // de tiempo por su cuenta desde /cliente/suscripcion).
        var yaActiva = empresa.estado === 'activo';
        activarSection.hidden = yaActiva;
        activarHint.hidden = !yaActiva;
    }

    function openEmpresa(id) {
        var empresa = empresasById[id];
        if (!empresa) {
            return;
        }
        currentId = id;

        document.getElementById('slideOverNombre').textContent = empresa.nombre;
        document.getElementById('slideOverCorreo').textContent = empresa.correo || '—';
        document.getElementById('slideOverTelefono').textContent = empresa.telefono || '—';
        document.getElementById('slideOverDireccion').textContent = empresa.direccion || '—';
        document.getElementById('slideOverUbicacion').textContent = [empresa.ciudad, empresa.departamento].filter(Boolean).join(', ') || '—';
        document.getElementById('slideOverNit').textContent = empresa.nit + (empresa.dv ? '-' + empresa.dv : '');
        document.getElementById('slideOverTipoPersona').textContent = empresa.tipoPersona || '—';
        document.getElementById('slideOverRegimen').textContent = empresa.regimen || '—';
        document.getElementById('slideOverVencimiento').textContent = empresa.vencimiento;

        planSelect.value = 'mensual';
        montoInput.value = '';
        metodoInput.value = '';

        var pill = pillMarkup(empresa.estado);
        var slideOverPill = document.getElementById('slideOverEstado');
        slideOverPill.className = pill.className;
        slideOverPill.textContent = pill.label;
        updateActionButtons(empresa);

        moduloFacturacionCheck.checked = !!empresa.tieneFacturacion;

        slideOver.classList.add('is-open');
        slideOver.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-visible');
    }

    function closeSlideOver() {
        slideOver.classList.remove('is-open');
        slideOver.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-visible');
        currentId = null;
    }

    table.querySelectorAll('.data-table__row').forEach(function (row) {
        var id = parseInt(row.getAttribute('data-empresa-id'), 10);

        row.addEventListener('click', function () {
            openEmpresa(id);
        });

        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openEmpresa(id);
            }
        });
    });

    closeBtn.addEventListener('click', closeSlideOver);
    overlay.addEventListener('click', closeSlideOver);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && slideOver.classList.contains('is-open')) {
            closeSlideOver();
        }
    });

    activarBtn.addEventListener('click', function () {
        if (!currentId) return;

        var originalText = activarBtn.textContent;
        activarBtn.disabled = true;
        suspenderBtn.disabled = true;
        activarBtn.textContent = 'Activando...';

        var montoValor = valorDineroInput(montoInput);

        empresasApiRequest('POST', '/admin/empresas/' + currentId + '/activar', {
            plan: planSelect.value,
            monto: montoValor > 0 ? montoValor : null,
            metodo: metodoInput.value.trim() || null
        })
            .then(function (json) {
                aplicarEmpresa(json.empresa);
            })
            .catch(function (error) {
                empresasMostrarError(error.message);
            })
            .finally(function () {
                activarBtn.disabled = false;
                activarBtn.textContent = originalText;
                updateActionButtons(empresasById[currentId]);
            });
    });

    suspenderBtn.addEventListener('click', function () {
        if (!currentId || suspenderBtn.disabled) return;

        empresasConfirmar({
            titulo: '¿Suspender esta empresa?',
            texto: 'Sus usuarios no van a poder ingresar hasta que la vuelvas a activar. Los días que le quedaban pagados no se pierden.',
            textoConfirmar: 'Sí, suspender',
            peligro: true
        }).then(function (confirmado) {
            if (!confirmado) return;

            var originalText = suspenderBtn.textContent;
            suspenderBtn.disabled = true;
            activarBtn.disabled = true;
            suspenderBtn.textContent = 'Suspendiendo...';

            empresasApiRequest('POST', '/admin/empresas/' + currentId + '/suspender')
                .then(function (json) {
                    aplicarEmpresa(json.empresa);
                })
                .catch(function (error) {
                    empresasMostrarError(error.message);
                })
                .finally(function () {
                    activarBtn.disabled = false;
                    suspenderBtn.textContent = originalText;
                    updateActionButtons(empresasById[currentId]);
                });
        });
    });

    /* ---------- Módulos: Facturación electrónica ----------
     * Único interruptor real -Nómina va siempre incluida en Administración
     * para todas las empresas, con o sin Factus. */
    function guardarModulos() {
        if (!currentId) return;

        empresasApiRequest('PATCH', '/admin/empresas/' + currentId + '/modulos', {
            tiene_facturacion: moduloFacturacionCheck.checked
        })
            .then(function (json) {
                // No hay columna de módulos en la tabla -solo se
                // actualiza el estado en memoria, el checkbox ya refleja
                // lo guardado porque es lo que el usuario acaba de marcar.
                empresasById[json.empresa.id] = json.empresa;
            })
            .catch(function (error) {
                empresasMostrarError(error.message);
                // Si falló, revertir el checkbox a lo que ya estaba guardado.
                var empresa = empresasById[currentId];
                moduloFacturacionCheck.checked = !!empresa.tieneFacturacion;
            });
    }

    moduloFacturacionCheck.addEventListener('change', guardarModulos);

    /* ---------- Búsqueda + filtro por estado ---------- */
    var searchInput = document.getElementById('empresasSearch');
    var estadoFilter = document.getElementById('empresasEstadoFilter');
    var emptyState = document.getElementById('empresasEmpty');

    function applyFilters() {
        var term = normalizarTexto(searchInput.value.trim());
        var estado = estadoFilter.value;
        var visibleCount = 0;

        table.querySelectorAll('.data-table__row').forEach(function (row) {
            var id = parseInt(row.getAttribute('data-empresa-id'), 10);
            var empresa = empresasById[id];
            var matchesTerm = !term
                || normalizarTexto(empresa.nombre).indexOf(term) !== -1
                || (empresa.nit && empresa.nit.indexOf(term) !== -1);
            var matchesEstado = !estado || empresa.estado === estado;
            var visible = matchesTerm && matchesEstado;

            row.hidden = !visible;
            if (visible) {
                visibleCount++;
            }
        });

        emptyState.hidden = visibleCount !== 0;
    }

    searchInput.addEventListener('input', applyFilters);
    estadoFilter.addEventListener('change', applyFilters);
}
