<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nuevo pago reportado — Stockly</title>
</head>
<body style="margin:0; padding:0; background-color:#F2F0ED; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F2F0ED; padding:40px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="max-width:480px; width:100%; background-color:#FFFFFF; border-radius:16px; overflow:hidden; box-shadow:0 12px 32px rgba(30,45,61,0.10);">

                    <!-- Encabezado -->
                    <tr>
                        <td style="background-color:#1E2D3D; padding:28px 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <svg width="26" height="26" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;">
                                            <path d="M16 3 27 9v14L16 29 5 23V9Z" stroke="#C9B99A" stroke-width="1.8" stroke-linejoin="round"/>
                                            <path d="M5 9 16 15 27 9M16 15v14" stroke="#4A7C6F" stroke-width="1.8" stroke-linejoin="round"/>
                                        </svg>
                                    </td>
                                    <td style="vertical-align:middle; padding-left:10px;">
                                        <span style="font-size:18px; font-weight:600; color:#FFFFFF; letter-spacing:0.02em;">Stockly</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Cuerpo -->
                    <tr>
                        <td style="padding:36px 32px 8px 32px;">
                            <p style="margin:0 0 4px 0; font-size:13px; font-weight:600; letter-spacing:0.06em; text-transform:uppercase; color:#4A7C6F;">
                                Pago reportado
                            </p>
                            <h1 style="margin:0 0 16px 0; font-size:22px; line-height:1.3; color:#1E2D3D;">
                                {{ $empresa->nombre_negocio }} reportó un pago
                            </h1>
                            <p style="margin:0 0 24px 0; font-size:15px; line-height:1.6; color:#3E4B58;">
                                Subió un comprobante para el plan <strong>{{ $planLabel }}</strong>. Revísalo antes de activar la suscripción.
                            </p>
                        </td>
                    </tr>

                    <!-- Datos -->
                    <tr>
                        <td style="padding:0 32px 28px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F2F0ED; border-radius:10px;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px; color:#3E4B58;">
                                            <tr>
                                                <td style="padding:4px 0; color:#8C9BAB;">Empresa</td>
                                                <td style="padding:4px 0; text-align:right; font-weight:600; color:#1E2D3D;">{{ $empresa->nombre_negocio }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0; color:#8C9BAB;">Correo</td>
                                                <td style="padding:4px 0; text-align:right; font-weight:600; color:#1E2D3D;">{{ $empresa->correo_contacto ?? '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0; color:#8C9BAB;">Teléfono</td>
                                                <td style="padding:4px 0; text-align:right; font-weight:600; color:#1E2D3D;">{{ $empresa->telefonoContacto() ?? '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0; color:#8C9BAB;">Plan</td>
                                                <td style="padding:4px 0; text-align:right; font-weight:600; color:#1E2D3D;">{{ $planLabel }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0; color:#8C9BAB;">Monto</td>
                                                <td style="padding:4px 0; text-align:right; font-weight:600; color:#1E2D3D;">${{ number_format($pago->monto, 0, ',', '.') }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0; color:#8C9BAB;">Fecha</td>
                                                <td style="padding:4px 0; text-align:right; font-weight:600; color:#1E2D3D;">{{ $pago->fecha_pago->locale('es')->translatedFormat('d M Y, g:i a') }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Botón -->
                    <tr>
                        <td style="padding:0 32px 32px 32px;" align="center">
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="border-radius:10px; background-color:#4A7C6F;">
                                        <a href="{{ route('admin.pagos') }}" target="_blank" style="display:block; padding:14px 24px; font-size:15px; font-weight:600; color:#FFFFFF; text-decoration:none; border-radius:10px;">
                                            Revisar pago
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:20px 32px; background-color:#F2F0ED; border-top:1px solid #ECE9E3;">
                            <p style="margin:0; font-size:12px; color:#8C9BAB; text-align:center;">
                                Stockly · DevSec Solutions
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
