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
    /** Routes that bypass authentication entirely. */
    private const PUBLIC_ROUTES = [
        'api/register',
        'api/documentos/tipos',
        'api/logs/unauthorized',
    ];

    /** Routes that authenticated users can access without RBAC check. */
    private const AUTHENTICATED_FREE_ROUTES = [
        'api/logout',
        'api/user',
        'api/notificaciones',
        'api/notificaciones/*',
        'api/usuarios/sesiones',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        return $this->authorizeRequest($request, $next);
    }

    private function authorizeRequest(Request $request, Closure $next): Response
    {
        if ($this->isPublicRoute($request)) {
            return $next($request);
        }

        $token = $request->bearerToken();
        if (!$token) {
            return $this->denyUnauthenticated($request, 'TOKEN_AUSENTE', 'Petición API sin token Bearer.', 'No autorizado. Token ausente.');
        }

        $userId = Cache::store('redis')->get('auth_token:' . $token);
        if (!$userId) {
            return $this->denyUnauthenticated($request, 'TOKEN_EXPIRADO', 'Token inválido o sesión expirada en Redis.', 'Sesión expirada o inválida.');
        }

        $usuario = $this->loadUserFromCache($userId);
        if (!$usuario instanceof Usuario) {
            return $this->denyUnauthenticated($request, 'USUARIO_INEXISTENTE', "ID de usuario {$userId} en token no encontrado en base de datos.", 'Usuario no encontrado en la base de datos.');
        }

        Auth::login($usuario);

        if ($this->isAuthenticatedFreeRoute($request)) {
            return $next($request);
        }

        if (!$this->userHasPermission($usuario, $request)) {
            return $this->denyRbac($request, $usuario);
        }

        return $next($request);
    }

    private function isPublicRoute(Request $request): bool
    {
        foreach (self::PUBLIC_ROUTES as $route) {
            if ($request->is($route)) {
                return true;
            }
        }
        return $request->is('api/ubicaciones/*');
    }

    private function isAuthenticatedFreeRoute(Request $request): bool
    {
        foreach (self::AUTHENTICATED_FREE_ROUTES as $route) {
            if ($request->is($route)) {
                return true;
            }
        }
        return false;
    }

    private function loadUserFromCache(string $userId): ?Usuario
    {
        return Cache::store('redis')->remember('user_profile:' . $userId, now()->addMinutes(10), function () use ($userId) {
            $user = Usuario::find($userId);
            if ($user) {
                $user->load('roles.opciones.endpoints');
            }
            return $user;
        });
    }

    private function userHasPermission(Usuario $usuario, Request $request): bool
    {
        $metodoActual = $request->method();

        foreach ($usuario->roles as $rol) {
            if ($rol->deleted) {
                continue;
            }
            foreach ($rol->opciones as $opcion) {
                if ($opcion->deleted) {
                    continue;
                }
                if ($this->endpointGrantsAccess($opcion, $metodoActual, $request)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function endpointGrantsAccess(Opcion $opcion, string $metodoActual, Request $request): bool
    {
        foreach ($opcion->endpoints as $endpoint) {
            if ($endpoint->deleted || !$endpoint->rbac_enabled) {
                continue;
            }

            $metodoCoincide = strtoupper($endpoint->metodo) === strtoupper($metodoActual)
                || strtoupper($endpoint->metodo) === 'ANY';

            if (!$metodoCoincide || !$request->is($endpoint->url)) {
                continue;
            }

            $esGet        = strtoupper($metodoActual) === 'GET';
            $tieneLectura = filter_var($opcion->pivot->lectura ?? false, FILTER_VALIDATE_BOOLEAN);
            $tieneEscritura = filter_var($opcion->pivot->escritura ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($esGet && $tieneLectura) {
                return true;
            }
            if (!$esGet && $tieneEscritura) {
                return true;
            }
        }

        return false;
    }

    private function logAccess(Request $request, ?string $uuid, ?string $correo, string $rol, string $tipo, string $detalle): void
    {
        AccesoNoAutorizado::create([
            'usuario_uuid'       => $uuid,
            'correo_electronico' => $correo,
            'rol'                => $rol,
            'ip'                 => $request->ip(),
            'user_agent'         => $request->userAgent(),
            'metodo'             => $request->method(),
            'url'                => $request->path(),
            'tipo_violacion'     => $tipo,
            'detalle'            => $detalle,
            'fecha_hora'         => now(),
        ]);
    }

    private function denyUnauthenticated(Request $request, string $tipo, string $detalle, string $message): Response
    {
        $this->logAccess($request, null, null, 'SIN_AUTENTICAR', $tipo, $detalle);
        return response()->json(['message' => $message], 401);
    }

    private function denyRbac(Request $request, Usuario $usuario): Response
    {
        /** @var Rol|null $rol */
        $rol = $usuario->roles->first();
        $this->logAccess(
            $request,
            $usuario->uuid,
            $usuario->correo_electronico,
            $rol ? $rol->codigo : 'SIN_ROL',
            'RBAC',
            'Rol sin permiso para acceder al endpoint.'
        );
        return response()->json(['message' => 'Acceso denegado. Tu rol no tiene permisos para este endpoint.'], 403);
    }
}
