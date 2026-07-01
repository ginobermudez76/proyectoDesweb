<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Modules\Auth\Entities\Usuario;
use Symfony\Component\HttpFoundation\Response;

class CheckRolePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['message' => 'No autorizado. Token ausente.'], 401);
        }

       
        $userId = Cache::store('redis')->get('auth_token:' . $token);
        
        if (!$userId) {
            return response()->json(['message' => 'Sesión expirada o inválida.'], 401);
        }

        $usuario = Usuario::find($userId);
        
        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado en la base de datos.'], 401);
        }

        
        Auth::login($usuario);

       
        if ($request->is('api/logout') || $request->is('api/user')) {
            return $next($request);
        }

        
        $usuario->load('roles.opciones.endpoints');
        $metodoActual = $request->method();
        $tienePermiso = false;

        foreach ($usuario->roles as $rol) {
            if ($rol->deleted) continue;

            foreach ($rol->opciones as $opcion) {
                if ($opcion->deleted) continue;

                foreach ($opcion->endpoints as $endpoint) {
                    if ($endpoint->deleted || !$endpoint->rbac_enabled) continue;

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
            return response()->json([
                'message' => 'Acceso denegado. Tu rol no tiene permisos para este endpoint.'
            ], 403);
        }

        return $next($request);
    }
}