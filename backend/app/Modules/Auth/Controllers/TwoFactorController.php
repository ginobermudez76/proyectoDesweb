<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\Codigo2FaMail;
use App\Modules\Auth\Entities\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TwoFactorController extends Controller
{
    /**
     * Inicia el proceso de vinculación de App Autenticadora (Google/Microsoft Authenticator).
     * Retorna el secreto temporal base32 y la URL/SVG del Código QR.
     */
    public function setupApp(Request $request)
    {
        /** @var Usuario $user */
        $user = $request->user();

        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $secretKey = $google2fa->generateSecretKey();

        // Guardar secreto temporal en Redis por 10 minutos
        Cache::store('redis')->put('2fa_setup_secret:' . $user->id, $secretKey, now()->addMinutes(10));

        $companyName = config('app.name', 'Urbi');
        $holder = $user->correo_electronico;

        // Generar otpauth URL
        $otpauthUrl = $google2fa->getQRCodeUrl($companyName, $holder, $secretKey);

        // Generar SVG Data URL con BaconQrCode
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(220),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new \BaconQrCode\Writer($renderer);
        $svgQr = $writer->writeString($otpauthUrl);
        $qrDataUrl = 'data:image/svg+xml;base64,' . base64_encode($svgQr);

        return response()->json([
            'secret' => $secretKey,
            'qr_code_url' => $qrDataUrl,
            'otpauth_url' => $otpauthUrl,
        ], 200);
    }

    /**
     * Confirma la vinculación del secreto con un código de 6 dígitos introducido por el usuario.
     */
    public function confirmApp(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        /** @var Usuario $user */
        $user = $request->user();

        $secretKey = Cache::store('redis')->get('2fa_setup_secret:' . $user->id);

        if (!$secretKey) {
            return response()->json(['message' => 'La sesión de configuración del QR ha expirado. Genera un nuevo código QR.'], 400);
        }

        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $valid = $google2fa->verifyKey($secretKey, $request->code);

        if (!$valid) {
            return response()->json(['message' => 'El código de 6 dígitos ingresado es incorrecto o venció.'], 422);
        }

        // Guardar el secreto encriptado y activar el método
        $user->aut_2fa_secret = Crypt::encryptString($secretKey);
        $user->aut_app_autenticacion = true;
        $user->save();

        Cache::store('redis')->forget('2fa_setup_secret:' . $user->id);
        Cache::store('redis')->forget('user_profile:' . $user->id);

        return response()->json(['message' => '¡App de Autenticación configurada e integrada con éxito!'], 200);
    }

    /**
     * Desactiva la App de Autenticación TOTP del usuario.
     */
    public function disableApp(Request $request)
    {
        /** @var Usuario $user */
        $user = $request->user();

        $user->aut_app_autenticacion = false;
        $user->aut_2fa_secret = null;
        $user->save();

        Cache::store('redis')->forget('user_profile:' . $user->id);

        return response()->json(['message' => 'App de Autenticación desactivada.'], 200);
    }

    /**
     * Activa o desactiva la Autenticación de Doble Factor por Email.
     */
    public function toggleEmail(Request $request)
    {
        $request->validate([
            'enable' => 'required|boolean',
        ]);

        /** @var Usuario $user */
        $user = $request->user();

        $user->aut_email = (bool) $request->enable;
        $user->save();

        Cache::store('redis')->forget('user_profile:' . $user->id);

        $statusMsg = $user->aut_email
            ? 'Autenticación de 2FA por Correo Electrónico activada.'
            : 'Autenticación de 2FA por Correo Electrónico desactivada.';

        return response()->json(['message' => $statusMsg, 'aut_email' => $user->aut_email], 200);
    }

    /**
     * Envía un código OTP por correo durante el proceso de verificación de 2FA (Paso 2 del login).
     */
    public function sendEmailCode(Request $request)
    {
        $request->validate([
            'pending_token' => 'required|string',
        ]);

        $pendingData = Cache::store('redis')->get('2fa_pending:' . $request->pending_token);

        if (!$pendingData) {
            return response()->json(['message' => 'La sesión de 2FA ha expirado. Inicia sesión nuevamente.'], 410);
        }

        $userId = $pendingData['usuario_id'];
        $user = Usuario::find($userId);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $code = sprintf('%06d', mt_rand(0, 999999));
        Cache::store('redis')->put('2fa_email_code:' . $request->pending_token, $code, now()->addMinutes(5));

        try {
            Mail::to($user->correo_electronico)->send(new Codigo2FaMail($user, $code));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error enviando código 2FA por email: ' . $e->getMessage());
            return response()->json(['message' => 'Error al enviar el correo de verificación.'], 500);
        }

        return response()->json(['message' => 'Código de 6 dígitos enviado a tu correo electrónico.'], 200);
    }

    /**
     * Valida el desafío del Paso 2 del Login (vía App o vía Email OTP) y emite el access_token final.
     */
    public function verifyChallenge(Request $request)
    {
        $request->validate([
            'pending_token' => 'required|string',
            'method' => 'required|string|in:app,email',
            'code' => 'required|string|size:6',
        ]);

        $pendingData = Cache::store('redis')->get('2fa_pending:' . $request->pending_token);

        if (!$pendingData) {
            return response()->json(['message' => 'La sesión de verificación 2FA ha expirado. Vuelve a iniciar sesión.'], 410);
        }

        $userId = $pendingData['usuario_id'];
        /** @var Usuario|null $user */
        $user = Usuario::with('roles.opciones')->find($userId);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $verified = false;

        if ($request->method === 'app') {
            if (!$user->aut_app_autenticacion || empty($user->aut_2fa_secret)) {
                return response()->json(['message' => 'No tienes configurada la App de Autenticación.'], 400);
            }

            try {
                $secretKey = Crypt::decryptString($user->aut_2fa_secret);
                $google2fa = new \PragmaRX\Google2FA\Google2FA();
                $verified = $google2fa->verifyKey($secretKey, $request->code);
            } catch (\Exception $e) {
                $verified = false;
            }
        } elseif ($request->method === 'email') {
            $savedCode = Cache::store('redis')->get('2fa_email_code:' . $request->pending_token);
            if ($savedCode && $savedCode === $request->code) {
                $verified = true;
                Cache::store('redis')->forget('2fa_email_code:' . $request->pending_token);
            }
        }

        if (!$verified) {
            return response()->json(['message' => 'El código de 6 dígitos ingresado es incorrecto o expiró.'], 422);
        }

        // Limpiar token de sesión pendiente en Redis
        Cache::store('redis')->forget('2fa_pending:' . $request->pending_token);

        // Generar token Bearer final de autenticación
        $token = Str::random(60);
        Cache::store('redis')->put('auth_token:' . $token, $user->id, now()->addDays(7));

        \App\Modules\Auth\Entities\HistorialSesion::create([
            'usuario_id'         => $user->uuid,
            'correo_electronico' => $user->correo_electronico,
            'accion'             => 'LOGIN_2FA_OK',
            'ip'                 => $request->ip(),
            'dispositivo'        => $request->userAgent(),
            'fecha_hora'         => now(),
        ]);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'usuario' => $user,
        ], 200);
    }
}
