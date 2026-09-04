{{-- Historial de pagos de ESTA empresa -mismas columnas que ve el admin en
     /admin/pagos, pero filtrado solo a lo propio. Se muestra sin importar
     el estado de la suscripción (útil como referencia siempre que exista
     al menos un pago). Espera $historial en scope (ver
     App\Http\Controllers\Cliente\SuscripcionController::index()).

     Paginado del lado del cliente (4 por página) -mismo patrón exacto que
     el historial de cierres de Caja (ver public/assets/js/cliente/caja.js
     initHistorialCierres()/renderHistorialCierres()): todo el historial ya
     vino cargado, el JS solo decide qué filas mostrar. --}}
@if ($historial->isNotEmpty())
    <div class="panel cliente-reveal cliente-reveal-5">
        <h2 class="panel__title" style="margin-bottom: 18px;">Historial de pagos</h2>

        <div class="data-table-wrap">
            <table class="data-table" id="suscripcionHistorialTable">
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($historial as $pago)
                        <tr class="data-table__row" data-pago-id="{{ $pago['id'] }}">
                            <td class="data-table__title">{{ $pago['plan'] }}</td>
                            <td class="data-table__meta">{{ $pago['monto'] !== null ? '$'.number_format($pago['monto'], 0, ',', '.') : '—' }}</td>
                            <td class="data-table__meta">{{ $pago['metodo'] ?? '—' }}</td>
                            <td class="data-table__meta">{{ $pago['fecha'] }}</td>
                            <td>
                                <span class="status-pill status-pill--{{ ['activado' => 'pagada', 'pago_recibido' => 'pendiente', 'rechazado' => 'rechazada'][$pago['estado']] ?? 'sin-facturar' }}">
                                    {{ $pago['estadoLabel'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="data-table__pagination" id="suscripcionHistorialPagination">
            <button type="button" class="cliente-btn-ghost" id="suscripcionHistorialPrevPage">← Anterior</button>
            <span class="data-table__pagination-info" id="suscripcionHistorialPageInfo">Página 1 de 1</span>
            <button type="button" class="cliente-btn-ghost" id="suscripcionHistorialNextPage">Siguiente →</button>
        </div>
    </div>
@endif
