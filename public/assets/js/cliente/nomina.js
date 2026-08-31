/**
 * Stockly — Panel del negocio cliente: vista Nómina (vanilla JS)
 * Depende de cliente/layout.js (formatCOP, formatNumber, normalizarTexto,
 * confirmarAccion, mostrarError) ya cargado antes que este.
 *
 * Módulos:
 *   1. initCountUp        -> anima los números de las stat cards
 *   2. initTabs            -> pestañas Empleados / Nómina electrónica
 *   3. initEmpleados       -> tabla + slide-over + modal Nuevo/Editar empleado
 *   4. initDocumentos      -> tabla + slide-over de documentos de nómina
 *   5. initPagarNominaModal -> modal para pagarle a varios empleados a la vez
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    initCountUp();
    cargarNominaData();
    initTabs();
    initEmpleadosTable();
    initEmpleadoModal();
    initDocumentosTable();
    initPagarNominaModal();
});

/* --------------------------------------------------------------------
 * 1. Contador animado en las stat cards
 * ------------------------------------------------------------------ */
function initCountUp() {
    document.querySelectorAll('[data-count]').forEach(function (el) {
        var target = parseFloat(el.getAttribute('data-count')) || 0;
        var isMoney = el.getAttribute('data-format') === 'money';
        var duration = 1100;
        var start = null;

        function frame(timestamp) {
            if (start === null) start = timestamp;
            var progress = Math.min(1, (timestamp - start) / duration);
            var eased = 1 - Math.pow(1 - progress, 5);
            var value = Math.round(target * eased);
            el.textContent = isMoney ? formatCOP(value) : String(value);
            if (progress < 1) window.requestAnimationFrame(frame);
        }
        window.requestAnimationFrame(frame);
    });
}

/* --------------------------------------------------------------------
 * Estado compartido + helper de fetch (mismo patrón que proveedores.js)
 * ------------------------------------------------------------------ */
var empleadosById = {};
var nominaDocsById = {};

function cargarNominaData() {
    var empEl = document.getElementById('empleadosData');
    if (empEl) {
        JSON.parse(empEl.textContent).forEach(function (e) { empleadosById[e.id] = e; });
    }
    var docEl = document.getElementById('nominaDocumentosData');
    if (docEl) {
        JSON.parse(docEl.textContent).forEach(function (d) { nominaDocsById[d.id] = d; });
    }
}

