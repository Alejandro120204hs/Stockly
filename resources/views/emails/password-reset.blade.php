<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recupera tu contraseña — Stockly</title>
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
                                Recuperación de contraseña
                            </p>
                            <h1 style="margin:0 0 16px 0; font-size:22px; line-height:1.3; color:#1E2D3D;">
                                Hola{{ isset($nombre) ? ', ' . $nombre : '' }} 👋
                            </h1>
                            <p style="margin:0 0 24px 0; font-size:15px; line-height:1.6; color:#3E4B58;">
                                Recibimos una solicitud para restablecer la contraseña de tu cuenta en Stockly.
                                Si fuiste tú, haz clic en el botón de abajo para elegir una nueva contraseña.
                            </p>
                        </td>
                    </tr>

                    <!-- Botón -->
                    <tr>
                        <td style="padding:0 32px 28px 32px;" align="center">
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="border-radius:10px; background-color:#4A7C6F;">
                                        <a href="{{ $url }}" target="_blank" style="display:block; padding:14px 24px; font-size:15px; font-weight:600; color:#FFFFFF; text-decoration:none; border-radius:10px;">
                                            Restablecer contraseña
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Nota de expiración -->
                    <tr>
                        <td style="padding:0 32px 32px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F2F0ED; border-radius:10px; border-left:3px solid #C9B99A;">
                                <tr>
                                    <td style="padding:14px 16px; font-size:13px; line-height:1.6; color:#5A6672;">
                                        Este enlace vence en {{ $count ?? 60 }} minutos. Si no solicitaste este cambio, puedes ignorar este correo -tu contraseña seguirá siendo la misma.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Enlace alterno -->
                    <tr>
                        <td style="padding:0 32px 32px 32px; border-top:1px solid #ECE9E3;">
                            <p style="margin:20px 0 0 0; font-size:12px; line-height:1.6; color:#8C9BAB;">
                                Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                                <a href="{{ $url }}" style="color:#4A7C6F; word-break:break-all;">{{ $url }}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:20px 32px; background-color:#F2F0ED;">
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
