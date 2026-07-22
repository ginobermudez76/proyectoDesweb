<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación 2FA</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 20px;
            color: #1a1f36;
        }
        .email-container {
            max-width: 520px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 28px 24px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }
        .content {
            padding: 32px 24px;
            text-align: center;
        }
        .code-box {
            background: #f8fafc;
            border: 2px dashed #f97316;
            border-radius: 12px;
            padding: 18px;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #f97316;
            display: inline-block;
            margin: 24px 0;
        }
        .footer {
            background: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Código de Verificación 2FA</h1>
        </div>
        <div class="content">
            <p style="margin: 0; font-size: 15px;">Hola <strong>{{ $usuario->nombres }}</strong>,</p>
            <p style="font-size: 14px; color: #475569; margin-top: 8px;">Usa el siguiente código de verificación para completar el inicio de sesión en tu cuenta:</p>
            
            <div class="code-box">{{ $codigo }}</div>

            <p style="font-size: 13px; color: #64748b; margin: 0;">Este código vence en <strong>5 minutos</strong> y es de un solo uso.</p>
        </div>
        <div class="footer">
            <div style="font-weight: 600; color: #475569; margin-bottom: 6px;">Este es un correo electrónico generado automáticamente. Por favor, no responda a este mensaje.</div>
            <div>&copy; {{ date('Y') }} Sistema de Incidencias Urbi. Todos los derechos reservados.</div>
        </div>
    </div>
</body>
</html>