function nominaApiRequest(method, url, data) {
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

/* --------------------------------------------------------------------
 * 2. Pestañas Empleados / Nómina electrónica
 * ------------------------------------------------------------------ */
function initTabs() {
    var btnEmpleados = document.getElementById('tabBtnEmpleados');
    var btnDocumentos = document.getElementById('tabBtnDocumentos');
    var panelEmpleados = document.getElementById('tabPanelEmpleados');
    var panelDocumentos = document.getElementById('tabPanelDocumentos');
    if (!btnEmpleados || !btnDocumentos || !panelEmpleados || !panelDocumentos) return;

    function activar(destino) {
        var esEmpleados = destino === 'empleados';
        btnEmpleados.classList.toggle('is-active', esEmpleados);
        btnDocumentos.classList.toggle('is-active', !esEmpleados);
        btnEmpleados.setAttribute('aria-selected', esEmpleados ? 'true' : 'false');
        btnDocumentos.setAttribute('aria-selected', esEmpleados ? 'false' : 'true');
        panelEmpleados.hidden = !esEmpleados;
        panelDocumentos.hidden = esEmpleados;
    }

    btnEmpleados.addEventListener('click', function () { activar('empleados'); });
    btnDocumentos.addEventListener('click', function () { activar('documentos'); });
}

/* --------------------------------------------------------------------
 * 3. Tabla de empleados + paginación + slide-over
 * ------------------------------------------------------------------ */
var EMPLEADOS_PAGE_SIZE = 4;
var empleadosPaginaActual = 1;

function initEmpleadosTable() {
    var table = document.getElementById('empleadosTable');
    if (!table) return;

    wireFilasEmpleado(table);

    var prevBtn = document.getElementById('empleadosPrevPage');
    var nextBtn = document.getElementById('empleadosNextPage');

    prevBtn?.addEventListener('click', function () {
        if (empleadosPaginaActual > 1) {
            empleadosPaginaActual--;
            renderEmpleadosPagina();
        }
    });
    nextBtn?.addEventListener('click', function () {
        empleadosPaginaActual++;
        renderEmpleadosPagina();
    });

    renderEmpleadosPagina();
}

/**
 * Igual que proveedores.js: se vuelve a consultar el DOM en cada render
 * (no una sola vez) porque agregar un empleado nuevo mete una fila más
 * después -si no, esa fila quedaría fuera de la paginación.
 */
function renderEmpleadosPagina() {
    var table = document.getElementById('empleadosTable');
    var emptyState = document.getElementById('empleadosEmpty');
    var paginationEl = document.getElementById('empleadosPagination');
    var pageInfoEl = document.getElementById('empleadosPageInfo');
    var prevBtn = document.getElementById('empleadosPrevPage');
    var nextBtn = document.getElementById('empleadosNextPage');
    if (!table) return;

    var filas = Array.prototype.slice.call(table.querySelectorAll('.data-table__row'));
    var totalPaginas = Math.max(1, Math.ceil(filas.length / EMPLEADOS_PAGE_SIZE));
    empleadosPaginaActual = Math.min(empleadosPaginaActual, totalPaginas);

    var start = (empleadosPaginaActual - 1) * EMPLEADOS_PAGE_SIZE;
    var filasPagina = filas.slice(start, start + EMPLEADOS_PAGE_SIZE);

    filas.forEach(function (fila) {
        fila.hidden = filasPagina.indexOf(fila) === -1;
    });

    if (emptyState) emptyState.hidden = filas.length !== 0;
    if (paginationEl) paginationEl.hidden = filas.length === 0;
    if (pageInfoEl) pageInfoEl.textContent = 'Página ' + empleadosPaginaActual + ' de ' + totalPaginas;
    if (prevBtn) prevBtn.disabled = empleadosPaginaActual <= 1;
    if (nextBtn) nextBtn.disabled = empleadosPaginaActual >= totalPaginas;
}

function wireFilasEmpleado(table) {
    table.querySelectorAll('.data-table__row').forEach(function (row) {
        var id = parseInt(row.getAttribute('data-empleado-id'), 10);
        row.addEventListener('click', function () { abrirEmpleadoSlideOver(id); });
        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                abrirEmpleadoSlideOver(id);
            }
        });
    });
}

function abrirEmpleadoSlideOver(id) {
    var empleado = empleadosById[id];
    var overlay = document.getElementById('empleadoSlideOverOverlay');
    var slideOver = document.getElementById('empleadoSlideOver');
    if (!empleado || !overlay || !slideOver) return;

    document.getElementById('empleadoSlideOverNombre').textContent = empleado.nombreCompleto;

    var estadoEl = document.getElementById('empleadoSlideOverEstado');
    estadoEl.textContent = empleado.activo ? 'Activo' : 'Retirado';
    estadoEl.className = 'status-pill ' + (empleado.activo ? 'status-pill--pagada' : 'status-pill--error');

    document.getElementById('empleadoSlideOverDocumento').textContent = empleado.tipoDocumento + ' ' + empleado.numeroDocumento;
    document.getElementById('empleadoSlideOverCargo').textContent = empleado.cargo || '—';
    document.getElementById('empleadoSlideOverSalario').textContent = empleado.salario !== null ? formatCOP(empleado.salario) : '—';
    document.getElementById('empleadoSlideOverRetiro').textContent = empleado.fechaRetiro || '—';

    document.getElementById('empleadoSlideOverEditarBtn').onclick = function () {
        cerrarEmpleadoSlideOver();
        window.abrirEditarEmpleado(id);
    };
    document.getElementById('empleadoSlideOverEliminarBtn').onclick = function () { eliminarEmpleado(id); };

    slideOver.classList.add('is-open');
    slideOver.setAttribute('aria-hidden', 'false');
    overlay.classList.add('is-visible');
}

function cerrarEmpleadoSlideOver() {
    var overlay = document.getElementById('empleadoSlideOverOverlay');
    var slideOver = document.getElementById('empleadoSlideOver');
    slideOver.classList.remove('is-open');
    slideOver.setAttribute('aria-hidden', 'true');
    overlay.classList.remove('is-visible');
}

(function wireEmpleadoSlideOverClose() {
    var overlay = document.getElementById('empleadoSlideOverOverlay');
    var slideOver = document.getElementById('empleadoSlideOver');
    var closeBtn = document.getElementById('empleadoSlideOverClose');
    if (!overlay || !slideOver || !closeBtn) return;
    closeBtn.addEventListener('click', cerrarEmpleadoSlideOver);
    overlay.addEventListener('click', cerrarEmpleadoSlideOver);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && slideOver.classList.contains('is-open')) {
            cerrarEmpleadoSlideOver();
        }
    });
})();

