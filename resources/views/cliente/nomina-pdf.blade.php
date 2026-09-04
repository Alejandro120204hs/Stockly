<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nómina {{ $documento->numero }}</title>
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 13px;
            color: #1E2D3D;
            margin: 0;
            padding: 50px 55px;
        }

        .marca {
            border-bottom: 4px solid #4A7C6F;
            padding-bottom: 20px;
            margin-bottom: 28px;
        }

        .marca table {
            width: 100%;
        }

        .marca__logo img {
            max-height: 64px;
            max-width: 220px;
        }

        .marca__logo-fallback {
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 24px;
            font-weight: bold;
            color: #1E2D3D;
        }

        .marca__tag {
            text-align: right;
        }

        .marca__tag span {
            display: inline-block;
            border: 1.5px solid #1E2D3D;
            border-radius: 6px;
            padding: 8px 18px;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.03em;
        }

        .info-grid {
            width: 100%;
            margin-bottom: 26px;
        }

        .info-grid td {
            width: 50%;
            vertical-align: top;
            padding: 18px 20px;
            background-color: #F2F0ED;
        }

        .info-grid td.info-negocio {
            border-radius: 8px 0 0 8px;
        }

        .info-grid td.info-cliente {
            background-color: #E3ECE9;
            border-radius: 0 8px 8px 0;
        }

        .info-grid h3 {
            margin: 0 0 8px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #566573;
        }

        .info-grid p {
            margin: 0 0 3px;
            font-size: 12px;
        }

        .info-grid p.nombre {
            font-size: 13.5px;
            font-weight: bold;
        }

        .pago-wrap {
            border: 1px solid #1E2D3D;
            border-radius: 8px;
            padding: 22px 24px;
            margin-bottom: 26px;
        }

        .pago-wrap .fila {
            display: table;
            width: 100%;
            padding: 6px 0;
        }

        .pago-wrap .fila .etiqueta,
        .pago-wrap .fila .valor {
            display: table-cell;
        }

        .pago-wrap .etiqueta {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #566573;
        }

        .pago-wrap .valor {
            text-align: right;
            font-size: 13px;
        }

        .total-wrap {
            width: 100%;
            margin: 0 0 30px;
        }

        .total-wrap table {
            width: 260px;
            float: right;
            border-collapse: collapse;
        }

        .total-wrap td {
            padding: 12px 16px;
            background-color: #F2F0ED;
        }

        .total-wrap td.label {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #566573;
            border-radius: 6px 0 0 6px;
        }

        .total-wrap td.numero {
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            color: #1E2D3D;
            background-color: #E3ECE9;
            border-radius: 0 6px 6px 0;
        }

        .cierre {
            clear: both;
            page-break-inside: avoid;
        }

        .dian {
            padding-top: 18px;
            border-top: 1px dashed #C9CDD2;
        }

        .dian h3 {
            margin: 0 0 8px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #566573;
        }

        .dian__cufe {
            font-size: 10px;
            word-break: break-all;
            background-color: #F2F0ED;
            padding: 8px 10px;
            border-radius: 6px;
            color: #1E2D3D;
        }

        .pie {
            margin-top: 22px;
            padding-top: 14px;
            border-top: 1px solid #E3E6E9;
            text-align: center;
        }

        .pie__legal {
            margin: 0;
            font-size: 10px;
            color: #8C9BAB;
        }
    </style>
</head>
<body>
    <div class="marca">
        <table>
            <tr>
                <td class="marca__logo">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="{{ $empresa->nombre_negocio }}">
                    @else
                        <span class="marca__logo-fallback">{{ $empresa->nombre_negocio }}</span>
                    @endif
                </td>
                <td class="marca__tag">
                    <span>{{ $documento->numero }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="info-grid">
        <tr>
            <td class="info-negocio">
                <h3>Negocio</h3>
                <p class="nombre">{{ $empresa->nombre_negocio }}</p>
                @if ($empresa->nit)
                    <p>NIT {{ $empresa->nit }}{{ $empresa->dv ? '-'.$empresa->dv : '' }}</p>
                @endif
                @if ($empresa->direccion)
                    <p>{{ $empresa->direccion }}{{ $empresa->ciudad ? ', '.$empresa->ciudad : '' }}</p>
                @endif
            </td>
            <td class="info-cliente">
                <h3>Empleado</h3>
                <p class="nombre">{{ $documento->empleado->nombreCompleto() }}</p>
                <p>{{ $documento->empleado->tipo_documento }} {{ $documento->empleado->numero_documento }}</p>
                @if ($documento->empleado->cargo)
                    <p>{{ $documento->empleado->cargo }}</p>
                @endif
            </td>
        </tr>
    </table>

    <div class="pago-wrap">
        <div class="fila">
            <span class="etiqueta">Período</span>
            <span class="valor">{{ $documento->periodo }}</span>
        </div>
        <div class="fila">
            <span class="etiqueta">Fecha de pago</span>
            <span class="valor">{{ $documento->fecha_pago->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</span>
        </div>
    </div>

    <div class="total-wrap">
        <table>
            <tr>
                <td class="label">Monto pagado</td>
                <td class="numero">${{ number_format($documento->monto_pagado, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="cierre">
        @if ($empresa->tiene_facturacion)
            <div class="dian">
                <h3>Verificación DIAN</h3>
                <p class="dian__cufe">CUNE: {{ $documento->cune }}</p>
            </div>

            <div class="pie">
                {{-- Mismo criterio de honestidad que el resto de Facturación:
                     este comprobante refleja lo que el negocio decidió pagar
                     -no calcula salud, pensión ni retención, y el CUNE es
                     simulado hasta que se conecte Factus de verdad. --}}
                <p class="pie__legal">
                    Documento generado por Stockly. La integración con la DIAN (Factus) está pendiente de conexión
                    -el CUNE mostrado es simulado y este documento todavía no tiene validez fiscal ante la DIAN.
                </p>
            </div>
        @else
            {{-- Sin el módulo de Facturación, este es un comprobante de pago
                 normal -sin ninguna mención a la DIAN ni a un documento
                 electrónico que esta empresa no puede emitir. --}}
            <div class="pie">
                <p class="pie__legal">Comprobante de pago generado por Stockly.</p>
            </div>
        @endif
    </div>
</body>
</html>
