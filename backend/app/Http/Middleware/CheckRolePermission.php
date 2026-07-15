<?php

namespace App\Http\Middleware;

use App\Modules\Auth\Entities\AccesoNoAutorizado;
use App\Modules\Auth\Entities\Endpoint;
use App\Modules\Auth\Entities\Opcion;
use App\Modules\Auth\Entities\Rol;
use App\Modules\Auth\Entities\Usuario;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckRolePermission
{
    public function handle(Request $request, Closure $next): Response
    {

        $token = $request->bearerToken();

        if (!$token) {
            AccesoNoAutorizado::create([
                'usuario_uuid'       => null,
                'correo_electronico' => null,
                'rol'                => 'SIN_AUTENTICAR',
                'ip'                 => $request->ip(),
                'user_agent'         => $request->userAgent(),
                'metodo'             => $request->method(),
                'url'                => $request->path(),
                'tipo_violacion'     => 'TOKEN_AUSENTE',
                'detalle'            => 'Petición API sin token Bearer.',
                'fecha_hora'         => now(),
            ]);
            return response()->json(['message' => 'No autorizado. Token ausente.'], 401);
        }

        $userId = Cache::store('redis')->get('auth_token:'.$token);

        if (!$userId) {
            AccesoNoAutorizado::create([
                'usuario_uuid'       => null,
                'correo_electronico' => null,
                'rol'                => 'SIN_AUTENTICAR',
                'ip'                 => $request->ip(),
                'user_agent'         => $request->userAgent(),
                'metodo'             => $request->method(),
                'url'                => $request->path(),
                'tipo_violacion'     => 'TOKEN_EXPIRADO',
                'detalle'            => 'Token inválido o sesión expirada en Redis.',
                'fecha_hora'         => now(),
            ]);
            return response()->json(['message' => 'Sesión expirada o inválida.'], 401);
        }

        // Cachear el perfil del usuario junto con sus roles y permisos en Redis
        $usuario = Cache::store('redis')->remember('user_profile:'.$userId, now()->addMinutes(10), function () use ($userId) {
            $user = Usuario::find($userId);
            if ($user) {
                $user->load('roles.opciones.endpoints');
            }

            return $user;
        });

        if (!$usuario instanceof Usuario) {
            AccesoNoAutorizado::create([
                'usuario_uuid'       => null,
                'correo_electronico' => null,
                'rol'                => 'SIN_AUTENTICAR',
                'ip'                 => $request->ip(),
                'user_agent'         => $request->userAgent(),
                'metodo'             => $request->method(),
                'url'                => $request->path(),
                'tipo_violacion'     => 'USUARIO_INEXISTENTE',
                'detalle'            => "ID de usuario {$userId} en token no encontrado en base de datos.",
                'fecha_hora'         => now(),
            ]);
            return response()->json(['message' => 'Usuario no encontrado en la base de datos.'], 401);
        }

        Auth::login($usuario);

        if ($request->is('api/logout') || $request->is('api/user') || $request->is('api/notificaciones') || $request->is('api/notificaciones/*')) {
            return $next($request);
        }
        $metodoActual = $request->method();
        $tienePermiso = false;

        /** @var Rol $rol */
        foreach ($usuario->roles as $rol) {
            if ($rol->deleted) {
                continue;
            }

            /** @var Opcion $opcion */
            foreach ($rol->opciones as $opcion) {
                if ($opcion->deleted) {
                    continue;
                }

                /** @var Endpoint $endpoint */
                foreach ($opcion->endpoints as $endpoint) {
                    if ($endpoint->deleted || !$endpoint->rbac_enabled) {
                        continue;
                    }

                    $metodoCoincide = strtoupper($endpoint->metodo) === strtoupper($metodoActual) || strtoupper($endpoint->metodo) === 'ANY';
                    $rutaCoincide = $request->is($endpoint->url);

                    if ($metodoCoincide && $rutaCoincide) {
                        $tienePermiso = true;
                        break 3;
                    }
                }
            }
        }

        if (!$tienePermiso) {
            AccesoNoAutorizado::create([
                'usuario_uuid'       => $usuario->uuid,
                'correo_electronico' => $usuario->correo_electronico,
                'rol'                => $usuario->roles->first()?->codigo ?? 'SIN_ROL',
                'ip'                 => $request->ip(),
                'user_agent'         => $request->userAgent(),
                'metodo'             => $request->method(),
                'url'                => $request->path(),
                'tipo_violacion'     => 'RBAC',
                'detalle'            => 'Rol sin permiso para acceder al endpoint.',
                'fecha_hora'         => now(),
            ]);

            return response()->json([
                'message' => 'Acceso denegado. Tu rol no tiene permisos para este endpoint.',
            ], 403);
        }

        return $next($request);
    }
}