function eliminarEmpleado(id) {
    var empleado = empleadosById[id];
    if (!empleado) return;

    confirmarAccion({
        titulo: '¿Eliminar este empleado?',
        texto: '"' + empleado.nombreCompleto + '" se quitará de tu lista. Sus documentos de nómina ya emitidos se conservan.',
        textoConfirmar: 'Sí, eliminar',
        peligro: true
    }).then(function (confirmado) {
        if (!confirmado) return;

        nominaApiRequest('DELETE', '/cliente/nomina/empleados/' + id)
            .then(function () {
                var row = document.querySelector('#empleadosTable .data-table__row[data-empleado-id="' + id + '"]');
                if (row) row.remove();
                delete empleadosById[id];

                renderEmpleadosPagina();
                cerrarEmpleadoSlideOver();
            })
            .catch(function (error) { mostrarError(error.message); });
    });
}

function crearFilaEmpleado(empleado) {
    var row = document.createElement('tr');
    row.className = 'data-table__row';
    row.setAttribute('data-empleado-id', empleado.id);
    row.tabIndex = 0;

    var celdaNombre = row.insertCell();
    celdaNombre.className = 'data-table__title';
    celdaNombre.textContent = empleado.nombreCompleto;

    var celdaDoc = row.insertCell();
    celdaDoc.className = 'data-table__meta';
    celdaDoc.textContent = empleado.tipoDocumento + ' ' + empleado.numeroDocumento;

    var celdaCargo = row.insertCell();
    celdaCargo.className = 'data-table__meta';
    celdaCargo.textContent = empleado.cargo || '—';

    var celdaSalario = row.insertCell();
    celdaSalario.className = 'data-table__meta';
    celdaSalario.textContent = empleado.salario !== null ? formatCOP(empleado.salario) : '—';

    var celdaEstado = row.insertCell();
    var pill = document.createElement('span');
    pill.className = 'status-pill ' + (empleado.activo ? 'status-pill--pagada' : 'status-pill--error');
    pill.textContent = empleado.activo ? 'Activo' : 'Retirado';
    celdaEstado.appendChild(pill);

    row.addEventListener('click', function () { abrirEmpleadoSlideOver(empleado.id); });
    row.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            abrirEmpleadoSlideOver(empleado.id);
        }
    });

    return row;
}

function actualizarFilaEmpleado(empleado) {
    var row = document.querySelector('#empleadosTable .data-table__row[data-empleado-id="' + empleado.id + '"]');
    if (!row) return;
    row.cells[0].textContent = empleado.nombreCompleto;
    row.cells[1].textContent = empleado.tipoDocumento + ' ' + empleado.numeroDocumento;
    row.cells[2].textContent = empleado.cargo || '—';
    row.cells[3].textContent = empleado.salario !== null ? formatCOP(empleado.salario) : '—';
    var pill = row.cells[4].querySelector('.status-pill');
    pill.className = 'status-pill ' + (empleado.activo ? 'status-pill--pagada' : 'status-pill--error');
    pill.textContent = empleado.activo ? 'Activo' : 'Retirado';
}

/* --------------------------------------------------------------------
 * Modal "Nuevo/Editar empleado"
 * ------------------------------------------------------------------ */
