<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Entities\Rol;
use App\Modules\Auth\Entities\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsuarioController extends Controller
{
    public function index()
    {
        // Obtener todos los usuarios con sus roles
        $usuarios = Usuario::with('roles')->get();

        return response()->json($usuarios, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombres' => 'required|string|max:50',
            'apellidos' => 'required|string|max:50',
            'correo_electronico' => 'required|email|max:255|unique:usuario,correo_electronico',
            'password' => 'required|string|min:8',
            'rol_codigo' => 'required|string|exists:rol,codigo',
            'nombre_usuario' => 'nullable|string|max:50|unique:usuario,nombre_usuario',
        ]);

        // Auto-generar nombre_usuario si no se proporciona
        $nombreUsuario = $validated['nombre_usuario'] ?? null;
        if (empty($nombreUsuario)) {
            $base = Str::slug($validated['nombres'].'.'.$validated['apellidos'], '.');
            $nombreUsuario = $base;
            $counter = 1;
            // Evitar colisiones de nombre_usuario único
            while (Usuario::withoutGlobalScopes()->where('nombre_usuario', $nombreUsuario)->exists()) {
                $nombreUsuario = $base.$counter;
                $counter++;
            }
        }

        // Crear usuario
        $usuario = new Usuario;
        $usuario->nombre_usuario = $nombreUsuario;
        $usuario->correo_electronico = $validated['correo_electronico'];
        $usuario->password_hash = Hash::make($validated['password']);
        $usuario->nombres = $validated['nombres'];
        $usuario->apellidos = $validated['apellidos'];
        $usuario->activo = true;
        $usuario->deleted = false;
        $usuario->save();

        // Mapear rol
        $rol = Rol::where('codigo', $validated['rol_codigo'])->firstOrFail();
        $usuario->roles()->attach($rol->id, [
            'uuid' => (string) Str::uuid(),
            'deleted' => false,
            'created_at' => now(),
        ]);

        // Invalidar caché de Redis de este usuario (aunque es nuevo, por seguridad)
        Cache::store('redis')->forget('user_profile:'.$usuario->id);

        return response()->json($usuario->load('roles'), 201);
    }

    public function update(Request $request, $uuid)
    {
        $usuario = Usuario::where('uuid', $uuid)->firstOrFail();
 
        $validated = $request->validate([
            'nombres' => 'required|string|max:50',
            'apellidos' => 'required|string|max:50',
            'correo_electronico' => 'required|email|max:255|unique:usuario,correo_electronico,'.$usuario->id,
            'nombre_usuario' => 'nullable|string|max:50|unique:usuario,nombre_usuario,'.$usuario->id,
            'rol_codigo' => 'required|string|exists:rol,codigo',
            'password' => 'nullable|string|min:8',
        ]);
 
        $usuario->nombres = $validated['nombres'];
        $usuario->apellidos = $validated['apellidos'];
        $usuario->correo_electronico = $validated['correo_electronico'];
 
        if (!empty($validated['nombre_usuario'])) {
            $usuario->nombre_usuario = $validated['nombre_usuario'];
        }
 
        if (!empty($validated['password'])) {
            $usuario->password_hash = Hash::make($validated['password']);
        }
 
        $usuario->save();
 
        $rol = Rol::where('codigo', $validated['rol_codigo'])->firstOrFail();
        $usuario->roles()->sync([
            $rol->id => [
                'uuid' => (string) Str::uuid(),
                'deleted' => false,
                'created_at' => now(),
            ],
        ]);
 
        Cache::store('redis')->forget('user_profile:'.$usuario->id);
 
        return response()->json($usuario->load('roles'), 200);
    }
 
    public function toggleActivo($uuid)
    {
        $usuario = Usuario::where('uuid', $uuid)->firstOrFail();
        $usuario->activo = !$usuario->activo;
        $usuario->save();
 
        Cache::store('redis')->forget('user_profile:'.$usuario->id);
 
        return response()->json($usuario->load('roles'), 200);
    }

    public function roles()
    {
        return response()->json(Rol::all(), 200);
    }
}
