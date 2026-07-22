<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitación al Sistema</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 20px;
            color: #1a1f36;
        }
        .email-container {
            max-width: 560px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 32px 24px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .content {
            padding: 32px 24px;
            line-height: 1.6;
            font-size: 15px;
        }
        .content p {
            margin: 0 0 16px 0;
        }
        .highlight-box {
            background: #f8fafc;
            border-left: 4px solid #f97316;
            padding: 16px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 14px;
        }
        .btn-container {
            text-align: center;
            margin: 32px 0;
        }
        .btn {
            display: inline-block;
            background-color: #f97316;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            box-shadow: 0 4px 10px rgba(249, 115, 22, 0.25);
        }
        .footer {
            background: #f8fafc;
            padding: 24px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
        .disclaimer {
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Sistema de Gestión de Incidencias</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{{ $usuario->nombres }} {{ $usuario->apellidos }}</strong>,</p>
            
            <p>Has sido invitado a formar parte de nuestra plataforma con el rol de <strong>{{ $nombreRol }}</strong>.</p>
            
            <div class="highlight-box">
                @if($esRecordatorio)
                    <p style="margin:0;"><strong>📌 Recordatorio de invitación:</strong> Esta invitación vence el <strong>{{ $fechaExpiracion }}</strong> (tiempo restante: {{ $tiempoRestante }}).</p>
                @else
                    <p style="margin:0;"><strong>⏳ Plazo de validez:</strong> Tienes hasta el <strong>{{ $fechaExpiracion }}</strong> (7 días a partir de hoy) para aceptar la invitación y establecer tu contraseña personal.</p>
                @endif
            </div>

            <p>Para activar tu cuenta y configurar tu contraseña de acceso, haz clic en el siguiente botón:</p>

            <div class="btn-container">
                <a href="{{ $urlActivacion }}" class="btn" target="_blank">Aceptar Invitación y Crear Contraseña</a>
            </div>

            <p style="font-size:13px; color:#64748b;">Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:<br>
            <a href="{{ $urlActivacion }}" style="color:#f97316; word-break:break-all;">{{ $urlActivacion }}</a></p>
        </div>
        <div class="footer">
            <div class="disclaimer">Este es un correo electrónico generado automáticamente. Por favor, no responda a este mensaje.</div>
            <div>&copy; {{ date('Y') }} Sistema de Gestión de Incidencias. Todos los derechos reservados.</div>
        </div>
    </div>
</body>
</html>