function initEmpleadoModal() {
    var openBtn = document.getElementById('nuevoEmpleadoBtn');
    var modal = document.getElementById('empleadoModal');
    var overlay = document.getElementById('empleadoModalOverlay');
    if (!openBtn || !modal || !overlay) return;

    var closeBtn = document.getElementById('empleadoModalClose');
    var titleEl = document.getElementById('empleadoModalTitle');
    var nombresInput = document.getElementById('empNombres');
    var apellidosInput = document.getElementById('empApellidos');
    var tipoDocSelect = document.getElementById('empTipoDoc');
    var numDocInput = document.getElementById('empNumDoc');
    var cargoInput = document.getElementById('empCargo');
    var salarioInput = document.getElementById('empSalario');
    var guardarBtn = document.getElementById('empGuardarBtn');

    formatearInputDinero(salarioInput);

    var empleadoEditandoId = null;

    function updateGuardarState() {
        guardarBtn.disabled = nombresInput.value.trim() === ''
            || apellidosInput.value.trim() === ''
            || numDocInput.value.trim() === '';
    }

    [nombresInput, apellidosInput, numDocInput].forEach(function (input) {
        input.addEventListener('input', updateGuardarState);
    });

    function resetModalVacio() {
        nombresInput.value = '';
        apellidosInput.value = '';
        tipoDocSelect.value = 'CC';
        numDocInput.value = '';
        cargoInput.value = '';
        salarioInput.value = '';
        updateGuardarState();
    }

    function llenarFormulario(empleado) {
        nombresInput.value = empleado.nombres;
        apellidosInput.value = empleado.apellidos;
        tipoDocSelect.value = empleado.tipoDocumento;
        numDocInput.value = empleado.numeroDocumento;
        cargoInput.value = empleado.cargo || '';
        salarioInput.value = empleado.salario !== null ? formatNumber(empleado.salario, 0) : '';
        updateGuardarState();
    }

    function openModal(empleado) {
        empleadoEditandoId = empleado ? empleado.id : null;

        if (empleado) {
            titleEl.textContent = 'Editar empleado';
            guardarBtn.textContent = 'Guardar cambios';
            llenarFormulario(empleado);
        } else {
            titleEl.textContent = 'Nuevo empleado';
            guardarBtn.textContent = 'Guardar empleado';
            resetModalVacio();
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-visible');
        window.setTimeout(function () { nombresInput.focus(); }, 250);
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-visible');
    }

    openBtn.addEventListener('click', function () { openModal(null); });
    window.abrirEditarEmpleado = function (id) {
        var empleado = empleadosById[id];
        if (empleado) openModal(empleado);
    };

    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
    });

    guardarBtn.addEventListener('click', function () {
        if (guardarBtn.disabled) return;

        var originalText = guardarBtn.textContent;
        guardarBtn.disabled = true;
        guardarBtn.textContent = empleadoEditandoId ? 'Guardando cambios...' : 'Guardando...';

        var payload = {
            nombres: nombresInput.value.trim(),
            apellidos: apellidosInput.value.trim(),
            tipo_documento: tipoDocSelect.value,
            numero_documento: numDocInput.value.trim(),
            cargo: cargoInput.value.trim() || null,
            salario: salarioInput.value.trim() !== '' ? valorDineroInput(salarioInput) : null
        };

        var url = empleadoEditandoId ? '/cliente/nomina/empleados/' + empleadoEditandoId : '/cliente/nomina/empleados';
        var method = empleadoEditandoId ? 'PUT' : 'POST';

        nominaApiRequest(method, url, payload)
            .then(function (json) {
                var empleado = json.empleado;
                empleadosById[empleado.id] = empleado;

                if (empleadoEditandoId) {
                    actualizarFilaEmpleado(empleado);
                } else {
                    var tbody = document.querySelector('#empleadosTable tbody');
                    if (tbody) tbody.appendChild(crearFilaEmpleado(empleado));
                    renderEmpleadosPagina();
                }

                closeModal();
            })
            .catch(function (error) { mostrarError(error.message); })
            .finally(function () {
                guardarBtn.disabled = false;
                guardarBtn.textContent = originalText;
            });
    });
}

/* --------------------------------------------------------------------
 * 4. Tabla de documentos de nómina + slide-over
 * ------------------------------------------------------------------ */
function initDocumentosTable() {
    var table = document.getElementById('nominaDocumentosTable');
    if (!table) return;

    table.querySelectorAll('.data-table__row').forEach(function (row) {
        var id = parseInt(row.getAttribute('data-doc-nomina-id'), 10);
        row.addEventListener('click', function () { abrirDocNominaSlideOver(id); });
        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                abrirDocNominaSlideOver(id);
            }
        });
    });

    document.getElementById('docNominaSlideOverClose')?.addEventListener('click', cerrarDocNominaSlideOver);
    document.getElementById('docNominaSlideOverOverlay')?.addEventListener('click', cerrarDocNominaSlideOver);

    document.getElementById('docNominaAnularBtn')?.addEventListener('click', function () {
        var slideOver = document.getElementById('docNominaSlideOver');
        var id = parseInt(slideOver.dataset.docId, 10);
        anularDocumentoNomina(id);
    });
}

