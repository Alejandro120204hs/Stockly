<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Recibo venta #{{ $venta->id }}</title>
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

        /* El borde que encierra la tabla va en este div, no en <table>
           -dompdf no siempre pinta bien el borde de una tabla completa
           cuando se combina con border-collapse (se veía el borde
           izquierdo pero no el derecho/inferior en algunas filas). Un
           div normal no tiene ese problema. */
        .lineas-wrap {
            border: 1px solid #1E2D3D;
            margin-bottom: 4px;
        }

        /* Máximo 10 productos por hoja -cada bloque de 10 es una tabla
           COMPLETA con su propio borde entero (nunca partida a la mitad
           entre dos páginas), y esto fuerza que el siguiente bloque
           arranque en la página de al lado. */
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

        /* Filas de relleno -sin esto, una venta de 1 o 2 productos deja la
           tabla "flotando" arriba con toda la hoja vacía debajo. Se rellena
           hasta un mínimo de filas, igual que una factura impresa de
           verdad, que siempre trae renglones en blanco. */
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

        .pago {
            padding-top: 18px;
            border-top: 1px dashed #C9CDD2;
        }

        .pago h3 {
            margin: 0 0 10px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #566573;
        }

        .pago table {
            width: 100%;
        }

        .pago td {
            padding: 3px 0;
            font-size: 12px;
        }

        .pago td.label {
            color: #566573;
            width: 160px;
        }

        .pie {
            margin-top: 40px;
            padding-top: 14px;
            border-top: 1px solid #E3E6E9;
            text-align: center;
        }

        .pie__gracias {
            margin: 0 0 6px;
            font-family: 'Georgia', 'Times New Roman', serif;
            font-size: 15px;
            font-weight: bold;
            color: #4A7C6F;
        }

        .pie__contacto {
            margin: 0 0 10px;
            font-size: 11px;
            color: #566573;
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
                        <img src="{{ $logoDataUri }}" alt="{{ $venta->empresa->nombre_negocio }}">
                    @else
                        <span class="marca__logo-fallback">{{ $venta->empresa->nombre_negocio }}</span>
                    @endif
                </td>
                <td class="marca__tag">
                    <span>RECIBO #{{ $venta->id }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="info-grid">
        <tr>
            <td class="info-negocio">
                <h3>Negocio</h3>
                <p class="nombre">{{ $venta->empresa->nombre_negocio }}</p>
                @if ($venta->empresa->nit)
                    <p>NIT {{ $venta->empresa->nit }}{{ $venta->empresa->dv ? '-'.$venta->empresa->dv : '' }}</p>
                @endif
                @if ($venta->empresa->direccion)
                    <p>{{ $venta->empresa->direccion }}{{ $venta->empresa->ciudad ? ', '.$venta->empresa->ciudad : '' }}</p>
                @endif
            </td>
            <td class="info-cliente">
                <h3>Cliente</h3>
                @if ($venta->comprador)
                    <p class="nombre">{{ $venta->comprador->nombre }}</p>
                    <p>{{ $venta->comprador->tipo_documento }} {{ $venta->comprador->numero_documento }}</p>
                @else
                    <p class="nombre">Consumidor final</p>
                    <p>No se pidió factura a nombre propio</p>
                @endif
                <p>{{ $venta->fecha->locale('es')->translatedFormat('d \d\e F \d\e Y, g:i a') }}</p>
            </td>
        </tr>
    </table>

    {{-- Máximo 12 productos por hoja -en vez de dejar que dompdf corte la
         tabla donde le quede (y potencialmente rompa el borde que la
         encierra al partirse entre páginas), se arma una tabla COMPLETA
         por cada bloque de 12, cada una con su propio borde entero, y se
         fuerza el salto de página entre una y otra. Así el TOTAL siempre
         cae justo debajo del último producto, sea cual sea la página.

         Las filas de relleno (para que 1-2 productos no se vean flotando
         en una hoja vacía) SOLO tienen sentido cuando el recibo es de una
         sola hoja -si ya hubo que pasar a una segunda hoja, la venta ya
         demostró tener suficiente contenido, y rellenar la última hoja
         con cuadros vacíos se ve al revés de profesional. --}}
    @php
        $paginasProductos = $venta->detallesAgrupados()->chunk(12);
        $esUnaSolaPagina = $paginasProductos->count() === 1;
    @endphp
    @foreach ($paginasProductos as $pagina)
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
                    @foreach ($pagina as $detalle)
                        <tr>
                            <td>{{ $detalle->producto->nombre }}</td>
                            <td class="numero">{{ $detalle->cantidad }}</td>
                            <td class="numero">${{ number_format($detalle->precio_unitario_venta, 0, ',', '.') }}</td>
                            <td class="numero">${{ number_format($detalle->cantidad * $detalle->precio_unitario_venta, 0, ',', '.') }}</td>
                        </tr>
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
                <td class="numero">${{ number_format($venta->total, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    {{-- Pago + pie van juntos en un solo bloque -si una venta trae muchos
         productos y esto no alcanza a caber en la página 1, dompdf lo
         pasa COMPLETO a la página 2 en vez de partirlo (dejando el pie
         solo, huérfano, en una página aparte). --}}
    <div class="cierre">
        <div class="pago">
            <h3>Información de pago</h3>
            <table>
                <tr>
                    <td class="label">Método</td>
                    <td>{{ $venta->metodo_pago === 'efectivo' ? 'Efectivo' : 'Digital (Wompi)' }}</td>
                </tr>
                @if ($venta->pagoEfectivo)
                    <tr>
                        <td class="label">Monto recibido</td>
                        <td>${{ number_format($venta->pagoEfectivo->monto_recibido, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Cambio</td>
                        <td>${{ number_format($venta->pagoEfectivo->cambio, 0, ',', '.') }}</td>
                    </tr>
                @elseif ($venta->pagoPasarela)
                    <tr>
                        <td class="label">Estado del pago</td>
                        <td>{{ $venta->pagoPasarela->estado === 'confirmado' ? 'Confirmado por Wompi' : 'Pendiente de confirmación' }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <div class="pie">
            <p class="pie__gracias">¡Gracias por tu compra!</p>
            @if ($venta->empresa->telefono_contacto || $venta->empresa->correo_contacto)
                <p class="pie__contacto">
                    {{ collect([$venta->empresa->telefono_contacto, $venta->empresa->correo_contacto])->filter()->implode('  •  ') }}
                </p>
            @endif
            <p class="pie__legal">Este recibo es un comprobante interno de {{ $venta->empresa->nombre_negocio }} y no tiene validez fiscal ante la DIAN.</p>
        </div>
    </div>
</body>
</html>
