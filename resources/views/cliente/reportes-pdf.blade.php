<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte {{ $periodoLabel }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1E2D3D;
            padding: 36px 40px;
            line-height: 1.5;
        }

        /* ——— Header ——— */
        .pdf-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #4A7C6F;
            padding-bottom: 14px;
            margin-bottom: 24px;
        }
        .pdf-header__brand { font-size: 20px; font-weight: 700; color: #4A7C6F; }
        .pdf-header__neg   { font-size: 12px; color: #1E2D3D; font-weight: 600; margin-top: 2px; }
        .pdf-header__meta  { font-size: 10px; color: #566573; text-align: right; }

        /* ——— Sección ——— */
        .section-title {
            font-size: 10px;
            font-weight: 700;
            color: #4A7C6F;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            border-bottom: 1px solid #E5E1DA;
            padding-bottom: 5px;
            margin: 20px 0 10px;
        }

        /* ——— Stats ——— */
        .stat-row { width: 100%; }
        .stat-row td {
            padding: 10px 12px;
            background: #F4F2EF;
            border: 3px solid #fff;
            text-align: center;
            width: 25%;
        }
        .stat-row .stat-value { font-size: 17px; font-weight: 700; color: #1E2D3D; display: block; }
        .stat-row .stat-label { font-size: 9px; color: #566573; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-top: 2px; }

        /* ——— Tablas de datos ——— */
        .data-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .data-table th {
            background: #F4F2EF;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #566573;
            font-weight: 600;
            padding: 6px 8px;
            text-align: left;
            border-bottom: 1px solid #E5E1DA;
        }
        .data-table td { padding: 6px 8px; border-bottom: 1px solid #F0EDE8; }
        .data-table tr:nth-child(even) td { background: #F9F8F6; }
        .data-table .num { text-align: right; font-variant-numeric: tabular-nums; }
        .data-table .empty { color: #566573; font-style: italic; text-align: center; padding: 14px; }

        /* ——— Dos columnas ——— */
        .two-col { width: 100%; }
        .two-col td { vertical-align: top; width: 50%; padding-right: 16px; }
        .two-col td:last-child { padding-right: 0; }

        /* ——— Footer ——— */
        .pdf-footer {
            margin-top: 32px;
            font-size: 9px;
            color: #566573;
            text-align: center;
            border-top: 1px solid #E5E1DA;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    {{-- ——— Encabezado ——— --}}
    <div class="pdf-header">
        <div>
            <div class="pdf-header__brand">Stockly</div>
            <div class="pdf-header__neg">{{ $empresa?->nombre_negocio ?? 'Mi negocio' }}</div>
        </div>
        <div class="pdf-header__meta">
            Reporte — {{ $periodoLabel }}<br>
            {{ $generadoEl }}
        </div>
    </div>

    {{-- ——— Resumen ——— --}}
    <div class="section-title">Resumen del período</div>
    <table class="stat-row">
        <tr>
            <td>
                <span class="stat-value">${{ number_format($data['ingresos'], 0, ',', '.') }}</span>
                <span class="stat-label">Ingresos</span>
            </td>
            <td>
                <span class="stat-value">${{ number_format($data['gastos'], 0, ',', '.') }}</span>
                <span class="stat-label">Gastos</span>
            </td>
            <td>
                <span class="stat-value">{{ $data['gananciaNeta'] < 0 ? '-' : '' }}${{ number_format(abs($data['gananciaNeta']), 0, ',', '.') }}</span>
                <span class="stat-label">Ganancia neta</span>
            </td>
            <td>
                <span class="stat-value">{{ $data['cantidadVentas'] }}</span>
                <span class="stat-label">Ventas</span>
            </td>
        </tr>
    </table>

    {{-- ——— Métodos de pago + Gastos por categoría ——— --}}
    @php
        $totalPagos   = $data['pagoEfectivo'] + $data['pagoDigital'];
        $pctEfectivo  = $totalPagos > 0 ? round(($data['pagoEfectivo'] / $totalPagos) * 100) : 0;
        $pctDigital   = 100 - $pctEfectivo;
    @endphp

    <table class="two-col">
        <tr>
            <td>
                <div class="section-title">Métodos de pago</div>
                <table class="data-table">
                    <thead>
                        <tr><th>Método</th><th class="num">Total</th><th class="num">%</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Efectivo</td>
                            <td class="num">${{ number_format($data['pagoEfectivo'], 0, ',', '.') }}</td>
                            <td class="num">{{ $pctEfectivo }}%</td>
                        </tr>
                        <tr>
                            <td>Digital</td>
                            <td class="num">${{ number_format($data['pagoDigital'], 0, ',', '.') }}</td>
                            <td class="num">{{ $pctDigital }}%</td>
                        </tr>
                    </tbody>
                </table>

                <div class="section-title" style="margin-top:10px;">Ganancia neta por método</div>
                <table class="data-table">
                    <thead>
                        <tr><th>Método</th><th class="num">Ganancia neta</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Efectivo</td>
                            <td class="num">{{ $data['gananciaNetaEfectivo'] < 0 ? '-' : '' }}${{ number_format(abs($data['gananciaNetaEfectivo']), 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Digital</td>
                            <td class="num">{{ $data['gananciaNetaDigital'] < 0 ? '-' : '' }}${{ number_format(abs($data['gananciaNetaDigital']), 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td>
                <div class="section-title">Gastos por categoría</div>
                <table class="data-table">
                    <thead>
                        <tr><th>Categoría</th><th class="num">Total</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Nómina</td><td class="num">${{ number_format($data['gastosCategorias']['nomina'], 0, ',', '.') }}</td></tr>
                        <tr><td>Arriendo</td><td class="num">${{ number_format($data['gastosCategorias']['arriendo'], 0, ',', '.') }}</td></tr>
                        <tr><td>Servicios</td><td class="num">${{ number_format($data['gastosCategorias']['servicios'], 0, ',', '.') }}</td></tr>
                        <tr><td>Otros</td><td class="num">${{ number_format($data['gastosCategorias']['otros'], 0, ',', '.') }}</td></tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    {{-- ——— Top productos ——— --}}
    <div class="section-title">Top productos más vendidos</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Producto</th>
                <th class="num">Unidades</th>
                <th class="num">Ingresos generados</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data['topProductos'] as $i => $prod)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $prod['nombre'] }}</td>
                    <td class="num">{{ $prod['cantidad'] }}</td>
                    <td class="num">${{ number_format($prod['ingresos'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="4">Sin ventas en este período</td></tr>
            @endforelse
        </tbody>
    </table>

    @if (is_array($data['ventasDetalle'] ?? null))
        {{-- ——— Detalle de ventas del día ——— --}}
        <div class="section-title">Detalle de ventas del día</div>
        <table class="data-table">
            <thead>
                <tr><th>Hora</th><th>Producto(s)</th><th class="num">Método</th><th class="num">Total</th></tr>
            </thead>
            <tbody>
                @forelse ($data['ventasDetalle'] as $venta)
                    <tr>
                        <td>{{ $venta['hora'] }}</td>
                        <td>{{ $venta['productos'] }}</td>
                        <td class="num">{{ $venta['metodo'] }}</td>
                        <td class="num">${{ number_format($venta['total'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td class="empty" colspan="4">Sin ventas este día</td></tr>
                @endforelse
            </tbody>
        </table>
    @else
        {{-- ——— Ventas por período ——— --}}
        <div class="section-title">Ventas por período</div>
        <table class="data-table">
            <thead>
                <tr><th>Período</th><th class="num">Total en ventas</th></tr>
            </thead>
            <tbody>
                @foreach ($data['graficaBars'] as $bar)
                    <tr>
                        <td>{{ $bar['label'] }}{{ $bar['esHoy'] ? ' (hoy)' : '' }}</td>
                        <td class="num">${{ number_format($bar['total'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="pdf-footer">
        Generado por Stockly · {{ $generadoEl }}
    </div>

</body>
</html>
