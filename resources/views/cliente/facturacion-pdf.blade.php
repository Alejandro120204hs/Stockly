<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura {{ $documento->numero }}</title>
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

        .lineas-wrap {
            border: 1px solid #1E2D3D;
            margin-bottom: 4px;
        }

        /* Máximo 12 filas por hoja -cada bloque es una tabla COMPLETA con
           su propio borde entero (nunca partida entre dos páginas), igual
           que en el recibo de Ventas (ver recibo-pdf.blade.php). */
        .lineas-wrap.salto-pagina {
            page-break-after: always;
        }

        table.lineas {
            width: 100%;
            border-collapse: collapse;
        }

        table.lineas thead th {
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #566573;
            background-color: #F2F0ED;
            border-bottom: 1.5px solid #1E2D3D;
            padding: 10px 12px;
        }

        table.lineas tbody tr {
            page-break-inside: avoid;
        }

        table.lineas tbody td {
            padding: 11px 12px;
            border-bottom: 1px solid #E3E6E9;
            font-size: 12.5px;
        }

        table.lineas .numero {
            text-align: right;
        }

        table.lineas tr.venta-sep td {
            background-color: #F2F0ED;
            font-size: 10.5px;
            font-weight: bold;
            color: #566573;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 7px 12px;
        }

        /* Filas de relleno -sin esto, un documento de 1-2 productos deja
           la tabla "flotando" arriba con toda la hoja vacía debajo. */
        table.lineas tbody tr.fila-vacia td {
            color: transparent;
        }

        .total-wrap {
            width: 100%;
            margin: 18px 0 30px;
        }

        .total-wrap table {
            width: 210px;
            float: right;
            border-collapse: collapse;
        }

        .total-wrap td {
            padding: 9px 14px;
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
            font-size: 14px;
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
                <h3>Comprador</h3>
                @if ($documento->comprador)
                    <p class="nombre">{{ $documento->comprador->nombre }}</p>
                    <p>{{ $documento->comprador->tipo_documento }} {{ $documento->comprador->numero_documento }}</p>
                @else
                    <p class="nombre">Consumidor final</p>
                @endif
                <p>{{ $documento->fecha_emision->locale('es')->translatedFormat('d \d\e F \d\e Y, g:i a') }}</p>
            </td>
        </tr>
    </table>

    {{-- Máximo 12 filas por hoja -en vez de dejar que dompdf corte la
         tabla donde le quede, se arma una tabla COMPLETA por cada bloque
         de 12, cada una con su propio borde entero, forzando el salto de
         página entre una y otra (mismo criterio que el recibo de Ventas,
         ver recibo-pdf.blade.php). Acá cada "fila" puede ser un producto
         o un separador "Venta #N" (cuando el documento agrupa varias
         ventas, en una consolidada) -se aplanan las dos cosas en una
         sola lista antes de repartir por páginas. --}}
    @php
        $variasVentas = $documento->ventas->count() > 1;
        $filas = collect();
        foreach ($documento->ventas as $venta) {
            if ($variasVentas) {
                $filas->push(['tipo' => 'venta_sep', 'venta' => $venta]);
            }
            foreach ($venta->detallesAgrupados() as $detalle) {
                $filas->push(['tipo' => 'producto', 'detalle' => $detalle]);
            }
        }
        $paginas = $filas->chunk(12);
        $esUnaSolaPagina = $paginas->count() === 1;
    @endphp
    @foreach ($paginas as $pagina)
        <div class="lineas-wrap {{ ! $loop->last ? 'salto-pagina' : '' }}">
            <table class="lineas">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="numero">Cant.</th>
                        <th class="numero">Precio unit.</th>
                        <th class="numero">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pagina as $fila)
                        @if ($fila['tipo'] === 'venta_sep')
                            <tr class="venta-sep">
                                <td colspan="4">Venta #{{ $fila['venta']->id }} — {{ $fila['venta']->fecha->locale('es')->translatedFormat('d M Y, g:i a') }}</td>
                            </tr>
                        @else
                            @php($detalle = $fila['detalle'])
                            <tr>
                                <td>{{ $detalle->producto->nombre }}</td>
                                <td class="numero">{{ $detalle->cantidad }}</td>
                                <td class="numero">${{ number_format($detalle->precio_unitario_venta, 0, ',', '.') }}</td>
                                <td class="numero">${{ number_format($detalle->cantidad * $detalle->precio_unitario_venta, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    @endforeach
                    @if ($loop->last && $esUnaSolaPagina)
                        @for ($i = $pagina->count(); $i < 8; $i++)
                            <tr class="fila-vacia">
                                <td>&nbsp;</td>
                                <td class="numero">&nbsp;</td>
                                <td class="numero">&nbsp;</td>
                                <td class="numero">&nbsp;</td>
                            </tr>
                        @endfor
                    @endif
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="total-wrap">
        <table>
            <tr>
                <td class="label">Total</td>
                <td class="numero">${{ number_format($documento->valor_total, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="cierre">
        <div class="dian">
            <h3>Verificación DIAN</h3>
            <p class="dian__cufe">CUFE: {{ $documento->cufe }}</p>
        </div>

        <div class="pie">
            {{-- Honesto con el estado real del proyecto: el CUFE de acá es
                 simulado (no hay integración real con Factus/DIAN todavía,
                 ver FacturacionController::store()) -este aviso evita que
                 el PDF se pueda confundir con una factura electrónica
                 válida de verdad si alguien lo llega a compartir. --}}
            <p class="pie__legal">
                Documento generado por Stockly. La integración con la DIAN (Factus) está pendiente de conexión
                -el CUFE mostrado es simulado y este documento todavía no tiene validez fiscal ante la DIAN.
            </p>
        </div>
    </div>
</body>
</html>
