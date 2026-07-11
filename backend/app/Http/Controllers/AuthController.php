<?php

namespace App\Http\Controllers;

use App\Modules\Auth\Entities\HistorialSesion;
use App\Modules\Auth\Entities\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'correo_electronico' => 'required|email',
            'password' => 'required',
        ]);

        $usuario = Usuario::where('correo_electronico', $request->correo_electronico)->first();

        if (!$usuario || !Hash::check($request->password, $usuario->password_hash)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        if (!$usuario->activo) {
            return response()->json(['message' => 'Cuenta inactiva. Contacta al administrador.'], 403);
        }

        $token = Str::random(60);

        Cache::store('redis')->put('auth_token:'.$token, $usuario->id, now()->addMinutes(120));

        HistorialSesion::create([
            'usuario_id' => $usuario->id,
            'correo_electronico' => $usuario->correo_electronico,
            'accion' => 'LOGIN',
            'ip' => $request->ip(),
            'dispositivo' => $request->userAgent(),
            'fecha_hora' => now(),
        ]);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'usuario' => $usuario->load('roles'),
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
                    'usuario_id' => $usuarioId,
                    'correo_electronico' => $usuario ? $usuario->correo_electronico : 'Desconocido',
                    'accion' => 'LOGOUT',
                    'ip' => $request->ip(),
                    'dispositivo' => $request->userAgent(),
                    'fecha_hora' => now(),
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
}
