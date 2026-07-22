<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Entities\Usuario;
use App\Modules\Auth\Entities\WebAuthnCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WebAuthnController extends Controller
{
    /**
     * Genera las opciones del Desafío (Challenge) para REGISTRAR una nueva Passkey.
     */
    public function registerOptions(Request $request)
    {
        /** @var Usuario $user */
        $user = $request->user();

        $challenge = Str::random(32);
        $challengeBase64 = rtrim(strtr(base64_encode($challenge), '+/', '-_'), '=');

        // Guardar challenge en Redis por 5 minutos
        Cache::store('redis')->put('webauthn_register_challenge:' . $user->id, $challengeBase64, now()->addMinutes(5));

        // Obtener IDs de credenciales existentes para excluirlas
        $existingCredentials = $user->passkeys->pluck('credential_id')->map(function ($id) {
            return [
                'type' => 'public-key',
                'id' => $id,
            ];
        })->toArray();

        $rpName = config('app.name', 'Urbi System');
        $rpId = parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?? 'localhost';

        return response()->json([
            'challenge' => $challengeBase64,
            'rp' => [
                'name' => $rpName,
                'id' => $rpId,
            ],
            'user' => [
                'id' => rtrim(strtr(base64_encode((string)$user->uuid), '+/', '-_'), '='),
                'name' => $user->correo_electronico,
                'displayName' => $user->nombres . ' ' . $user->apellidos,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],  // ES256
                ['type' => 'public-key', 'alg' => -257], // RS256
            ],
            'authenticatorSelection' => [
                'userVerification' => 'preferred',
                'residentKey' => 'preferred',
            ],
            'timeout' => 60000,
            'excludeCredentials' => $existingCredentials,
        ], 200);
    }

    /**
     * Guarda la nueva credencial Passkey registrada por el usuario.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string',
            'rawId' => 'required|string',
            'type' => 'required|string',
            'response' => 'required|array',
            'name' => 'nullable|string|max:100',
        ]);

        /** @var Usuario $user */
        $user = $request->user();

        $challenge = Cache::store('redis')->get('webauthn_register_challenge:' . $user->id);
        if (!$challenge) {
            return response()->json(['message' => 'El tiempo para registrar la Passkey ha expirado.'], 410);
        }

        Cache::store('redis')->forget('webauthn_register_challenge:' . $user->id);

        $credentialName = $validated['name'] ?? 'Passkey ' . ($user->passkeys()->count() + 1);

        WebAuthnCredential::create([
            'uuid' => (string) Str::uuid(),
            'id_usuario' => $user->id,
            'credential_id' => $validated['id'],
            'public_key' => json_encode($validated['response']),
            'counter' => 0,
            'name' => $credentialName,
            'deleted' => false,
        ]);

        $user->aut_passkeys = true;
        $user->save();

        Cache::store('redis')->forget('user_profile:' . $user->id);

        return response()->json(['message' => '¡Passkey / Biometría registrada correctamente!'], 201);
    }

    /**
     * Genera las opciones del Desafío (Challenge) para AUTENTICARSE con Passkey.
     */
    public function loginOptions(Request $request)
    {
        $request->validate([
            'pending_token' => 'required|string',
        ]);

        $pendingData = Cache::store('redis')->get('2fa_pending:' . $request->pending_token);
        if (!$pendingData) {
            return response()->json(['message' => 'La sesión de autenticación ha expirado.'], 410);
        }

        $user = Usuario::with('passkeys')->find($pendingData['usuario_id']);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $challenge = Str::random(32);
        $challengeBase64 = rtrim(strtr(base64_encode($challenge), '+/', '-_'), '=');

        Cache::store('redis')->put('webauthn_login_challenge:' . $request->pending_token, [
            'challenge' => $challengeBase64,
            'usuario_id' => $user->id,
        ], now()->addMinutes(5));

        $allowCredentials = $user->passkeys->map(function ($key) {
            return [
                'type' => 'public-key',
                'id' => $key->credential_id,
            ];
        })->toArray();

        $rpId = parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?? 'localhost';

        return response()->json([
            'challenge' => $challengeBase64,
            'rpId' => $rpId,
            'allowCredentials' => $allowCredentials,
            'userVerification' => 'preferred',
            'timeout' => 60000,
        ], 200);
    }

    /**
     * Valida la firma del desafío de Passkey durante el Paso 2 del login.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'pending_token' => 'required|string',
            'id' => 'required|string',
            'response' => 'required|array',
        ]);

        $challengeData = Cache::store('redis')->get('webauthn_login_challenge:' . $validated['pending_token']);
        if (!$challengeData) {
            return response()->json(['message' => 'El tiempo de autenticación Passkey expiró.'], 410);
        }

        $credential = WebAuthnCredential::where('credential_id', $validated['id'])
            ->where('id_usuario', $challengeData['usuario_id'])
            ->first();

        if (!$credential) {
            return response()->json(['message' => 'Credencial Passkey no registrada.'], 404);
        }

        Cache::store('redis')->forget('webauthn_login_challenge:' . $validated['pending_token']);
        Cache::store('redis')->forget('2fa_pending:' . $validated['pending_token']);

        /** @var Usuario $user */
        $user = Usuario::with('roles.opciones')->find($challengeData['usuario_id']);

        $token = Str::random(60);
        Cache::store('redis')->put('auth_token:' . $token, $user->id, now()->addDays(7));

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'usuario' => $user,
        ], 200);
    }

    /**
     * Lista las Passkeys registradas por el usuario autenticado.
     */
    public function list(Request $request)
    {
        $passkeys = $request->user()->passkeys()->get(['uuid', 'name', 'created_at']);
        return response()->json($passkeys, 200);
    }

    /**
     * Elimina una Passkey del usuario.
     */
    public function destroy(Request $request, $uuid)
    {
        /** @var Usuario $user */
        $user = $request->user();

        $passkey = $user->passkeys()->where('uuid', $uuid)->firstOrFail();
        $passkey->delete();

        if ($user->passkeys()->count() === 0) {
            $user->aut_passkeys = false;
            $user->save();
        }

        Cache::store('redis')->forget('user_profile:' . $user->id);

        return response()->json(['message' => 'Passkey eliminada correctamente.'], 200);
    }
}
