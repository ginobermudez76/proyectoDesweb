<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Poblar Roles
        $roles = [
            ['codigo' => 'ADMIN', 'nombre_rol' => 'Administrador', 'descripcion' => 'Acceso total.'],
            ['codigo' => 'SUPERVISOR', 'nombre_rol' => 'Supervisor', 'descripcion' => 'Supervisor de incidencias.'],
            ['codigo' => 'TECNICO', 'nombre_rol' => 'Técnico', 'descripcion' => 'Resolutor de incidencias.'],
            ['codigo' => 'CIUDADANO', 'nombre_rol' => 'Ciudadano', 'descripcion' => 'Reporta incidencias.'],
        ];

        foreach ($roles as $rol) {
            DB::table('rol')->updateOrInsert(
                ['codigo' => $rol['codigo']],
                [
                    'uuid' => DB::raw('uuid_generate_v4()'),
                    'nombre_rol' => $rol['nombre_rol'],
                    'descripcion' => $rol['descripcion'],
                    'deleted' => false,
                    'created_at' => now(),
                ],
            );
        }

        // 2. Poblar Opciones (Menús Visuales y Permisos)
        $opciones = [
            ['nombre_opcion' => 'Incidencias - Lectura y Reporte', 'descripcion' => 'Acceso básico para ver, reportar y comentar incidencias.'],
            ['nombre_opcion' => 'Incidencias - Gestión Operativa', 'descripcion' => 'Permiso para cambiar estado y enviar mensajes oficiales.'],
            ['nombre_opcion' => 'Incidencias - Edición Especial', 'descripcion' => 'Permiso para actualizar detalles de la incidencia.'],
            ['nombre_opcion' => 'Incidencias - Eliminación', 'descripcion' => 'Permiso para eliminar incidencias.'],
            ['nombre_opcion' => 'Perfil de Usuario', 'descripcion' => 'Acceso a la información personal del usuario logueado.'],
            ['nombre_opcion' => 'Gestión de Usuarios', 'descripcion' => 'Administración y creación de usuarios y listar roles.'],
        ];

        foreach ($opciones as $opcion) {
            DB::table('opcion')->updateOrInsert(
                ['nombre_opcion' => $opcion['nombre_opcion']],
                [
                    'uuid' => DB::raw('uuid_generate_v4()'),
                    'descripcion' => $opcion['descripcion'],
                    'deleted' => false,
                    'created_at' => now(),
                ],
            );
        }

        // 3. Crear Usuario Administrador (Tus credenciales)
        DB::table('usuario')->updateOrInsert(
            ['correo_electronico' => 'said@admin.com'],
            [
                'uuid' => DB::raw('uuid_generate_v4()'),
                'nombre_usuario' => 'said.pinto',
                'password_hash' => Hash::make('password123'),
                'nombres' => 'Said',
                'apellidos' => 'Pinto',
                'activo' => true,
                'deleted' => false,
                'created_at' => now(),
            ],
        );

        // 4. Mapear Usuario a Rol Administrador
        $adminRolId = DB::table('rol')->where('codigo', 'ADMIN')->value('id');
        $usuarioId = DB::table('usuario')->where('correo_electronico', 'said@admin.com')->value('id');

        if ($adminRolId && $usuarioId) {
            DB::table('rol_usuario')->updateOrInsert(
                ['id_rol' => $adminRolId, 'id_usuario' => $usuarioId],
                ['uuid' => DB::raw('uuid_generate_v4()'), 'deleted' => false, 'created_at' => now()],
            );
        }

        // 5. Mapear Opciones a los roles de forma segura y jerárquica
        $rolAdmin = DB::table('rol')->where('codigo', 'ADMIN')->first();
        $rolSupervisor = DB::table('rol')->where('codigo', 'SUPERVISOR')->first();
        $rolTecnico = DB::table('rol')->where('codigo', 'TECNICO')->first();
        $rolCiudadano = DB::table('rol')->where('codigo', 'CIUDADANO')->first();

        $mapRolOpcion = function($rolId, $opcionNombre) {
            $opcionId = DB::table('opcion')->where('nombre_opcion', $opcionNombre)->value('id');
            if ($rolId && $opcionId) {
                DB::table('rol_opcion')->updateOrInsert(
                    ['id_rol' => $rolId, 'id_opcion' => $opcionId],
                    ['uuid' => DB::raw('uuid_generate_v4()'), 'deleted' => false, 'created_at' => now()]
                );
            }
        };

        // Ciudadano: Solo lectura y reporte, y perfil de usuario
        if ($rolCiudadano) {
            $mapRolOpcion($rolCiudadano->id, 'Incidencias - Lectura y Reporte');
            $mapRolOpcion($rolCiudadano->id, 'Perfil de Usuario');
        }

        // Técnico: Lectura, reporte, gestión operativa (estado, mensajes) y perfil
        if ($rolTecnico) {
            $mapRolOpcion($rolTecnico->id, 'Incidencias - Lectura y Reporte');
            $mapRolOpcion($rolTecnico->id, 'Incidencias - Gestión Operativa');
            $mapRolOpcion($rolTecnico->id, 'Perfil de Usuario');
        }

        // Supervisor: Todo sobre incidencias y perfil
        if ($rolSupervisor) {
            $mapRolOpcion($rolSupervisor->id, 'Incidencias - Lectura y Reporte');
            $mapRolOpcion($rolSupervisor->id, 'Incidencias - Gestión Operativa');
            $mapRolOpcion($rolSupervisor->id, 'Incidencias - Edición Especial');
            $mapRolOpcion($rolSupervisor->id, 'Incidencias - Eliminación');
            $mapRolOpcion($rolSupervisor->id, 'Perfil de Usuario');
        }

        // Administrador: Todo el sistema (Incidencias completa, perfil, gestión de usuarios)
        if ($rolAdmin) {
            $mapRolOpcion($rolAdmin->id, 'Incidencias - Lectura y Reporte');
            $mapRolOpcion($rolAdmin->id, 'Incidencias - Gestión Operativa');
            $mapRolOpcion($rolAdmin->id, 'Incidencias - Edición Especial');
            $mapRolOpcion($rolAdmin->id, 'Incidencias - Eliminación');
            $mapRolOpcion($rolAdmin->id, 'Perfil de Usuario');
            $mapRolOpcion($rolAdmin->id, 'Gestión de Usuarios');
        }

        // 6. Poblar Endpoints Físicos de la API
        $endpoints = [
            ['nombre' => 'Listar Incidencias', 'metodo' => 'GET', 'url' => 'api/incidencias'],
            ['nombre' => 'Ver Detalle Incidencia', 'metodo' => 'GET', 'url' => 'api/incidencias/*'],
            ['nombre' => 'Crear Incidencia', 'metodo' => 'POST', 'url' => 'api/incidencias'],
            ['nombre' => 'Actualizar Incidencia', 'metodo' => 'PUT', 'url' => 'api/incidencias/*'],
            ['nombre' => 'Eliminar Incidencia', 'metodo' => 'DELETE', 'url' => 'api/incidencias/*'],
            ['nombre' => 'Cambiar Estado Incidencia', 'metodo' => 'POST', 'url' => 'api/incidencias/*/estado'],
            ['nombre' => 'Agregar Comentario', 'metodo' => 'POST', 'url' => 'api/incidencias/*/comentarios'],
            ['nombre' => 'Subir Evidencia', 'metodo' => 'POST', 'url' => 'api/incidencias/*/evidencias'],
            ['nombre' => 'Enviar Mensaje', 'metodo' => 'POST', 'url' => 'api/incidencias/*/mensajes'],
            ['nombre' => 'Ver Perfil', 'metodo' => 'GET', 'url' => 'api/user'],
            ['nombre' => 'Listar Roles', 'metodo' => 'GET', 'url' => 'api/roles'],
            ['nombre' => 'Listar Usuarios', 'metodo' => 'GET', 'url' => 'api/usuarios'],
            ['nombre' => 'Crear Usuario', 'metodo' => 'POST', 'url' => 'api/usuarios'],
            ['nombre' => 'Actualizar Usuario', 'metodo' => 'PUT', 'url' => 'api/usuarios/*'],
            ['nombre' => 'Activar/Desactivar Usuario', 'metodo' => 'PATCH', 'url' => 'api/usuarios/*/toggle'],
        ];

        foreach ($endpoints as $endpoint) {
            DB::table('endpoint')->updateOrInsert(
                ['url' => $endpoint['url'], 'metodo' => $endpoint['metodo']],
                [
                    'uuid' => DB::raw('uuid_generate_v4()'),
                    'nombre_endpoint' => $endpoint['nombre'],
                    'rbac_enabled' => true,
                    'deleted' => false,
                    'created_at' => now(),
                ],
            );
        }

        // 7. Conectar Opciones con Endpoints
        $mapOpcionEndpoint = function($opcionNombre, $metodo, $url) {
            $opcionId = DB::table('opcion')->where('nombre_opcion', $opcionNombre)->value('id');
            $endpointId = DB::table('endpoint')->where('metodo', $metodo)->where('url', $url)->value('id');
            if ($opcionId && $endpointId) {
                DB::table('opcion_endpoint')->updateOrInsert(
                    ['id_opcion' => $opcionId, 'id_endpoint' => $endpointId],
                    ['uuid' => DB::raw('uuid_generate_v4()'), 'deleted' => false, 'created_at' => now()]
                );
            }
        };

        // Opción: Incidencias - Lectura y Reporte
        $mapOpcionEndpoint('Incidencias - Lectura y Reporte', 'GET', 'api/incidencias');
        $mapOpcionEndpoint('Incidencias - Lectura y Reporte', 'GET', 'api/incidencias/*');
        $mapOpcionEndpoint('Incidencias - Lectura y Reporte', 'POST', 'api/incidencias');
        $mapOpcionEndpoint('Incidencias - Lectura y Reporte', 'PUT', 'api/incidencias/*');
        $mapOpcionEndpoint('Incidencias - Lectura y Reporte', 'DELETE', 'api/incidencias/*');
        $mapOpcionEndpoint('Incidencias - Lectura y Reporte', 'POST', 'api/incidencias/*/comentarios');
        $mapOpcionEndpoint('Incidencias - Lectura y Reporte', 'POST', 'api/incidencias/*/evidencias');

        // Opción: Incidencias - Gestión Operativa
        $mapOpcionEndpoint('Incidencias - Gestión Operativa', 'POST', 'api/incidencias/*/estado');
        $mapOpcionEndpoint('Incidencias - Gestión Operativa', 'POST', 'api/incidencias/*/mensajes');

        // Opción: Perfil de Usuario
        $mapOpcionEndpoint('Perfil de Usuario', 'GET', 'api/user');

        // Opción: Gestión de Usuarios
        $mapOpcionEndpoint('Gestión de Usuarios', 'GET', 'api/roles');
        $mapOpcionEndpoint('Gestión de Usuarios', 'GET', 'api/usuarios');
        $mapOpcionEndpoint('Gestión de Usuarios', 'POST', 'api/usuarios');
        $mapOpcionEndpoint('Gestión de Usuarios', 'PUT', 'api/usuarios/*');
        $mapOpcionEndpoint('Gestión de Usuarios', 'PATCH', 'api/usuarios/*/toggle');
    }
}