function abrirDocNominaSlideOver(id) {
    var doc = nominaDocsById[id];
    var overlay = document.getElementById('docNominaSlideOverOverlay');
    var slideOver = document.getElementById('docNominaSlideOver');
    if (!doc || !overlay || !slideOver) return;

    slideOver.dataset.docId = String(id);

    document.getElementById('docNominaSlideOverNumero').textContent = doc.numero;

    var estadoEl = document.getElementById('docNominaSlideOverEstado');
    estadoEl.textContent = doc.estado === 'emitida' ? 'Emitida' : 'Anulada';
    estadoEl.className = 'status-pill ' + (doc.estado === 'emitida' ? 'status-pill--pagada' : 'status-pill--error');

    document.getElementById('docNominaSlideOverEmpleado').textContent = doc.empleado.nombre;
    document.getElementById('docNominaSlideOverDocumentoEmpleado').textContent = doc.empleado.numDoc;
    document.getElementById('docNominaSlideOverPeriodo').textContent = doc.periodo;
    document.getElementById('docNominaSlideOverFechaPago').textContent = doc.fechaPago;
    document.getElementById('docNominaSlideOverMonto').textContent = formatCOP(doc.montoPagado);
    document.getElementById('docNominaSlideOverCune').textContent = doc.cune;

    var descargarBtn = document.getElementById('docNominaDescargarBtn');
    if (descargarBtn) descargarBtn.href = '/cliente/nomina/documentos/' + doc.id + '/pdf';

    var anularSection = document.getElementById('docNominaAnularSection');
    if (anularSection) anularSection.hidden = doc.estado === 'anulada';

    slideOver.classList.add('is-open');
    slideOver.setAttribute('aria-hidden', 'false');
    overlay.classList.add('is-visible');
}

function cerrarDocNominaSlideOver() {
    document.getElementById('docNominaSlideOverOverlay').classList.remove('is-visible');
    document.getElementById('docNominaSlideOver').classList.remove('is-open');
    document.getElementById('docNominaSlideOver').setAttribute('aria-hidden', 'true');
}

function anularDocumentoNomina(id) {
    confirmarAccion({
        titulo: '¿Anular este documento?',
        texto: 'El documento quedará marcado como anulado.',
        textoConfirmar: 'Sí, anular',
        peligro: true
    }).then(function (confirmado) {
        if (!confirmado) return;

        nominaApiRequest('POST', '/cliente/nomina/documentos/' + id + '/anular', {})
            .then(function () {
                cerrarDocNominaSlideOver();
                window.location.reload();
            })
            .catch(function (error) { mostrarError(error.message); });
    });
}

/* --------------------------------------------------------------------
 * 5. Modal "Pagar nómina" -select de empleado + "Agregar" arma la lista
 *    de pago; cada fila se puede quitar (vuelve a aparecer en el select).
 * ------------------------------------------------------------------ */
