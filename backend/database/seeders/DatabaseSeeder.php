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

        // 2. Poblar Opciones (Menús Visuales)
        $opciones = [
            ['nombre_opcion' => 'Incidencias - Gestión Total', 'descripcion' => 'Acceso completo al módulo de incidencias.'],
            ['nombre_opcion' => 'Perfil de Usuario', 'descripcion' => 'Acceso a la información personal del usuario logueado.'],
            ['nombre_opcion' => 'Gestión de Usuarios', 'descripcion' => 'Administración y creación de usuarios del sistema.'],
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

        // 5. Mapear Opciones a los roles (Las opciones generales van a todos, Gestión de Usuarios solo a ADMIN)
        $opcionesGenerales = DB::table('opcion')
            ->where('nombre_opcion', '!=', 'Gestión de Usuarios')
            ->pluck('id');
        $todosLosRoles = DB::table('rol')->pluck('id');

        foreach ($todosLosRoles as $rolId) {
            foreach ($opcionesGenerales as $opcionId) {
                DB::table('rol_opcion')->updateOrInsert(
                    ['id_rol' => $rolId, 'id_opcion' => $opcionId],
                    ['uuid' => DB::raw('uuid_generate_v4()'), 'deleted' => false, 'created_at' => now()],
                );
            }
        }

        // Mapear "Gestión de Usuarios" únicamente a ADMIN
        $opcionUsuariosId = DB::table('opcion')->where('nombre_opcion', 'Gestión de Usuarios')->value('id');
        if ($adminRolId && $opcionUsuariosId) {
            DB::table('rol_opcion')->updateOrInsert(
                ['id_rol' => $adminRolId, 'id_opcion' => $opcionUsuariosId],
                ['uuid' => DB::raw('uuid_generate_v4()'), 'deleted' => false, 'created_at' => now()],
            );
        }

        // 6. Poblar Endpoints Físicos de la API (Separados por método para respetar el CHECK de Postgres)
        $endpoints = [
            ['nombre' => 'Listar Incidencias', 'metodo' => 'GET', 'url' => 'api/incidencias*'],
            ['nombre' => 'Crear Incidencia', 'metodo' => 'POST', 'url' => 'api/incidencias*'],
            ['nombre' => 'Actualizar Incidencia', 'metodo' => 'PUT', 'url' => 'api/incidencias*'],
            ['nombre' => 'Eliminar Incidencia', 'metodo' => 'DELETE', 'url' => 'api/incidencias*'],
            ['nombre' => 'Ver Perfil', 'metodo' => 'GET', 'url' => 'api/user'],
            ['nombre' => 'Listar Usuarios', 'metodo' => 'GET', 'url' => 'api/usuarios*'],
            ['nombre' => 'Crear Usuario', 'metodo' => 'POST', 'url' => 'api/usuarios*'],
            ['nombre' => 'Actualizar Usuario', 'metodo' => 'PUT', 'url' => 'api/usuarios*'],
            ['nombre' => 'Activar/Desactivar Usuario', 'metodo' => 'PATCH', 'url' => 'api/usuarios*'],
        ];

        foreach ($endpoints as $endpoint) {
            DB::table('endpoint')->updateOrInsert(
                // Buscamos por URL y Método para no duplicar
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
        $idOpcionIncidencias = DB::table('opcion')->where('nombre_opcion', 'Incidencias - Gestión Total')->value('id');
        $idOpcionPerfil = DB::table('opcion')->where('nombre_opcion', 'Perfil de Usuario')->value('id');
        $idOpcionUsuarios = DB::table('opcion')->where('nombre_opcion', 'Gestión de Usuarios')->value('id');

        // Mapeamos TODOS los endpoints de incidencias a su opción
        $incidenciaEndpoints = DB::table('endpoint')->where('url', 'api/incidencias*')->pluck('id');
        foreach ($incidenciaEndpoints as $idEndpoint) {
            DB::table('opcion_endpoint')->updateOrInsert(
                ['id_opcion' => $idOpcionIncidencias, 'id_endpoint' => $idEndpoint],
                ['uuid' => DB::raw('uuid_generate_v4()'), 'deleted' => false, 'created_at' => now()],
            );
        }

        // Mapeamos el endpoint del perfil a su opción
        $idEndpointPerfil = DB::table('endpoint')->where('url', 'api/user')->value('id');
        if ($idOpcionPerfil && $idEndpointPerfil) {
            DB::table('opcion_endpoint')->updateOrInsert(
                ['id_opcion' => $idOpcionPerfil, 'id_endpoint' => $idEndpointPerfil],
                ['uuid' => DB::raw('uuid_generate_v4()'), 'deleted' => false, 'created_at' => now()],
            );
        }

        // Mapeamos los endpoints de usuarios a su opción de Gestión de Usuarios
        $usuarioEndpoints = DB::table('endpoint')->where('url', 'api/usuarios*')->pluck('id');
        foreach ($usuarioEndpoints as $idEndpoint) {
            if ($idOpcionUsuarios) {
                DB::table('opcion_endpoint')->updateOrInsert(
                    ['id_opcion' => $idOpcionUsuarios, 'id_endpoint' => $idEndpoint],
                    ['uuid' => DB::raw('uuid_generate_v4()'), 'deleted' => false, 'created_at' => now()],
                );
            }
        }
    }
}
