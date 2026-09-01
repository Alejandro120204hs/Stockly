<x-cliente-layout title="Proveedores">

    {{-- Proveedores — datos reales (App\Http\Controllers\Cliente\ProveedorController).
         Se guardan los datos fiscales completos (NIT+DV, tipo de persona,
         régimen) porque un proveedor es otro tercero ante la DIAN -para
         que la contabilidad (deducción de costos, exógena) sea válida hace
         falta identificarlo bien, no solo con el nombre. Este módulo es
         además de dónde "Registrar compra" (en Inventario) elige el
         proveedor -ya no se escribe el nombre a mano cada vez. --}}

    <div class="cliente-page-header cliente-reveal cliente-reveal-1" style="display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div>
            <p class="cliente-page-header__eyebrow">Tu negocio</p>
            <h1 class="cliente-page-header__title">Proveedores</h1>
            <p class="cliente-page-header__date">{{ count($proveedores) }} proveedores registrados</p>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="button" class="cliente-btn-primary" id="nuevoProveedorBtn">+ Nuevo proveedor</button>
        </div>
    </div>

    <!-- ==========================================================
         STAT CARDS
         ========================================================== -->
    <section class="stat-grid stat-grid--3 cliente-reveal cliente-reveal-2">
        <div class="stat-card stat-card--sage">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="10" width="18" height="10" rx="1.5"/>
                    <path d="M7 10V7a5 5 0 0 1 10 0v3"/>
                </svg>
            </div>
            <span class="stat-card__value" id="statProveedores" data-count="{{ count($proveedores) }}">0</span>
            <span class="stat-card__label">Proveedores registrados</span>
        </div>

        <div class="stat-card stat-card--sand">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
            <span class="stat-card__value" id="statTotalComprado" data-count="{{ collect($proveedores)->sum('totalComprado') }}" data-prefix="$">$0</span>
            <span class="stat-card__label">Total comprado (histórico)</span>
        </div>

        <div class="stat-card stat-card--mist">
            <div class="stat-card__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="9" cy="20" r="1"/>
                    <circle cx="18" cy="20" r="1"/>
                    <path d="M3 4h2l2.3 11.4a2 2 0 0 0 2 1.6h7.4a2 2 0 0 0 2-1.6L21 8H6"/>
                </svg>
            </div>
            <span class="stat-card__value" id="statCompras" data-count="{{ collect($proveedores)->sum('comprasCount') }}">0</span>
            <span class="stat-card__label">Compras registradas</span>
        </div>
    </section>

    <!-- ==========================================================
         TABLA DE PROVEEDORES
         ========================================================== -->
    <div class="panel cliente-reveal cliente-reveal-3">
        <div class="cliente-toolbar">
            <div class="cliente-toolbar__search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <input type="search" id="proveedoresSearch" class="cliente-input" placeholder="Buscar por nombre o NIT..." autocomplete="off">
            </div>
        </div>

        <div class="data-table-wrap">
            <table class="data-table" id="proveedoresTable">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>NIT</th>
                        <th>Teléfono</th>
                        <th>Compras</th>
                        <th>Total comprado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($proveedores as $proveedor)
                        <tr class="data-table__row" data-proveedor-id="{{ $proveedor['id'] }}" tabindex="0">
                            <td>
                                <div class="data-table__title">{{ $proveedor['nombre'] }}</div>
                                <div class="data-table__meta">{{ $proveedor['tipoPersona'] === 'juridica' ? 'Persona jurídica' : 'Persona natural' }}</div>
                            </td>
                            <td class="data-table__meta">{{ $proveedor['nit'] }}{{ $proveedor['dv'] ? '-'.$proveedor['dv'] : '' }}</td>
                            <td class="data-table__meta">{{ $proveedor['telefono'] ?? '—' }}</td>
                            <td class="data-table__meta">{{ $proveedor['comprasCount'] }}</td>
                            <td class="data-table__title">${{ number_format($proveedor['totalComprado'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="data-table__empty" id="proveedoresEmpty" @if(count($proveedores) > 0) hidden @endif>
                Todavía no tienes proveedores registrados.
            </p>
        </div>

        <div class="data-table__pagination" id="proveedoresPagination">
            <button type="button" class="cliente-btn-ghost" id="proveedoresPrevPage">← Anterior</button>
            <span class="data-table__pagination-info" id="proveedoresPageInfo">Página 1 de 1</span>
            <button type="button" class="cliente-btn-ghost" id="proveedoresNextPage">Siguiente →</button>
        </div>
    </div>

    {{-- ==================================================================
         PANEL LATERAL — detalle del proveedor + historial de compras
         ================================================================== --}}
    <div class="slide-over-overlay" id="proveedorSlideOverOverlay"></div>

    <aside class="slide-over" id="proveedorSlideOver" aria-hidden="true">
        <div class="slide-over__header">
            <div>
                <h2 class="slide-over__title" id="proveedorSlideOverNombre">—</h2>
                <span class="status-pill status-pill--sin-facturar" id="proveedorSlideOverTipo">—</span>
            </div>
            <button type="button" class="slide-over__close" id="proveedorSlideOverClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="slide-over__body">
            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Datos fiscales</h3>
                <div class="slide-over__field"><span>NIT</span><strong id="proveedorSlideOverNit">—</strong></div>
                <div class="slide-over__field"><span>Régimen</span><strong id="proveedorSlideOverRegimen">—</strong></div>
                <div class="slide-over__field"><span>Teléfono</span><strong id="proveedorSlideOverTelefono">—</strong></div>
                <div class="slide-over__field"><span>Correo</span><strong id="proveedorSlideOverCorreo">—</strong></div>
                <div class="slide-over__field"><span>Dirección</span><strong id="proveedorSlideOverDireccion">—</strong></div>
                <div class="slide-over__field"><span>Ciudad</span><strong id="proveedorSlideOverCiudad">—</strong></div>
            </section>

            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Historial de compras</h3>
                <div id="proveedorSlideOverCompras"></div>
                <p class="data-table__empty" id="proveedorSlideOverSinCompras" hidden>Todavía no hay compras registradas con este proveedor.</p>
                <div class="data-table__pagination" id="proveedorSlideOverComprasPagination" hidden>
                    <button type="button" class="cliente-btn-ghost" id="proveedorSlideOverComprasPrev">← Anterior</button>
                    <span class="data-table__pagination-info" id="proveedorSlideOverComprasPageInfo">Página 1 de 1</span>
                    <button type="button" class="cliente-btn-ghost" id="proveedorSlideOverComprasNext">Siguiente →</button>
                </div>
            </section>

            <button type="button" class="cliente-btn-ghost" id="proveedorSlideOverEditarBtn" style="width:100%; margin-bottom:6px;">
                Editar proveedor
            </button>
            <button type="button" class="cliente-btn-ghost cliente-btn-ghost--peligro" id="proveedorSlideOverEliminarBtn" style="width:100%;">
                Eliminar proveedor
            </button>
        </div>
    </aside>

    {{-- ==================================================================
         PANEL LATERAL — detalle de una compra del historial (mismo
         contenido que el detalle de compra en Inventario). Se abre encima
         del panel del proveedor -misma posición, así que queda "apilado".
         ================================================================== --}}
    <div class="slide-over-overlay" id="compraDetalleOverlay"></div>

    <aside class="slide-over" id="compraDetalleSlideOver" aria-hidden="true">
        <div class="slide-over__header">
            <div>
                <h2 class="slide-over__title" id="compraDetalleTitulo">—</h2>
                <span class="status-pill" id="compraDetalleEstado">—</span>
            </div>
            <button type="button" class="slide-over__close" id="compraDetalleClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="slide-over__body">
            <section class="slide-over__section">
                <h3 class="slide-over__section-title">Productos comprados</h3>
                <div id="compraDetalleLineas"></div>
                <div class="data-table__pagination" id="compraDetalleLineasPagination" hidden>
                    <button type="button" class="cliente-btn-ghost" id="compraDetalleLineasPrev">← Anterior</button>
                    <span class="data-table__pagination-info" id="compraDetalleLineasPageInfo">Página 1 de 1</span>
                    <button type="button" class="cliente-btn-ghost" id="compraDetalleLineasNext">Siguiente →</button>
                </div>
            </section>

            <section class="slide-over__section" id="compraDetalleInfoSection">
                <h3 class="slide-over__section-title">Compra</h3>
                <div class="slide-over__compra-info">
                    <div class="slide-over__field" id="compraDetalleCufeRow"><span>CUFE</span><strong id="compraDetalleCufe">—</strong></div>
                    <div class="slide-over__compra-info-total">
                        <span>Total de la compra</span>
                        <strong id="compraDetalleTotal">—</strong>
                    </div>
                </div>
            </section>
        </div>
    </aside>

    {{-- ==================================================================
         MODAL — Nuevo/Editar proveedor
         ================================================================== --}}
    <div class="modal-overlay" id="proveedorModalOverlay"></div>

    <div class="modal" id="proveedorModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="proveedorModalTitle">
        <div class="modal__header">
            <h2 class="modal__title" id="proveedorModalTitle">Nuevo proveedor</h2>
            <button type="button" class="modal__close" id="proveedorModalClose" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18"/>
                </svg>
            </button>
        </div>

        <div class="modal__body">
            <label for="provNombre" class="cliente-label">Nombre o razón social</label>
            <input type="text" id="provNombre" class="cliente-input" placeholder="Ej: Licorera Continental S.A.S." style="margin-bottom:14px;">

            <div class="proveedor-form-row">
                <div>
                    <label for="provNit" class="cliente-label">NIT</label>
                    <input type="text" id="provNit" class="cliente-input" placeholder="900123456" inputmode="numeric">
                </div>
                <div>
                    <label for="provDv" class="cliente-label">DV</label>
                    <input type="text" id="provDv" class="cliente-input" placeholder="0" maxlength="2" inputmode="numeric">
                </div>
            </div>

            <label for="provTipoPersona" class="cliente-label">Tipo de persona</label>
            <select id="provTipoPersona" class="cliente-toolbar__select" style="width:100%; margin-bottom:14px;">
                <option value="juridica">Persona jurídica</option>
                <option value="natural">Persona natural</option>
            </select>

            <label for="provRegimenFiscal" class="cliente-label">Régimen fiscal</label>
            <select id="provRegimenFiscal" class="cliente-toolbar__select" style="width:100%; margin-bottom:14px;">
                <option value="">Sin especificar</option>
                <option value="Régimen ordinario">Régimen ordinario</option>
                <option value="Régimen Simple de Tributación (RST)">Régimen Simple de Tributación (RST)</option>
                <option value="Gran contribuyente">Gran contribuyente</option>
                <option value="Responsable de IVA">Responsable de IVA</option>
                <option value="No responsable de IVA">No responsable de IVA</option>
            </select>

            <div class="proveedor-form-row">
                <div>
                    <label for="provTelefono" class="cliente-label">Teléfono</label>
                    <input type="text" id="provTelefono" class="cliente-input" placeholder="300 123 4567">
                </div>
                <div>
                    <label for="provCorreo" class="cliente-label">Correo</label>
                    <input type="email" id="provCorreo" class="cliente-input" placeholder="contacto@proveedor.com">
                </div>
            </div>

            <label for="provDireccion" class="cliente-label">Dirección</label>
            <input type="text" id="provDireccion" class="cliente-input" placeholder="Cra 10 # 20-30" style="margin-bottom:14px;">

            {{-- Departamento -> Ciudad son selects dependientes, llenados
                 por proveedores.js desde el mismo dataset real que ya usa
                 el registro (colombia-locations.js) -no se escriben a mano. --}}
            <div class="proveedor-form-row">
                <div>
                    <label for="provDepartamento" class="cliente-label">Departamento</label>
                    <select id="provDepartamento" class="cliente-toolbar__select" style="width:100%;">
                        <option value="" disabled selected>Selecciona</option>
                    </select>
                </div>
                <div>
                    <label for="provCiudad" class="cliente-label">Ciudad</label>
                    <select id="provCiudad" class="cliente-toolbar__select" style="width:100%;" disabled>
                        <option value="" disabled selected>Elige un departamento</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="modal__footer">
            <button type="button" class="cliente-btn-primary" id="provGuardarBtn" disabled>Guardar proveedor</button>
        </div>
    </div>

    <script id="proveedoresData" type="application/json">{!! json_encode($proveedores) !!}</script>

    @push('styles')
        <link rel="stylesheet" href="{{ asset_v('assets/css/cliente/proveedores.css') }}">
    @endpush

    @push('scripts')
        <script src="{{ asset_v('assets/js/colombia-locations.js') }}" defer></script>
        <script src="{{ asset_v('assets/js/cliente/proveedores.js') }}" defer></script>
    @endpush

</x-cliente-layout>
