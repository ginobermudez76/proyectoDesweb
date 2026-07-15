<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================================================
        // 0. TIPOS DE DOCUMENTO
        // =====================================================================
        $tiposDoc = [
            [
                'codigo' => 'CEDULA',
                'label' => 'Cédula de Identidad',
                'validacion' => json_encode([
                    'regex' => '^[0-9]{10}$',
                    'min' => 10,
                    'max' => 10,
                    'error_msg' => 'La cédula debe tener exactamente 10 dígitos numéricos.'
                ])
            ],
            [
                'codigo' => 'RUC',
                'label' => 'RUC',
                'validacion' => json_encode([
                    'regex' => '^[0-9]{13}$',
                    'min' => 13,
                    'max' => 13,
                    'error_msg' => 'El RUC debe tener exactamente 13 dígitos numéricos.'
                ])
            ],
            [
                'codigo' => 'PASAPORTE',
                'label' => 'Pasaporte',
                'validacion' => json_encode([
                    'regex' => '^[a-zA-Z0-9]{5,20}$',
                    'min' => 5,
                    'max' => 20,
                    'error_msg' => 'El pasaporte debe tener entre 5 y 20 caracteres alfanuméricos.'
                ])
            ],
        ];

        foreach ($tiposDoc as $td) {
            DB::table('tipo_documento')->updateOrInsert(
                ['codigo' => $td['codigo']],
                [
                    'label' => $td['label'],
                    'validacion' => $td['validacion'],
                    'deleted' => false,
                    'created_at' => now(),
                ]
            );
        }

        // =====================================================================
        // 1. ROLES
        // =====================================================================
        $roles = [
            ['codigo' => 'ADMIN',      'nombre_rol' => 'Administrador', 'descripcion' => 'Acceso total.'],
            ['codigo' => 'SUPERVISOR', 'nombre_rol' => 'Supervisor',    'descripcion' => 'Supervisor de incidencias.'],
            ['codigo' => 'TECNICO',    'nombre_rol' => 'Técnico',       'descripcion' => 'Resolutor de incidencias.'],
            ['codigo' => 'CIUDADANO',  'nombre_rol' => 'Ciudadano',     'descripcion' => 'Reporta incidencias.'],
        ];

        foreach ($roles as $rol) {
            DB::table('rol')->updateOrInsert(
                ['codigo' => $rol['codigo']],
                [
                    'uuid'        => DB::raw('uuid_generate_v4()'),
                    'nombre_rol'  => $rol['nombre_rol'],
                    'descripcion' => $rol['descripcion'],
                    'deleted'     => false,
                    'created_at'  => now(),
                ],
            );
        }

        // =====================================================================
        // 2. OPCIONES (Menús Visuales y Permisos)
        // =====================================================================
        $opciones = [
            ['nombre_opcion' => 'Incidencias - Lectura y Reporte',  'descripcion' => 'Acceso básico para ver, reportar y comentar incidencias.', 'ruta' => '/incidencias/panel'],
            ['nombre_opcion' => 'Incidencias - Gestión Operativa',   'descripcion' => 'Permiso para cambiar estado y enviar mensajes oficiales.', 'ruta' => '/incidencias/gestion'],
            ['nombre_opcion' => 'Incidencias - Edición Especial',    'descripcion' => 'Permiso para actualizar detalles de la incidencia.', 'ruta' => '/incidencias/editar'],
            ['nombre_opcion' => 'Incidencias - Eliminación',         'descripcion' => 'Permiso para eliminar incidencias.', 'ruta' => '/incidencias/eliminar'],
            ['nombre_opcion' => 'Perfil de Usuario',                 'descripcion' => 'Acceso a la información personal del usuario logueado.', 'ruta' => '/perfil'],
            ['nombre_opcion' => 'Gestión de Usuarios',               'descripcion' => 'Administración y creación de usuarios y listar roles.', 'ruta' => '/usuarios'],
            ['nombre_opcion' => 'Catálogos',                         'descripcion' => 'Consulta de catálogos del sistema (estados, prioridades, tipos).', 'ruta' => '/catalogos'],
            ['nombre_opcion' => 'Gestión de Roles',                  'descripcion' => 'Administración y asignación de menús y permisos a roles.', 'ruta' => '/roles'],
        ];

        foreach ($opciones as $opcion) {
            DB::table('opcion')->updateOrInsert(
                ['nombre_opcion' => $opcion['nombre_opcion']],
                [
                    'uuid'        => DB::raw('uuid_generate_v4()'),
                    'descripcion' => $opcion['descripcion'],
                    'ruta'        => $opcion['ruta'],
                    'deleted'     => false,
                    'created_at'  => now(),
                ],
            );
        }

        // =====================================================================
        // 3. USUARIOS (Admin principal + usuarios de prueba por rol)
        // =====================================================================
        $usuarios = [
            [
                'correo'         => 'said@admin.com',
                'nombre_usuario' => 'said.pinto',
                'password'       => 'password123',
                'nombres'        => 'Said',
                'apellidos'      => 'Pinto',
                'rol_codigo'     => 'ADMIN',
            ],
            [
                'correo'         => 'supervisor@test.com',
                'nombre_usuario' => 'supervisor.test',
                'password'       => 'password123',
                'nombres'        => 'Supervisor',
                'apellidos'      => 'Test',
                'rol_codigo'     => 'SUPERVISOR',
            ],
            [
                'correo'         => 'tecnico@test.com',
                'nombre_usuario' => 'tecnico.test',
                'password'       => 'password123',
                'nombres'        => 'Técnico',
                'apellidos'      => 'Test',
                'rol_codigo'     => 'TECNICO',
            ],
            [
                'correo'         => 'ciudadano@test.com',
                'nombre_usuario' => 'ciudadano.test',
                'password'       => 'password123',
                'nombres'        => 'Ciudadano',
                'apellidos'      => 'Test',
                'rol_codigo'     => 'CIUDADANO',
            ],
        ];

        foreach ($usuarios as $u) {
            DB::table('usuario')->updateOrInsert(
                ['correo_electronico' => $u['correo']],
                [
                    'uuid'           => DB::raw('uuid_generate_v4()'),
                    'nombre_usuario' => $u['nombre_usuario'],
                    'password_hash'  => Hash::make($u['password']),
                    'nombres'        => $u['nombres'],
                    'apellidos'      => $u['apellidos'],
                    'activo'         => true,
                    'deleted'        => false,
                    'created_at'     => now(),
                ],
            );

            $rolId     = DB::table('rol')->where('codigo', $u['rol_codigo'])->value('id');
            $usuarioId = DB::table('usuario')->where('correo_electronico', $u['correo'])->value('id');

            if ($rolId && $usuarioId) {
                DB::table('rol_usuario')->updateOrInsert(
                    ['id_rol' => $rolId, 'id_usuario' => $usuarioId],
                    ['uuid' => DB::raw('uuid_generate_v4()'), 'deleted' => false, 'created_at' => now()],
                );
            }
        }

        // =====================================================================
        // 4. MAPEO ROL → OPCIONES
        // =====================================================================
        $rolAdmin      = DB::table('rol')->where('codigo', 'ADMIN')->first();
        $rolSupervisor = DB::table('rol')->where('codigo', 'SUPERVISOR')->first();
        $rolTecnico    = DB::table('rol')->where('codigo', 'TECNICO')->first();
        $rolCiudadano  = DB::table('rol')->where('codigo', 'CIUDADANO')->first();

        $mapRolOpcion = function ($rolId, $opcionNombre, $lectura = true, $escritura = false) {
            $opcionId = DB::table('opcion')->where('nombre_opcion', $opcionNombre)->value('id');
            if ($rolId && $opcionId) {
                DB::table('rol_opcion')->updateOrInsert(
                    ['id_rol' => $rolId, 'id_opcion' => $opcionId],
                    [
                        'uuid'       => DB::raw('uuid_generate_v4()'),
                        'lectura'    => $lectura,
                        'escritura'  => $escritura,
                        'deleted'    => false,
                        'created_at' => now(),
                    ]
                );
            }
        };

        // Ciudadano: Lectura/reporte, perfil, catálogos (escritura en reporte)
        if ($rolCiudadano) {
            $mapRolOpcion($rolCiudadano->id, 'Incidencias - Lectura y Reporte', true, true);
            $mapRolOpcion($rolCiudadano->id, 'Perfil de Usuario', true, true);
            $mapRolOpcion($rolCiudadano->id, 'Catálogos', true, false);
        }

        // Técnico: Lectura, gestión operativa, perfil, catálogos
        if ($rolTecnico) {
            $mapRolOpcion($rolTecnico->id, 'Incidencias - Lectura y Reporte', true, false);
            $mapRolOpcion($rolTecnico->id, 'Incidencias - Gestión Operativa', true, true);
            $mapRolOpcion($rolTecnico->id, 'Perfil de Usuario', true, true);
            $mapRolOpcion($rolTecnico->id, 'Catálogos', true, false);
        }

        // Supervisor: Todo sobre incidencias + perfil + catálogos + gestión de usuarios (ver técnicos)
        if ($rolSupervisor) {
            $mapRolOpcion($rolSupervisor->id, 'Incidencias - Lectura y Reporte', true, true);
            $mapRolOpcion($rolSupervisor->id, 'Incidencias - Gestión Operativa', true, true);
            $mapRolOpcion($rolSupervisor->id, 'Incidencias - Edición Especial', true, true);
            $mapRolOpcion($rolSupervisor->id, 'Incidencias - Eliminación', true, true);
            $mapRolOpcion($rolSupervisor->id, 'Perfil de Usuario', true, true);
            $mapRolOpcion($rolSupervisor->id, 'Catálogos', true, false);
            $mapRolOpcion($rolSupervisor->id, 'Gestión de Usuarios', true, true);
        }

        // Administrador: Sistema completo (excepto ver o gestionar incidencias individuales)
        if ($rolAdmin) {
            $mapRolOpcion($rolAdmin->id, 'Perfil de Usuario', true, true);
            $mapRolOpcion($rolAdmin->id, 'Gestión de Usuarios', true, true);
            $mapRolOpcion($rolAdmin->id, 'Catálogos', true, true);
            $mapRolOpcion($rolAdmin->id, 'Gestión de Roles', true, true);
        }

        // =====================================================================
        // 5. ENDPOINTS FÍSICOS DE LA API
        // =====================================================================
        $endpoints = [
            ['nombre' => 'Listar Incidencias',          'metodo' => 'GET',    'url' => 'api/incidencias'],
            ['nombre' => 'Ver Detalle Incidencia',       'metodo' => 'GET',    'url' => 'api/incidencias/*'],
            ['nombre' => 'Crear Incidencia',             'metodo' => 'POST',   'url' => 'api/incidencias'],
            ['nombre' => 'Actualizar Incidencia',        'metodo' => 'PUT',    'url' => 'api/incidencias/*'],
            ['nombre' => 'Eliminar Incidencia',          'metodo' => 'DELETE', 'url' => 'api/incidencias/*'],
            ['nombre' => 'Cambiar Estado Incidencia',    'metodo' => 'POST',   'url' => 'api/incidencias/*/estado'],
            ['nombre' => 'Agregar Comentario',           'metodo' => 'POST',   'url' => 'api/incidencias/*/comentarios'],
            ['nombre' => 'Subir Evidencia',              'metodo' => 'POST',   'url' => 'api/incidencias/*/evidencias'],
            ['nombre' => 'Enviar Mensaje',               'metodo' => 'POST',   'url' => 'api/incidencias/*/mensajes'],
            ['nombre' => 'Ver Mensajes',                 'metodo' => 'GET',    'url' => 'api/incidencias/*/mensajes'],
            ['nombre' => 'Estadísticas de Dashboard',    'metodo' => 'GET',    'url' => 'api/dashboard/stats'],
            ['nombre' => 'Crear Publicación',            'metodo' => 'POST',   'url' => 'api/publicaciones'],
            ['nombre' => 'Ver Perfil',                   'metodo' => 'GET',    'url' => 'api/user'],
            ['nombre' => 'Listar Roles',                 'metodo' => 'GET',    'url' => 'api/roles'],
            ['nombre' => 'Listar Usuarios',              'metodo' => 'GET',    'url' => 'api/usuarios'],
            ['nombre' => 'Listar Técnicos',              'metodo' => 'GET',    'url' => 'api/usuarios/tecnicos'],
            ['nombre' => 'Crear Usuario',                'metodo' => 'POST',   'url' => 'api/usuarios'],
            ['nombre' => 'Actualizar Usuario',           'metodo' => 'PUT',    'url' => 'api/usuarios/*'],
            ['nombre' => 'Activar/Desactivar Usuario',   'metodo' => 'PATCH',  'url' => 'api/usuarios/*/toggle'],
            ['nombre' => 'Consultar Catálogos',          'metodo' => 'GET',    'url' => 'api/catalogos'],
            ['nombre' => 'Listar Roles Admin',           'metodo' => 'GET',    'url' => 'api/admin/roles'],
            ['nombre' => 'Crear Rol',                    'metodo' => 'POST',   'url' => 'api/admin/roles'],
            ['nombre' => 'Actualizar Rol',               'metodo' => 'PUT',    'url' => 'api/admin/roles/*'],
            ['nombre' => 'Eliminar Rol',                 'metodo' => 'DELETE', 'url' => 'api/admin/roles/*'],
            ['nombre' => 'Listar Opciones Sistema',      'metodo' => 'GET',    'url' => 'api/admin/opciones'],
        ];

        foreach ($endpoints as $endpoint) {
            DB::table('endpoint')->updateOrInsert(
                ['url' => $endpoint['url'], 'metodo' => $endpoint['metodo']],
                [
                    'uuid'            => DB::raw('uuid_generate_v4()'),
                    'nombre_endpoint' => $endpoint['nombre'],
                    'rbac_enabled'    => true,
                    'deleted'         => false,
                    'created_at'      => now(),
                ],
            );
        }

        // =====================================================================
        // 6. MAPEO OPCIÓN → ENDPOINTS
        // =====================================================================
        $mapOpcionEndpoint = function ($opcionNombre, $metodo, $url) {
            $opcionId   = DB::table('opcion')->where('nombre_opcion', $opcionNombre)->value('id');
            $endpointId = DB::table('endpoint')->where('metodo', $metodo)->where('url', $url)->value('id');
            if ($opcionId && $endpointId) {
                DB::table('opcion_endpoint')->updateOrInsert(
                    ['id_opcion' => $opcionId, 'id_endpoint' => $endpointId],
                    ['uuid' => DB::raw('uuid_generate_v4()'), 'deleted' => false, 'created_at' => now()]
                );
            }
        };

        // Incidencias - Lectura y Reporte
        $mapOpcionEndpoint('Incidencias - Lectura y Reporte', 'GET',    'api/incidencias');
        $mapOpcionEndpoint('Incidencias - Lectura y Reporte', 'GET',    'api/incidencias/*');
        $mapOpcionEndpoint('Incidencias - Lectura y Reporte', 'POST',   'api/incidencias');
        $mapOpcionEndpoint('Incidencias - Lectura y Reporte', 'PUT',    'api/incidencias/*');
        $mapOpcionEndpoint('Incidencias - Lectura y Reporte', 'DELETE', 'api/incidencias/*');
        $mapOpcionEndpoint('Incidencias - Lectura y Reporte', 'POST',   'api/incidencias/*/comentarios');
        $mapOpcionEndpoint('Incidencias - Lectura y Reporte', 'POST',   'api/incidencias/*/evidencias');
        $mapOpcionEndpoint('Incidencias - Lectura y Reporte', 'GET',    'api/incidencias/*/mensajes');
        $mapOpcionEndpoint('Incidencias - Lectura y Reporte', 'POST',   'api/publicaciones');

        // Incidencias - Gestión Operativa
        $mapOpcionEndpoint('Incidencias - Gestión Operativa', 'POST', 'api/incidencias/*/estado');
        $mapOpcionEndpoint('Incidencias - Gestión Operativa', 'POST', 'api/incidencias/*/mensajes');

        // Perfil de Usuario
        $mapOpcionEndpoint('Perfil de Usuario', 'GET', 'api/user');

        // Gestión de Usuarios
        $mapOpcionEndpoint('Gestión de Usuarios', 'GET',   'api/roles');
        $mapOpcionEndpoint('Gestión de Usuarios', 'GET',   'api/usuarios');
        $mapOpcionEndpoint('Gestión de Usuarios', 'GET',   'api/usuarios/tecnicos');
        $mapOpcionEndpoint('Gestión de Usuarios', 'POST',  'api/usuarios');
        $mapOpcionEndpoint('Gestión de Usuarios', 'PUT',   'api/usuarios/*');
        $mapOpcionEndpoint('Gestión de Usuarios', 'PATCH', 'api/usuarios/*/toggle');

        // Catálogos — accesible para todos los roles autenticados
        $mapOpcionEndpoint('Catálogos', 'GET', 'api/catalogos');
        $mapOpcionEndpoint('Catálogos', 'GET', 'api/usuarios/tecnicos');
        $mapOpcionEndpoint('Catálogos', 'GET', 'api/dashboard/stats');

        // Gestión de Roles
        $mapOpcionEndpoint('Gestión de Roles', 'GET',    'api/admin/roles');
        $mapOpcionEndpoint('Gestión de Roles', 'POST',   'api/admin/roles');
        $mapOpcionEndpoint('Gestión de Roles', 'PUT',    'api/admin/roles/*');
        $mapOpcionEndpoint('Gestión de Roles', 'DELETE', 'api/admin/roles/*');
        $mapOpcionEndpoint('Gestión de Roles', 'GET',    'api/admin/opciones');

        // =====================================================================
        // 7. CATÁLOGOS DE DOMINIO
        // =====================================================================

        // Estados (código = valor exacto de MongoDB, label = texto UI)
        $estados = [
            ['codigo' => 'Pendiente',  'label' => 'Recibido',   'orden' => 1, 'css_class' => 'badge-recibido', 'color_hex' => '#3B82F6'],
            ['codigo' => 'En Proceso', 'label' => 'En proceso', 'orden' => 2, 'css_class' => 'badge-proceso',  'color_hex' => '#F97316'],
            ['codigo' => 'Resuelta',   'label' => 'Resuelto',   'orden' => 3, 'css_class' => 'badge-resuelto', 'color_hex' => '#22C55E'],
            ['codigo' => 'Rechazada',  'label' => 'Rechazado',  'orden' => 4, 'css_class' => 'badge-urgente',  'color_hex' => '#EF4444'],
        ];

        foreach ($estados as $e) {
            DB::table('catalogo_estado')->updateOrInsert(
                ['codigo' => $e['codigo']],
                [
                    'uuid'       => DB::raw('uuid_generate_v4()'),
                    'label'      => $e['label'],
                    'orden'      => $e['orden'],
                    'css_class'  => $e['css_class'],
                    'color_hex'  => $e['color_hex'],
                    'activo'     => true,
                    'deleted'    => false,
                    'created_at' => now(),
                ]
            );
        }

        // Prioridades
        $prioridades = [
            ['codigo' => 'Baja',    'label' => 'Baja',    'orden' => 1, 'css_class' => 'badge-baja',    'color_hex' => '#6B7280'],
            ['codigo' => 'Normal',  'label' => 'Normal',  'orden' => 2, 'css_class' => 'badge-media',   'color_hex' => '#8B5CF6'],
            ['codigo' => 'Media',   'label' => 'Media',   'orden' => 3, 'css_class' => 'badge-media',   'color_hex' => '#F97316'],
            ['codigo' => 'Alta',    'label' => 'Alta',    'orden' => 4, 'css_class' => 'badge-urgente',  'color_hex' => '#EF4444'],
            ['codigo' => 'Urgente', 'label' => 'Urgente', 'orden' => 5, 'css_class' => 'badge-urgente',  'color_hex' => '#B91C1C'],
        ];

        foreach ($prioridades as $p) {
            DB::table('catalogo_prioridad')->updateOrInsert(
                ['codigo' => $p['codigo']],
                [
                    'uuid'       => DB::raw('uuid_generate_v4()'),
                    'label'      => $p['label'],
                    'orden'      => $p['orden'],
                    'css_class'  => $p['css_class'],
                    'color_hex'  => $p['color_hex'],
                    'activo'     => true,
                    'deleted'    => false,
                    'created_at' => now(),
                ]
            );
        }

        // Tipos con sus subtipos
        $tiposSubtipos = [
            ['nombre' => 'Infraestructura', 'icono' => 'bi-building', 'orden' => 1, 'subs' => [
                'Alumbrado', 'Vialidad', 'Agua potable', 'Alcantarillado',
            ]],
            ['nombre' => 'Seguridad', 'icono' => 'bi-shield', 'orden' => 2, 'subs' => [
                'Robo', 'Vandalismo', 'Iluminación',
            ]],
            ['nombre' => 'Medio ambiente', 'icono' => 'bi-tree', 'orden' => 3, 'subs' => [
                'Basura', 'Contaminación', 'Áreas verdes',
            ]],
            ['nombre' => 'Servicios', 'icono' => 'bi-tools', 'orden' => 4, 'subs' => [
                'Otro',
            ]],
        ];

        foreach ($tiposSubtipos as $tipo) {
            DB::table('catalogo_tipo_incidencia')->updateOrInsert(
                ['nombre' => $tipo['nombre']],
                [
                    'uuid'        => DB::raw('uuid_generate_v4()'),
                    'icono_clase' => $tipo['icono'],
                    'orden'       => $tipo['orden'],
                    'activo'      => true,
                    'deleted'     => false,
                    'created_at'  => now(),
                ]
            );

            $tipoId = DB::table('catalogo_tipo_incidencia')->where('nombre', $tipo['nombre'])->value('id');

            foreach ($tipo['subs'] as $idx => $sub) {
                DB::table('catalogo_subtipo_incidencia')->updateOrInsert(
                    ['id_tipo' => $tipoId, 'nombre' => $sub],
                    [
                        'uuid'       => DB::raw('uuid_generate_v4()'),
                        'orden'      => $idx + 1,
                        'activo'     => true,
                        'deleted'    => false,
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
