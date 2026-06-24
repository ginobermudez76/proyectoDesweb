<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRolePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Obtenemos al usuario autenticado por Sanctum
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json(['message' => 'No autorizado. Token inválido o ausente.'], 401);
        }

        // 2. Cargamos sus roles y las opciones de esos roles de golpe (Eager Loading)
        // Esto evita hacer múltiples consultas a la base de datos (problema N+1)
        $usuario->load('roles.opciones');

        $metodoActual = $request->method(); // GET, POST, PUT, DELETE, etc.
        $tienePermiso = false;

        // 3. Recorremos los roles y opciones buscando un match
        foreach ($usuario->roles as $rol) {
            foreach ($rol->opciones as $opcion) {
                // Comparamos el método HTTP (ignorando mayúsculas/minúsculas)
                $metodoCoincide = strtoupper($opcion->metodo_http) === strtoupper($metodoActual) || strtoupper($opcion->metodo_http) === 'ANY';
                
                // Comparamos la ruta. El método $request->is() entiende comodines '*' 
                // Por lo que si en BD guardas 'api/incidencias/*', validará 'api/incidencias/1'
                $rutaCoincide = $request->is($opcion->ruta_enlace);

                if ($metodoCoincide && $rutaCoincide) {
                    $tienePermiso = true;
                    break 2; // Si encuentra un permiso válido, rompe los dos bucles por eficiencia
                }
            }
        }

        // 4. Si después de revisar todo no hay permiso, rebotamos la petición
        if (!$tienePermiso) {
            return response()->json([
                'message' => 'Acceso denegado. Tu rol no tiene permisos para esta acción.',
                'debug_info' => [ // Puedes quitar este debug en producción
                    'ruta_solicitada' => $request->path(),
                    'metodo_solicitado' => $metodoActual
                ]
            ], 403);
        }

        // 5. Si tiene permiso, dejamos que la petición continúe hacia el Controlador
        return $next($request);
    }
}