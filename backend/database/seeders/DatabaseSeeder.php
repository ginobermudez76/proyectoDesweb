<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Poblar Roles
        $roles = [
            [
                'codigo' => 'ADMIN',
                'nombre_rol' => 'Administrador',
                'descripcion' => 'Administrador del sistema con acceso total.',
                'deleted' => false,
            ],
            [
                'codigo' => 'SUPERVISOR',
                'nombre_rol' => 'Supervisor',
                'descripcion' => 'Supervisor de incidencias y asignaciones.',
                'deleted' => false,
            ],
            [
                'codigo' => 'TECNICO',
                'nombre_rol' => 'Técnico Resolutor',
                'descripcion' => 'Personal encargado de solventar las incidencias.',
                'deleted' => false,
            ],
            [
                'codigo' => 'CIUDADANO',
                'nombre_rol' => 'Ciudadano',
                'descripcion' => 'Usuario que reporta incidencias georreferenciadas.',
                'deleted' => false,
            ],
        ];

        foreach ($roles as $rol) {
            DB::table('rol')->updateOrInsert(
                ['codigo' => $rol['codigo']],
                [
                    'uuid' => DB::raw('uuid_generate_v4()'),
                    'nombre_rol' => $rol['nombre_rol'],
                    'descripcion' => $rol['descripcion'],
                    'deleted' => $rol['deleted'],
                    'created_at' => now(),
                ]
            );
        }

        // 2. Poblar Opciones
        $opciones = [
            ['nombre_opcion' => 'Dashboard', 'descripcion' => 'Panel de control principal.'],
            ['nombre_opcion' => 'Incidencias - Listado', 'descripcion' => 'Visualización y búsqueda de incidencias.'],
            ['nombre_opcion' => 'Incidencias - Reportar', 'descripcion' => 'Formulario para reportar nuevas incidencias.'],
            ['nombre_opcion' => 'Incidencias - Asignar', 'descripcion' => 'Asignación de técnicos a reportes.'],
            ['nombre_opcion' => 'Seguridad - Usuarios', 'descripcion' => 'Gestión de usuarios y asignación de roles.'],
        ];

        foreach ($opciones as $opcion) {
            DB::table('opcion')->updateOrInsert(
                ['nombre_opcion' => $opcion['nombre_opcion']],
                [
                    'uuid' => DB::raw('uuid_generate_v4()'),
                    'descripcion' => $opcion['descripcion'],
                    'deleted' => false,
                    'created_at' => now(),
                ]
            );
        }

        // 3. Crear Usuario Administrador de Prueba
        DB::table('usuario')->updateOrInsert(
            ['nombre_usuario' => 'admin'],
            [
                'uuid' => DB::raw('uuid_generate_v4()'),
                'correo_electronico' => 'admin@sistema.com',
                'password_hash' => Hash::make('admin123'),
                'nombres' => 'Administrador',
                'apellidos' => 'del Sistema',
                'activo' => true,
                'deleted' => false,
                'created_at' => now(),
            ]
        );

        // 4. Mapear Usuario a Rol Administrador
        $adminRolId = DB::table('rol')->where('codigo', 'ADMIN')->value('id');
        $adminUsuarioId = DB::table('usuario')->where('nombre_usuario', 'admin')->value('id');

        if ($adminRolId && $adminUsuarioId) {
            DB::table('rol_usuario')->updateOrInsert(
                [
                    'id_rol' => $adminRolId,
                    'id_usuario' => $adminUsuarioId,
                ],
                [
                    'uuid' => DB::raw('uuid_generate_v4()'),
                    'deleted' => false,
                    'created_at' => now(),
                ]
            );
        }

        // 5. Mapear Opciones a Rol Administrador (Todas las opciones para Admin)
        $opcionIds = DB::table('opcion')->pluck('id');
        foreach ($opcionIds as $opcionId) {
            DB::table('rol_opcion')->updateOrInsert(
                [
                    'id_rol' => $adminRolId,
                    'id_opcion' => $opcionId,
                ],
                [
                    'uuid' => DB::raw('uuid_generate_v4()'),
                    'deleted' => false,
                    'created_at' => now(),
                ]
            );
        }
    }
}
