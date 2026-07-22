<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Entities\Rol;
use App\Modules\Auth\Entities\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Mail\InvitacionUsuarioMail;
use Illuminate\Support\Facades\Mail;

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
            'password' => 'nullable|string|min:8',
            'rol_codigo' => 'required|string|exists:rol,codigo',
            'nombre_usuario' => 'nullable|string|max:50|unique:usuario,nombre_usuario',
        ]);

        $rol = Rol::where('codigo', $validated['rol_codigo'])->firstOrFail();
        $requiereInvitacion = (bool) $rol->req_token_invitacion;

        // Auto-generar nombre_usuario si no se proporciona
        $nombreUsuario = $validated['nombre_usuario'] ?? null;
        if (empty($nombreUsuario)) {
            $base = Str::slug($validated['nombres'].'.'.$validated['apellidos'], '.');
            $nombreUsuario = $base;
            $counter = 1;
            while (Usuario::withoutGlobalScopes()->where('nombre_usuario', $nombreUsuario)->exists()) {
                $nombreUsuario = $base.$counter;
                $counter++;
            }
        }

        // Si no requiere invitación y no viene contraseña, lanzar error de validación
        if (!$requiereInvitacion && empty($validated['password'])) {
            return response()->json([
                'message' => 'La contraseña es requerida para roles que no requieren invitación.',
            ], 422);
        }

        $passwordTemp = $validated['password'] ?? Str::random(16);

        // Crear usuario
        $usuario = new Usuario;
        $usuario->nombre_usuario = $nombreUsuario;
        $usuario->correo_electronico = $validated['correo_electronico'];
        $usuario->password_hash = Hash::make($passwordTemp);
        $usuario->nombres = $validated['nombres'];
        $usuario->apellidos = $validated['apellidos'];
        $usuario->deleted = false;

        if ($requiereInvitacion) {
            $usuario->activo = false;
            $usuario->token_invitacion = Str::random(64);
            $usuario->fecha_invitacion = now();
            $usuario->fecha_expiracion_invitacion = now()->addDays(7);
            $usuario->fecha_aceptacion = null;
        } else {
            $usuario->activo = true;
            $usuario->token_invitacion = null;
            $usuario->fecha_invitacion = null;
            $usuario->fecha_expiracion_invitacion = null;
            $usuario->fecha_aceptacion = now();
        }

        $usuario->save();

        // Mapear rol
        $usuario->roles()->attach($rol->id, [
            'uuid' => (string) Str::uuid(),
            'deleted' => false,
            'created_at' => now(),
        ]);

        if ($requiereInvitacion) {
            try {
                Mail::to($usuario->correo_electronico)->send(
                    new InvitacionUsuarioMail($usuario, $rol->nombre_rol, false)
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error enviando correo de invitación: '.$e->getMessage());
            }
        }

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

    public function reenviarInvitacion($uuid)
    {
        $usuario = Usuario::with('roles')->where('uuid', $uuid)->firstOrFail();

        if (!empty($usuario->fecha_aceptacion)) {
            return response()->json(['message' => 'La invitación ya fue aceptada por este usuario.'], 400);
        }

        $rol = $usuario->roles->first();
        $nombreRol = $rol ? $rol->nombre_rol : 'Usuario';

        $expirado = $usuario->fecha_expiracion_invitacion && now()->greaterThan($usuario->fecha_expiracion_invitacion);
        $esRecordatorio = !$expirado;

        if ($expirado || empty($usuario->token_invitacion)) {
            // Si está expirado o no tiene token, se genera uno nuevo y se reinicia la expiración a 7 días
            $usuario->token_invitacion = Str::random(64);
            $usuario->fecha_invitacion = now();
            $usuario->fecha_expiracion_invitacion = now()->addDays(7);
            $usuario->save();
        }

        try {
            Mail::to($usuario->correo_electronico)->send(
                new InvitacionUsuarioMail($usuario, $nombreRol, $esRecordatorio)
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error reenviando correo de invitación: '.$e->getMessage());
            return response()->json(['message' => 'No se pudo enviar el correo de invitación.'], 500);
        }

        $msg = $esRecordatorio
            ? 'Recordatorio de invitación enviado correctamente.'
            : 'Nueva invitación generada y enviada correctamente (validez renovada por 7 días).';

        return response()->json(['message' => $msg], 200);
    }

    public function validarInvitacion(Request $request)
    {
        $token = $request->query('token');
        if (empty($token)) {
            return response()->json(['message' => 'Token de invitación no proporcionado.'], 400);
        }

        $usuario = Usuario::where('token_invitacion', $token)->first();

        if (!$usuario) {
            return response()->json(['valido' => false, 'estado' => 'invalido', 'message' => 'Token de invitación no válido o inexistente.'], 404);
        }

        if (!empty($usuario->fecha_aceptacion)) {
            return response()->json(['valido' => false, 'estado' => 'aceptado', 'message' => 'Esta invitación ya fue aceptada previamente.'], 400);
        }

        if ($usuario->fecha_expiracion_invitacion && now()->greaterThan($usuario->fecha_expiracion_invitacion)) {
            return response()->json(['valido' => false, 'estado' => 'expirado', 'message' => 'La invitación ha expirado. Solicita un reenvío al administrador.'], 410);
        }

        return response()->json([
            'valido' => true,
            'estado' => 'pendiente',
            'usuario' => [
                'nombres' => $usuario->nombres,
                'apellidos' => $usuario->apellidos,
                'correo_electronico' => $usuario->correo_electronico,
            ],
        ], 200);
    }

    public function aceptarInvitacion(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $usuario = Usuario::where('token_invitacion', $validated['token'])->first();

        if (!$usuario) {
            return response()->json(['message' => 'Token de invitación no válido.'], 404);
        }

        if (!empty($usuario->fecha_aceptacion)) {
            return response()->json(['message' => 'Esta invitación ya fue aceptada.'], 400);
        }

        if ($usuario->fecha_expiracion_invitacion && now()->greaterThan($usuario->fecha_expiracion_invitacion)) {
            return response()->json(['message' => 'La invitación ha expirado. Por favor, solicita un reenvío de invitación.'], 410);
        }

        $usuario->password_hash = Hash::make($validated['password']);
        $usuario->activo = true;
        $usuario->fecha_aceptacion = now();
        $usuario->token_invitacion = null;
        $usuario->save();

        Cache::store('redis')->forget('user_profile:'.$usuario->id);

        return response()->json(['message' => '¡Cuenta activada y contraseña establecida con éxito! Ahora puedes iniciar sesión.'], 200);
    }

    /**
     * GET /api/usuarios/tecnicos
     * Lista usuarios activos con rol TECNICO para el modal de asignación.
     */
    public function tecnicos()
    {
        $rolTecnico = Rol::where('codigo', 'TECNICO')->first();
        if (!$rolTecnico) {
            return response()->json([], 200);
        }

        $tecnicos = Usuario::whereHas('roles', function ($q) use ($rolTecnico) {
            $q->where('rol.id', $rolTecnico->id)->where('rol_usuario.deleted', false);
        })->where('activo', true)
          ->orderBy('apellidos')
          ->get(['uuid', 'nombres', 'apellidos', 'nombre_usuario', 'correo_electronico']);

        return response()->json($tecnicos, 200);
    }

    public function roles()
    {
        return response()->json(Rol::all(), 200);
    }

    public function sesiones(Request $request)
    {
        $userUuid = $request->user()->uuid;
        $sesiones = \App\Modules\Auth\Entities\HistorialSesion::where('usuario_id', $userUuid)
            ->orderBy('fecha_hora', 'desc')
            ->limit(50)
            ->get();

        return response()->json($sesiones, 200);
    }
}