function initPagarNominaModal() {
    var openBtn = document.getElementById('pagarNominaBtn');
    var modal = document.getElementById('pagarNominaModal');
    var overlay = document.getElementById('pagarNominaOverlay');
    if (!openBtn || !modal || !overlay) return;

    var closeBtn = document.getElementById('pagarNominaClose');
    var cancelarBtn = document.getElementById('pagarNominaCancelar');
    var periodoMesSelect = document.getElementById('pagoPeriodoMes');
    var periodoAnioSelect = document.getElementById('pagoPeriodoAnio');
    function periodoTexto() {
        return periodoMesSelect.value + ' ' + periodoAnioSelect.value;
    }
    var fechaInput = document.getElementById('pagoFecha');
    var emitirBtn = document.getElementById('pagarNominaEmitir');
    var totalEl = document.getElementById('pagoTotalSeleccionado');
    var select = document.getElementById('pagoEmpleadoSelect');
    var lista = document.getElementById('pagoEmpleadosList');
    var listaHead = document.getElementById('pagoEmpleadosHead');
    var listaVacia = document.getElementById('pagoEmpleadosVacio');

    function recalcularTotal() {
        var total = 0;
        lista.querySelectorAll('.pago-empleado-monto').forEach(function (input) {
            total += valorDineroInput(input);
        });
        totalEl.textContent = formatCOP(total);
        emitirBtn.disabled = total <= 0;
    }

    function actualizarVisibilidadLista() {
        var hayFilas = lista.children.length > 0;
        listaHead.hidden = !hayFilas;
        listaVacia.hidden = hayFilas;
    }

    /** Ícono "quitar" -mismo trazo que el resto de botones de cerrar/quitar. */
    function iconoQuitar() {
        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('fill', 'none');
        svg.setAttribute('stroke', 'currentColor');
        svg.setAttribute('stroke-width', '1.8');
        svg.setAttribute('stroke-linecap', 'round');
        var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', 'M6 6l12 12M18 6 6 18');
        svg.appendChild(path);
        return svg;
    }

    function agregarFila(option) {
        var empleadoId = option.value;
        var salarioDia = parseFloat(option.dataset.salarioDia) || 0;

        var row = document.createElement('div');
        row.className = 'pago-empleado-row';
        row.dataset.empleadoId = empleadoId;

        var info = document.createElement('div');
        info.className = 'pago-empleado-row__info';
        var nombreEl = document.createElement('span');
        nombreEl.textContent = option.dataset.nombre;
        var docEl = document.createElement('span');
        docEl.textContent = option.dataset.doc + (salarioDia > 0 ? ' · ' + formatCOP(salarioDia) + '/día' : '');
        info.appendChild(nombreEl);
        info.appendChild(docEl);

        var diasInput = document.createElement('input');
        diasInput.type = 'number';
        diasInput.min = '0';
        diasInput.max = '31';
        diasInput.placeholder = 'Días';
        diasInput.className = 'cliente-input pago-empleado-dias';
        diasInput.dataset.salarioDia = String(salarioDia);
        if (salarioDia <= 0) diasInput.disabled = true;

        var montoInput = document.createElement('input');
        montoInput.type = 'text';
        montoInput.inputMode = 'numeric';
        montoInput.placeholder = '0';
        montoInput.className = 'cliente-input pago-empleado-monto';
        formatearInputDinero(montoInput);

        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'pago-empleado-remove';
        removeBtn.setAttribute('aria-label', 'Quitar empleado');
        removeBtn.appendChild(iconoQuitar());

        // Días trabajados solo SUGIERE el monto (salario por día × días)
        // -no lo bloquea. Si la persona edita el monto directamente
        // después, esa edición manual se respeta hasta que vuelva a
        // tocar los días.
        diasInput.addEventListener('input', function () {
            var dias = parseFloat(diasInput.value) || 0;
            montoInput.value = dias > 0 && salarioDia > 0 ? Math.round(dias * salarioDia).toLocaleString('es-CO') : '';
            recalcularTotal();
        });
        montoInput.addEventListener('input', recalcularTotal);

        removeBtn.addEventListener('click', function () {
            row.remove();
            option.hidden = false;
            option.disabled = false;
            actualizarVisibilidadLista();
            recalcularTotal();
        });

        row.appendChild(info);
        row.appendChild(diasInput);
        row.appendChild(montoInput);
        row.appendChild(removeBtn);
        lista.appendChild(row);

        option.hidden = true;
        option.disabled = true;
        select.value = '';

        actualizarVisibilidadLista();
        recalcularTotal();
    }

    // Elegir un empleado en el select ya lo agrega -sin botón aparte. Si
    // te equivocas, cada fila tiene su propio botón de quitar.
    select.addEventListener('change', function () {
        var option = select.options[select.selectedIndex];
        if (!option || !option.value) return;
        agregarFila(option);
    });


    function openModal() {
        lista.innerHTML = '';
        select.querySelectorAll('option[value]').forEach(function (opt) {
            if (opt.value) { opt.hidden = false; opt.disabled = false; }
        });
        select.value = '';
        periodoMesSelect.selectedIndex = new Date().getMonth();
        periodoAnioSelect.value = String(new Date().getFullYear());
        actualizarVisibilidadLista();
        recalcularTotal();
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-visible');
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-visible');
    }

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelarBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
    });

    emitirBtn.addEventListener('click', function () {
        var pagos = [];
        lista.querySelectorAll('.pago-empleado-row').forEach(function (row) {
            var montoInput = row.querySelector('.pago-empleado-monto');
            var monto = montoInput ? valorDineroInput(montoInput) : 0;
            if (monto > 0) {
                pagos.push({ empleado_id: parseInt(row.dataset.empleadoId, 10), monto_pagado: monto });
            }
        });

        if (pagos.length === 0) return;

        var originalText = emitirBtn.textContent;
        emitirBtn.disabled = true;
        emitirBtn.textContent = 'Emitiendo…';

        nominaApiRequest('POST', '/cliente/nomina/documentos', {
            periodo: periodoTexto(),
            fecha_pago: fechaInput.value,
            pagos: pagos
        })
            .then(function () {
                closeModal();
                window.location.reload();
            })
            .catch(function (error) {
                emitirBtn.disabled = false;
                emitirBtn.textContent = originalText;
                mostrarError(error.message);
            });
    });
}
