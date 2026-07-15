<?php

namespace App\Http\Controllers;

use App\Modules\Auth\Entities\HistorialSesion;
use App\Modules\Auth\Entities\IntentoLoginFallido;
use App\Modules\Auth\Entities\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /** Número máximo de intentos fallidos antes del bloqueo */
    private const MAX_FAILS = 5;

    /** Segundos de bloqueo tras alcanzar MAX_FAILS */
    private const LOCKOUT_TTL = 300; // 5 minutos

    public function login(Request $request)
    {
        $request->validate([
            'correo_electronico' => 'required|email',
            'password'           => 'required',
        ]);

        $ip    = $request->ip();
        $email = strtolower($request->correo_electronico);

        $keyIp    = "login_fails:ip:{$ip}";
        $keyEmail = "login_fails:email:{$email}";

        // ── 1. Verificar si hay un bloqueo activo ──────────────────────────
        $failsIp    = (int) Cache::store('redis')->get($keyIp, 0);
        $failsEmail = (int) Cache::store('redis')->get($keyEmail, 0);

        if ($failsIp >= self::MAX_FAILS || $failsEmail >= self::MAX_FAILS) {
            // Obtener TTL restante directamente desde la conexión Redis subyacente
            $prefix    = config('cache.prefix', '');
            $blockedKey = $failsIp >= self::MAX_FAILS ? $keyIp : $keyEmail;
            $ttl        = Cache::store('redis')->getRedis()->ttl($prefix.$blockedKey);
            $ttl        = ($ttl && $ttl > 0) ? $ttl : self::LOCKOUT_TTL;

            return response()->json([
                'message'             => 'Cuenta bloqueada por múltiples intentos fallidos. '
                                        .'Intenta de nuevo en '.ceil($ttl / 60).' minuto(s).',
                'retry_after_seconds' => $ttl,
            ], 429);
        }

        // ── 2. Validar credenciales ────────────────────────────────────────
        $usuario = Usuario::where('correo_electronico', $email)->first();

        if (!$usuario || !Hash::check($request->password, $usuario->password_hash)) {

            // Incrementar contadores usando get+put para mantener TTL de 5 min
            $numIp    = (int) Cache::store('redis')->get($keyIp, 0) + 1;
            $numEmail = (int) Cache::store('redis')->get($keyEmail, 0) + 1;
            Cache::store('redis')->put($keyIp,    $numIp,    self::LOCKOUT_TTL);
            Cache::store('redis')->put($keyEmail, $numEmail, self::LOCKOUT_TTL);

            $intento   = max($numIp, $numEmail);
            $bloqueado = $intento >= self::MAX_FAILS;
            $remaining = max(0, self::MAX_FAILS - $intento);

            // ── Registrar en MongoDB ──────────────────────────────────────
            IntentoLoginFallido::create([
                'correo_electronico' => $email,
                'ip'                 => $ip,
                'user_agent'         => $request->userAgent(),
                'intento_numero'     => $intento,
                'bloqueado'          => $bloqueado,
                'fecha_hora'         => now(),
            ]);

            return response()->json([
                'message'             => $bloqueado
                    ? 'Cuenta bloqueada. Intenta de nuevo en '.self::LOCKOUT_TTL / 60 .' minuto(s).'
                    : "Credenciales incorrectas. Te quedan {$remaining} intento(s) antes del bloqueo.",
                'attempts_remaining'  => $remaining,
                'retry_after_seconds' => $bloqueado ? self::LOCKOUT_TTL : null,
            ], 401);
        }

        // ── 3. Verificar cuenta activa ─────────────────────────────────────
        if (!$usuario->activo) {
            return response()->json(['message' => 'Cuenta inactiva. Contacta al administrador.'], 403);
        }

        // ── 4. Login exitoso: limpiar contadores de Redis ─────────────────
        Cache::store('redis')->forget($keyIp);
        Cache::store('redis')->forget($keyEmail);

        $token = Str::random(60);
        Cache::store('redis')->put('auth_token:'.$token, $usuario->id, now()->addDays(7));

        HistorialSesion::create([
            'usuario_id'         => $usuario->uuid,
            'correo_electronico' => $usuario->correo_electronico,
            'accion'             => 'LOGIN',
            'ip'                 => $ip,
            'dispositivo'        => $request->userAgent(),
            'fecha_hora'         => now(),
        ]);

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'usuario'      => $usuario->load('roles.opciones'),
        ], 200);
    }

    public function logout(Request $request)
    {
        $token = $request->bearerToken();

        if ($token) {
            $usuarioId = Cache::store('redis')->get('auth_token:'.$token);

            if ($usuarioId) {
                $usuario = Usuario::find($usuarioId);

                HistorialSesion::create([
                    'usuario_id'         => $usuarioId,
                    'correo_electronico' => $usuario ? $usuario->correo_electronico : 'Desconocido',
                    'accion'             => 'LOGOUT',
                    'ip'                 => $request->ip(),
                    'dispositivo'        => $request->userAgent(),
                    'fecha_hora'         => now(),
                ]);

                // Invalidar la caché de permisos de Redis al cerrar sesión
                Cache::store('redis')->forget('user_profile:'.$usuarioId);
            }

            Cache::store('redis')->forget('auth_token:'.$token);
        }

        return response()->json([
            'message' => 'Sesión cerrada exitosamente en Redis. Token destruido.',
        ], 200);
    }

    public function logUnauthorizedAccess(Request $request)
    {
        $request->validate([
            'tipo_violacion' => 'required|string',
            'url'            => 'required|string',
            'detalle'        => 'required|string',
            'metodo'         => 'nullable|string',
        ]);

        $token = $request->bearerToken();
        $usuario = null;

        if ($token) {
            $userId = Cache::store('redis')->get('auth_token:'.$token);
            if ($userId) {
                $usuario = Usuario::find($userId);
            }
        }

        \App\Modules\Auth\Entities\AccesoNoAutorizado::create([
            'usuario_uuid'       => $usuario?->uuid,
            'correo_electronico' => $usuario?->correo_electronico,
            'rol'                => $usuario?->roles->first()?->codigo ?? 'SIN_AUTENTICAR',
            'ip'                 => $request->ip(),
            'user_agent'         => $request->userAgent(),
            'metodo'             => $request->input('metodo', 'GET'),
            'url'                => $request->input('url'),
            'tipo_violacion'     => $request->input('tipo_violacion'),
            'detalle'            => $request->input('detalle'),
            'fecha_hora'         => now(),
        ]);

        return response()->json(['message' => 'Acceso no autorizado registrado en MongoDB.'], 201);
    }
}
