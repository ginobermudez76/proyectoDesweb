<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private const UUID = 'uuid_generate_v4()';

    private const ROLE_ADMIN = 'ADMIN';
    private const ROLE_SUPERVISOR = 'SUPERVISOR';
    private const ROLE_TECNICO = 'TECNICO';
    private const ROLE_CIUDADANO = 'CIUDADANO';

    private const OPCION_INCIDENCIAS_LECTURA_REPORTE = 'Incidencias - Lectura y Reporte';
    private const OPCION_INCIDENCIAS_GESTION_OPERATIVA = 'Incidencias - Gestión Operativa';
    private const OPCION_INCIDENCIAS_EDICION_ESPECIAL = 'Incidencias - Edición Especial';
    private const OPCION_INCIDENCIAS_ELIMINACION = 'Incidencias - Eliminación';
    private const OPCION_PERFIL_USUARIO = 'Perfil de Usuario';
    private const OPCION_GESTION_USUARIOS = 'Gestión de Usuarios';
    private const OPCION_CATALOGOS = 'Catálogos';
    private const OPCION_GESTION_ROLES = 'Gestión de Roles';
    private const OPCION_PUBLICACIONES_GESTION = 'Publicaciones - Gestión';

    private const ENDPOINT_INCIDENCIAS = 'api/incidencias';
    private const ENDPOINT_INCIDENCIAS_ANY = 'api/incidencias/*';
    private const ENDPOINT_INCIDENCIAS_ESTADO = 'api/incidencias/*/estado';
    private const ENDPOINT_INCIDENCIAS_COMENTARIOS = 'api/incidencias/*/comentarios';
    private const ENDPOINT_INCIDENCIAS_EVIDENCIAS = 'api/incidencias/*/evidencias';
    private const ENDPOINT_INCIDENCIAS_MENSAJES = 'api/incidencias/*/mensajes';
    private const ENDPOINT_DASHBOARD_STATS = 'api/dashboard/stats';
    private const ENDPOINT_PUBLICACIONES = 'api/publicaciones';
    private const ENDPOINT_PUBLICACIONES_ANY = 'api/publicaciones/*';
    private const ENDPOINT_USER = 'api/user';
    private const ENDPOINT_PERFIL_STATS_TECNICO = 'api/perfil/stats-tecnico';
    private const ENDPOINT_ROLES = 'api/roles';
    private const ENDPOINT_USUARIOS = 'api/usuarios';
    private const ENDPOINT_USUARIOS_TECNICOS = 'api/usuarios/tecnicos';
    private const ENDPOINT_USUARIOS_TOGGLE = 'api/usuarios/*/toggle';
    private const ENDPOINT_CATALOGOS = 'api/catalogos';
    private const ENDPOINT_CATALOGOS_ESTADOS_ANY = 'api/catalogos/estados/*';
    private const ENDPOINT_CATALOGOS_PRIORIDADES_ANY = 'api/catalogos/prioridades/*';
    private const ENDPOINT_CATALOGOS_TIPOS = 'api/catalogos/tipos';
    private const ENDPOINT_CATALOGOS_TIPOS_ANY = 'api/catalogos/tipos/*';
    private const ENDPOINT_CATALOGOS_SUBTIPOS = 'api/catalogos/subtipos';
    private const ENDPOINT_CATALOGOS_SUBTIPOS_ANY = 'api/catalogos/subtipos/*';
    private const ENDPOINT_ADMIN_ROLES = 'api/admin/roles';
    private const ENDPOINT_ADMIN_ROLES_ANY = 'api/admin/roles/*';
    private const ENDPOINT_ADMIN_OPCIONES = 'api/admin/opciones';

    public function run(): void
    {
        $this->seedTiposDocumento();
        $this->seedRoles();
        $this->seedOpciones();
        $this->seedUsuarios();
        $this->seedRolOpciones();
        $this->seedEndpoints();
        $this->seedOpcionEndpoints();
        $this->seedCatalogosDominio();
    }

    private function seedTiposDocumento(): void
    {
        $tiposDoc = [
            [
                'codigo' => 'CEDULA',
                'label' => 'Cédula de Identidad',
                'validacion' => json_encode([
                    'regex' => '^[0-9]{10}$',
                    'min' => 10,
                    'max' => 10,
                    'error_msg' => 'La cédula debe tener exactamente 10 dígitos numéricos.',
                ]),
            ],
            [
                'codigo' => 'RUC',
                'label' => 'RUC',
                'validacion' => json_encode([
                    'regex' => '^[0-9]{13}$',
                    'min' => 13,
                    'max' => 13,
                    'error_msg' => 'El RUC debe tener exactamente 13 dígitos numéricos.',
                ]),
            ],
            [
                'codigo' => 'PASAPORTE',
                'label' => 'Pasaporte',
                'validacion' => json_encode([
                    'regex' => '^[a-zA-Z0-9]{5,20}$',
                    'min' => 5,
                    'max' => 20,
                    'error_msg' => 'El pasaporte debe tener entre 5 y 20 caracteres alfanuméricos.',
                ]),
            ],
        ];

        foreach ($tiposDoc as $tipoDocumento) {
            DB::table('tipo_documento')->updateOrInsert(
                ['codigo' => $tipoDocumento['codigo']],
                [
                    'label' => $tipoDocumento['label'],
                    'validacion' => $tipoDocumento['validacion'],
                    'deleted' => false,
                    'created_at' => now(),
                ]
            );
        }
    }

    private function seedRoles(): void
    {
        $roles = [
            ['codigo' => self::ROLE_ADMIN, 'nombre_rol' => 'Administrador', 'descripcion' => 'Acceso total.'],
            ['codigo' => self::ROLE_SUPERVISOR, 'nombre_rol' => 'Supervisor', 'descripcion' => 'Supervisor de incidencias.'],
            ['codigo' => self::ROLE_TECNICO, 'nombre_rol' => 'Técnico', 'descripcion' => 'Resolutor de incidencias.'],
            ['codigo' => self::ROLE_CIUDADANO, 'nombre_rol' => 'Ciudadano', 'descripcion' => 'Reporta incidencias.'],
        ];

        foreach ($roles as $rol) {
            DB::table('rol')->updateOrInsert(
                ['codigo' => $rol['codigo']],
                [
                    'uuid' => DB::raw(self::UUID),
                    'nombre_rol' => $rol['nombre_rol'],
                    'descripcion' => $rol['descripcion'],
                    'deleted' => false,
                    'created_at' => now(),
                ]
            );
        }
    }

    private function seedOpciones(): void
    {
        $opciones = [
            ['nombre_opcion' => self::OPCION_INCIDENCIAS_LECTURA_REPORTE, 'descripcion' => 'Acceso básico para ver, reportar y comentar incidencias.', 'ruta' => '/incidencias/panel'],
            ['nombre_opcion' => self::OPCION_INCIDENCIAS_GESTION_OPERATIVA, 'descripcion' => 'Permiso para cambiar estado y enviar mensajes oficiales.', 'ruta' => '/incidencias/gestion'],
            ['nombre_opcion' => self::OPCION_INCIDENCIAS_EDICION_ESPECIAL, 'descripcion' => 'Permiso para actualizar detalles de la incidencia.', 'ruta' => '/incidencias/editar'],
            ['nombre_opcion' => self::OPCION_INCIDENCIAS_ELIMINACION, 'descripcion' => 'Permiso para eliminar incidencias.', 'ruta' => '/incidencias/eliminar'],
            ['nombre_opcion' => self::OPCION_PERFIL_USUARIO, 'descripcion' => 'Acceso a la información personal del usuario logueado.', 'ruta' => '/perfil'],
            ['nombre_opcion' => self::OPCION_GESTION_USUARIOS, 'descripcion' => 'Administración y creación de usuarios y listar roles.', 'ruta' => '/usuarios'],
            ['nombre_opcion' => self::OPCION_CATALOGOS, 'descripcion' => 'Consulta de catálogos del sistema (estados, prioridades, tipos).', 'ruta' => '/catalogos'],
            ['nombre_opcion' => self::OPCION_GESTION_ROLES, 'descripcion' => 'Administración y asignación de menús y permisos a roles.', 'ruta' => '/roles'],
            ['nombre_opcion' => self::OPCION_PUBLICACIONES_GESTION, 'descripcion' => 'Creación y gestión de publicaciones y comunicados oficiales.', 'ruta' => '/publicaciones'],
        ];

        foreach ($opciones as $opcion) {
            DB::table('opcion')->updateOrInsert(
                ['nombre_opcion' => $opcion['nombre_opcion']],
                [
                    'uuid' => DB::raw(self::UUID),
                    'descripcion' => $opcion['descripcion'],
                    'ruta' => $opcion['ruta'],
                    'deleted' => false,
                    'created_at' => now(),
                ]
            );
        }
    }

    private function seedUsuarios(): void
    {
        $usuarios = [
            ['correo' => 'said@admin.com', 'nombre_usuario' => 'said.pinto', 'password' => 'password123', 'nombres' => 'Said', 'apellidos' => 'Pinto', 'rol_codigo' => self::ROLE_ADMIN],
            ['correo' => 'supervisor@test.com', 'nombre_usuario' => 'supervisor.test', 'password' => 'password123', 'nombres' => 'Supervisor', 'apellidos' => 'Test', 'rol_codigo' => self::ROLE_SUPERVISOR],
            ['correo' => 'tecnico@test.com', 'nombre_usuario' => 'tecnico.test', 'password' => 'password123', 'nombres' => 'Técnico', 'apellidos' => 'Test', 'rol_codigo' => self::ROLE_TECNICO],
            ['correo' => 'ciudadano@test.com', 'nombre_usuario' => 'ciudadano.test', 'password' => 'password123', 'nombres' => 'Ciudadano', 'apellidos' => 'Test', 'rol_codigo' => self::ROLE_CIUDADANO],
        ];

        foreach ($usuarios as $usuario) {
            // El uuid solo se genera al crear el usuario por primera vez: regenerarlo en cada
            // reseed rompería las referencias existentes (usuario_id/asignado_a) en Incidencia (Mongo).
            $yaExiste = DB::table('usuario')->where('correo_electronico', $usuario['correo'])->exists();

            $valores = [
                'nombre_usuario' => $usuario['nombre_usuario'],
                'password_hash' => Hash::make($usuario['password']),
                'nombres' => $usuario['nombres'],
                'apellidos' => $usuario['apellidos'],
                'activo' => true,
                'deleted' => false,
                'created_at' => now(),
            ];

            if (!$yaExiste) {
                $valores['uuid'] = DB::raw(self::UUID);
            }

            DB::table('usuario')->updateOrInsert(
                ['correo_electronico' => $usuario['correo']],
                $valores
            );

            $rolId = DB::table('rol')->where('codigo', $usuario['rol_codigo'])->value('id');
            $usuarioId = DB::table('usuario')->where('correo_electronico', $usuario['correo'])->value('id');

            if ($rolId && $usuarioId) {
                DB::table('rol_usuario')->updateOrInsert(
                    ['id_rol' => $rolId, 'id_usuario' => $usuarioId],
                    ['uuid' => DB::raw(self::UUID), 'deleted' => false, 'created_at' => now()]
                );
            }
        }
    }

    private function seedRolOpciones(): void
    {
        $rolAdmin = DB::table('rol')->where('codigo', self::ROLE_ADMIN)->first();
        $rolSupervisor = DB::table('rol')->where('codigo', self::ROLE_SUPERVISOR)->first();
        $rolTecnico = DB::table('rol')->where('codigo', self::ROLE_TECNICO)->first();
        $rolCiudadano = DB::table('rol')->where('codigo', self::ROLE_CIUDADANO)->first();

        if ($rolCiudadano) {
            $this->mapRolOpcion($rolCiudadano->id, self::OPCION_INCIDENCIAS_LECTURA_REPORTE, true, true);
            $this->mapRolOpcion($rolCiudadano->id, self::OPCION_PERFIL_USUARIO, true, true);
            $this->mapRolOpcion($rolCiudadano->id, self::OPCION_CATALOGOS, true, false);
        }

        if ($rolTecnico) {
            $this->mapRolOpcion($rolTecnico->id, self::OPCION_INCIDENCIAS_LECTURA_REPORTE, true, false);
            $this->mapRolOpcion($rolTecnico->id, self::OPCION_INCIDENCIAS_GESTION_OPERATIVA, true, true);
            $this->mapRolOpcion($rolTecnico->id, self::OPCION_PERFIL_USUARIO, true, true);
            $this->mapRolOpcion($rolTecnico->id, self::OPCION_CATALOGOS, true, false);
        }

        if ($rolSupervisor) {
            $this->mapRolOpcion($rolSupervisor->id, self::OPCION_INCIDENCIAS_LECTURA_REPORTE, true, true);
            $this->mapRolOpcion($rolSupervisor->id, self::OPCION_INCIDENCIAS_GESTION_OPERATIVA, true, true);
            $this->mapRolOpcion($rolSupervisor->id, self::OPCION_INCIDENCIAS_EDICION_ESPECIAL, true, true);
            $this->mapRolOpcion($rolSupervisor->id, self::OPCION_INCIDENCIAS_ELIMINACION, true, true);
            $this->mapRolOpcion($rolSupervisor->id, self::OPCION_PERFIL_USUARIO, true, true);
            $this->mapRolOpcion($rolSupervisor->id, self::OPCION_CATALOGOS, true, false);
            $this->mapRolOpcion($rolSupervisor->id, self::OPCION_GESTION_USUARIOS, true, true);
            $this->mapRolOpcion($rolSupervisor->id, self::OPCION_PUBLICACIONES_GESTION, true, true);
        }

        if ($rolAdmin) {
            $this->mapRolOpcion($rolAdmin->id, self::OPCION_PERFIL_USUARIO, true, true);
            $this->mapRolOpcion($rolAdmin->id, self::OPCION_GESTION_USUARIOS, true, true);
            $this->mapRolOpcion($rolAdmin->id, self::OPCION_CATALOGOS, true, true);
            $this->mapRolOpcion($rolAdmin->id, self::OPCION_GESTION_ROLES, true, true);
            $this->mapRolOpcion($rolAdmin->id, self::OPCION_PUBLICACIONES_GESTION, true, true);
        }
    }

    private function seedEndpoints(): void
    {
        $endpoints = [
            ['nombre' => 'Listar Incidencias', 'metodo' => 'GET', 'url' => self::ENDPOINT_INCIDENCIAS],
            ['nombre' => 'Ver Detalle Incidencia', 'metodo' => 'GET', 'url' => self::ENDPOINT_INCIDENCIAS_ANY],
            ['nombre' => 'Crear Incidencia', 'metodo' => 'POST', 'url' => self::ENDPOINT_INCIDENCIAS],
            ['nombre' => 'Actualizar Incidencia', 'metodo' => 'PUT', 'url' => self::ENDPOINT_INCIDENCIAS_ANY],
            ['nombre' => 'Eliminar Incidencia', 'metodo' => 'DELETE', 'url' => self::ENDPOINT_INCIDENCIAS_ANY],
            ['nombre' => 'Cambiar Estado Incidencia', 'metodo' => 'POST', 'url' => self::ENDPOINT_INCIDENCIAS_ESTADO],
            ['nombre' => 'Agregar Comentario', 'metodo' => 'POST', 'url' => self::ENDPOINT_INCIDENCIAS_COMENTARIOS],
            ['nombre' => 'Subir Evidencia', 'metodo' => 'POST', 'url' => self::ENDPOINT_INCIDENCIAS_EVIDENCIAS],
            ['nombre' => 'Enviar Mensaje', 'metodo' => 'POST', 'url' => self::ENDPOINT_INCIDENCIAS_MENSAJES],
            ['nombre' => 'Ver Mensajes', 'metodo' => 'GET', 'url' => self::ENDPOINT_INCIDENCIAS_MENSAJES],
            ['nombre' => 'Estadísticas de Dashboard', 'metodo' => 'GET', 'url' => self::ENDPOINT_DASHBOARD_STATS],
            ['nombre' => 'Estadísticas Perfil Técnico', 'metodo' => 'GET', 'url' => self::ENDPOINT_PERFIL_STATS_TECNICO],
            ['nombre' => 'Listar Publicaciones', 'metodo' => 'GET', 'url' => self::ENDPOINT_PUBLICACIONES],
            ['nombre' => 'Ver Detalle Publicación', 'metodo' => 'GET', 'url' => self::ENDPOINT_PUBLICACIONES_ANY],
            ['nombre' => 'Crear Publicación', 'metodo' => 'POST', 'url' => self::ENDPOINT_PUBLICACIONES],
            ['nombre' => 'Actualizar Publicación', 'metodo' => 'PUT', 'url' => self::ENDPOINT_PUBLICACIONES_ANY],
            ['nombre' => 'Eliminar Publicación', 'metodo' => 'DELETE', 'url' => self::ENDPOINT_PUBLICACIONES_ANY],
            ['nombre' => 'Ver Perfil', 'metodo' => 'GET', 'url' => self::ENDPOINT_USER],
            ['nombre' => 'Listar Roles', 'metodo' => 'GET', 'url' => self::ENDPOINT_ROLES],
            ['nombre' => 'Listar Usuarios', 'metodo' => 'GET', 'url' => self::ENDPOINT_USUARIOS],
            ['nombre' => 'Listar Técnicos', 'metodo' => 'GET', 'url' => self::ENDPOINT_USUARIOS_TECNICOS],
            ['nombre' => 'Crear Usuario', 'metodo' => 'POST', 'url' => self::ENDPOINT_USUARIOS],
            ['nombre' => 'Actualizar Usuario', 'metodo' => 'PUT', 'url' => self::ENDPOINT_USUARIOS_ANY()],
            ['nombre' => 'Activar/Desactivar Usuario', 'metodo' => 'PATCH', 'url' => self::ENDPOINT_USUARIOS_TOGGLE],
            ['nombre' => 'Consultar Catálogos', 'metodo' => 'GET', 'url' => self::ENDPOINT_CATALOGOS],
            ['nombre' => 'Actualizar Estado (catálogo)', 'metodo' => 'PUT', 'url' => self::ENDPOINT_CATALOGOS_ESTADOS_ANY],
            ['nombre' => 'Actualizar Prioridad (catálogo)', 'metodo' => 'PUT', 'url' => self::ENDPOINT_CATALOGOS_PRIORIDADES_ANY],
            ['nombre' => 'Crear Tipo de Incidencia', 'metodo' => 'POST', 'url' => self::ENDPOINT_CATALOGOS_TIPOS],
            ['nombre' => 'Actualizar Tipo de Incidencia', 'metodo' => 'PUT', 'url' => self::ENDPOINT_CATALOGOS_TIPOS_ANY],
            ['nombre' => 'Eliminar Tipo de Incidencia', 'metodo' => 'DELETE', 'url' => self::ENDPOINT_CATALOGOS_TIPOS_ANY],
            ['nombre' => 'Crear Subtipo de Incidencia', 'metodo' => 'POST', 'url' => self::ENDPOINT_CATALOGOS_SUBTIPOS],
            ['nombre' => 'Actualizar Subtipo de Incidencia', 'metodo' => 'PUT', 'url' => self::ENDPOINT_CATALOGOS_SUBTIPOS_ANY],
            ['nombre' => 'Eliminar Subtipo de Incidencia', 'metodo' => 'DELETE', 'url' => self::ENDPOINT_CATALOGOS_SUBTIPOS_ANY],
            ['nombre' => 'Listar Roles Admin', 'metodo' => 'GET', 'url' => self::ENDPOINT_ADMIN_ROLES],
            ['nombre' => 'Crear Rol', 'metodo' => 'POST', 'url' => self::ENDPOINT_ADMIN_ROLES],
            ['nombre' => 'Actualizar Rol', 'metodo' => 'PUT', 'url' => self::ENDPOINT_ADMIN_ROLES_ANY],
            ['nombre' => 'Eliminar Rol', 'metodo' => 'DELETE', 'url' => self::ENDPOINT_ADMIN_ROLES_ANY],
            ['nombre' => 'Listar Opciones Sistema', 'metodo' => 'GET', 'url' => self::ENDPOINT_ADMIN_OPCIONES],
        ];

        foreach ($endpoints as $endpoint) {
            DB::table('endpoint')->updateOrInsert(
                ['url' => $endpoint['url'], 'metodo' => $endpoint['metodo']],
                [
                    'uuid' => DB::raw(self::UUID),
                    'nombre_endpoint' => $endpoint['nombre'],
                    'rbac_enabled' => true,
                    'deleted' => false,
                    'created_at' => now(),
                ]
            );
        }
    }

    private function seedOpcionEndpoints(): void
    {
        $this->mapOpcionEndpoint(self::OPCION_INCIDENCIAS_LECTURA_REPORTE, 'GET', self::ENDPOINT_INCIDENCIAS);
        $this->mapOpcionEndpoint(self::OPCION_INCIDENCIAS_LECTURA_REPORTE, 'GET', self::ENDPOINT_INCIDENCIAS_ANY);
        $this->mapOpcionEndpoint(self::OPCION_INCIDENCIAS_LECTURA_REPORTE, 'POST', self::ENDPOINT_INCIDENCIAS);
        $this->mapOpcionEndpoint(self::OPCION_INCIDENCIAS_LECTURA_REPORTE, 'PUT', self::ENDPOINT_INCIDENCIAS_ANY);
        $this->mapOpcionEndpoint(self::OPCION_INCIDENCIAS_LECTURA_REPORTE, 'DELETE', self::ENDPOINT_INCIDENCIAS_ANY);
        $this->mapOpcionEndpoint(self::OPCION_INCIDENCIAS_LECTURA_REPORTE, 'POST', self::ENDPOINT_INCIDENCIAS_COMENTARIOS);
        $this->mapOpcionEndpoint(self::OPCION_INCIDENCIAS_LECTURA_REPORTE, 'POST', self::ENDPOINT_INCIDENCIAS_EVIDENCIAS);
        $this->mapOpcionEndpoint(self::OPCION_INCIDENCIAS_LECTURA_REPORTE, 'GET', self::ENDPOINT_INCIDENCIAS_MENSAJES);
        $this->mapOpcionEndpoint(self::OPCION_PUBLICACIONES_GESTION, 'POST', self::ENDPOINT_PUBLICACIONES);
        $this->mapOpcionEndpoint(self::OPCION_PUBLICACIONES_GESTION, 'PUT', self::ENDPOINT_PUBLICACIONES_ANY);
        $this->mapOpcionEndpoint(self::OPCION_PUBLICACIONES_GESTION, 'DELETE', self::ENDPOINT_PUBLICACIONES_ANY);

        $this->mapOpcionEndpoint(self::OPCION_INCIDENCIAS_GESTION_OPERATIVA, 'POST', self::ENDPOINT_INCIDENCIAS_ESTADO);
        $this->mapOpcionEndpoint(self::OPCION_INCIDENCIAS_GESTION_OPERATIVA, 'POST', self::ENDPOINT_INCIDENCIAS_MENSAJES);

        $this->mapOpcionEndpoint(self::OPCION_INCIDENCIAS_GESTION_OPERATIVA, 'POST', self::ENDPOINT_INCIDENCIAS_COMENTARIOS);

        $this->mapOpcionEndpoint(self::OPCION_PERFIL_USUARIO, 'GET', self::ENDPOINT_USER);
        $this->mapOpcionEndpoint(self::OPCION_PERFIL_USUARIO, 'GET', self::ENDPOINT_PERFIL_STATS_TECNICO);

        $this->mapOpcionEndpoint(self::OPCION_GESTION_USUARIOS, 'GET', self::ENDPOINT_ROLES);
        $this->mapOpcionEndpoint(self::OPCION_GESTION_USUARIOS, 'GET', self::ENDPOINT_USUARIOS);
        $this->mapOpcionEndpoint(self::OPCION_GESTION_USUARIOS, 'GET', self::ENDPOINT_USUARIOS_TECNICOS);
        $this->mapOpcionEndpoint(self::OPCION_GESTION_USUARIOS, 'POST', self::ENDPOINT_USUARIOS);
        $this->mapOpcionEndpoint(self::OPCION_GESTION_USUARIOS, 'PUT', self::ENDPOINT_USUARIOS_ANY());
        $this->mapOpcionEndpoint(self::OPCION_GESTION_USUARIOS, 'PATCH', self::ENDPOINT_USUARIOS_TOGGLE);

        $this->mapOpcionEndpoint(self::OPCION_CATALOGOS, 'GET', self::ENDPOINT_CATALOGOS);
        $this->mapOpcionEndpoint(self::OPCION_CATALOGOS, 'GET', self::ENDPOINT_USUARIOS_TECNICOS);
        $this->mapOpcionEndpoint(self::OPCION_CATALOGOS, 'GET', self::ENDPOINT_DASHBOARD_STATS);
        $this->mapOpcionEndpoint(self::OPCION_CATALOGOS, 'PUT', self::ENDPOINT_CATALOGOS_ESTADOS_ANY);
        $this->mapOpcionEndpoint(self::OPCION_CATALOGOS, 'PUT', self::ENDPOINT_CATALOGOS_PRIORIDADES_ANY);
        $this->mapOpcionEndpoint(self::OPCION_CATALOGOS, 'POST', self::ENDPOINT_CATALOGOS_TIPOS);
        $this->mapOpcionEndpoint(self::OPCION_CATALOGOS, 'PUT', self::ENDPOINT_CATALOGOS_TIPOS_ANY);
        $this->mapOpcionEndpoint(self::OPCION_CATALOGOS, 'DELETE', self::ENDPOINT_CATALOGOS_TIPOS_ANY);
        $this->mapOpcionEndpoint(self::OPCION_CATALOGOS, 'POST', self::ENDPOINT_CATALOGOS_SUBTIPOS);
        $this->mapOpcionEndpoint(self::OPCION_CATALOGOS, 'PUT', self::ENDPOINT_CATALOGOS_SUBTIPOS_ANY);
        $this->mapOpcionEndpoint(self::OPCION_CATALOGOS, 'DELETE', self::ENDPOINT_CATALOGOS_SUBTIPOS_ANY);

        $this->mapOpcionEndpoint(self::OPCION_GESTION_ROLES, 'GET', self::ENDPOINT_ADMIN_ROLES);
        $this->mapOpcionEndpoint(self::OPCION_GESTION_ROLES, 'POST', self::ENDPOINT_ADMIN_ROLES);
        $this->mapOpcionEndpoint(self::OPCION_GESTION_ROLES, 'PUT', self::ENDPOINT_ADMIN_ROLES_ANY);
        $this->mapOpcionEndpoint(self::OPCION_GESTION_ROLES, 'DELETE', self::ENDPOINT_ADMIN_ROLES_ANY);
        $this->mapOpcionEndpoint(self::OPCION_GESTION_ROLES, 'GET', self::ENDPOINT_ADMIN_OPCIONES);
    }

    private function seedCatalogosDominio(): void
    {
        $this->seedEstados();
        $this->seedPrioridades();
        $this->seedTiposConSubtipos();
    }

    private function seedEstados(): void
    {
        $estados = [
            ['codigo' => 'Pendiente', 'label' => 'Recibido', 'orden' => 1, 'css_class' => 'badge-recibido', 'color_hex' => '#3B82F6'],
            ['codigo' => 'En Proceso', 'label' => 'En proceso', 'orden' => 2, 'css_class' => 'badge-proceso', 'color_hex' => '#F97316'],
            ['codigo' => 'Resuelta', 'label' => 'Resuelto', 'orden' => 3, 'css_class' => 'badge-resuelto', 'color_hex' => '#22C55E'],
            ['codigo' => 'Rechazada', 'label' => 'Rechazado', 'orden' => 4, 'css_class' => 'badge-urgente', 'color_hex' => '#EF4444'],
        ];

        foreach ($estados as $estado) {
            DB::table('catalogo_estado')->updateOrInsert(
                ['codigo' => $estado['codigo']],
                [
                    'uuid' => DB::raw(self::UUID),
                    'label' => $estado['label'],
                    'orden' => $estado['orden'],
                    'css_class' => $estado['css_class'],
                    'color_hex' => $estado['color_hex'],
                    'activo' => true,
                    'deleted' => false,
                    'created_at' => now(),
                ]
            );
        }
    }

    private function seedPrioridades(): void
    {
        $prioridades = [
            ['codigo' => 'Baja', 'label' => 'Baja', 'orden' => 1, 'css_class' => 'badge-baja', 'color_hex' => '#6B7280'],
            ['codigo' => 'Normal', 'label' => 'Normal', 'orden' => 2, 'css_class' => 'badge-media', 'color_hex' => '#8B5CF6'],
            ['codigo' => 'Media', 'label' => 'Media', 'orden' => 3, 'css_class' => 'badge-media', 'color_hex' => '#F97316'],
            ['codigo' => 'Alta', 'label' => 'Alta', 'orden' => 4, 'css_class' => 'badge-urgente', 'color_hex' => '#EF4444'],
            ['codigo' => 'Urgente', 'label' => 'Urgente', 'orden' => 5, 'css_class' => 'badge-urgente', 'color_hex' => '#B91C1C'],
        ];

        foreach ($prioridades as $prioridad) {
            DB::table('catalogo_prioridad')->updateOrInsert(
                ['codigo' => $prioridad['codigo']],
                [
                    'uuid' => DB::raw(self::UUID),
                    'label' => $prioridad['label'],
                    'orden' => $prioridad['orden'],
                    'css_class' => $prioridad['css_class'],
                    'color_hex' => $prioridad['color_hex'],
                    'activo' => true,
                    'deleted' => false,
                    'created_at' => now(),
                ]
            );
        }
    }

    private function seedTiposConSubtipos(): void
    {
        $tiposSubtipos = [
            ['nombre' => 'Infraestructura', 'icono' => 'bi-building', 'orden' => 1, 'subs' => ['Alumbrado', 'Vialidad', 'Agua potable', 'Alcantarillado']],
            ['nombre' => 'Seguridad', 'icono' => 'bi-shield', 'orden' => 2, 'subs' => ['Robo', 'Vandalismo', 'Iluminación']],
            ['nombre' => 'Medio ambiente', 'icono' => 'bi-tree', 'orden' => 3, 'subs' => ['Basura', 'Contaminación', 'Áreas verdes']],
            ['nombre' => 'Servicios', 'icono' => 'bi-tools', 'orden' => 4, 'subs' => ['Otro']],
        ];

        foreach ($tiposSubtipos as $tipo) {
            DB::table('catalogo_tipo_incidencia')->updateOrInsert(
                ['nombre' => $tipo['nombre']],
                [
                    'uuid' => DB::raw(self::UUID),
                    'icono_clase' => $tipo['icono'],
                    'orden' => $tipo['orden'],
                    'activo' => true,
                    'deleted' => false,
                    'created_at' => now(),
                ]
            );

            $tipoId = DB::table('catalogo_tipo_incidencia')->where('nombre', $tipo['nombre'])->value('id');

            foreach ($tipo['subs'] as $idx => $subtipo) {
                DB::table('catalogo_subtipo_incidencia')->updateOrInsert(
                    ['id_tipo' => $tipoId, 'nombre' => $subtipo],
                    [
                        'uuid' => DB::raw(self::UUID),
                        'orden' => $idx + 1,
                        'activo' => true,
                        'deleted' => false,
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    private function mapRolOpcion(int $rolId, string $opcionNombre, bool $lectura = true, bool $escritura = false): void
    {
        $opcionId = DB::table('opcion')->where('nombre_opcion', $opcionNombre)->value('id');

        if ($opcionId) {
            DB::table('rol_opcion')->updateOrInsert(
                ['id_rol' => $rolId, 'id_opcion' => $opcionId],
                [
                    'uuid' => DB::raw(self::UUID),
                    'lectura' => $lectura,
                    'escritura' => $escritura,
                    'deleted' => false,
                    'created_at' => now(),
                ]
            );
        }
    }

    private function mapOpcionEndpoint(string $opcionNombre, string $metodo, string $url): void
    {
        $opcionId = DB::table('opcion')->where('nombre_opcion', $opcionNombre)->value('id');
        $endpointId = DB::table('endpoint')->where('metodo', $metodo)->where('url', $url)->value('id');

        if ($opcionId && $endpointId) {
            DB::table('opcion_endpoint')->updateOrInsert(
                ['id_opcion' => $opcionId, 'id_endpoint' => $endpointId],
                ['uuid' => DB::raw(self::UUID), 'deleted' => false, 'created_at' => now()]
            );
        }
    }

    private static function ENDPOINT_USUARIOS_ANY(): string
    {
        return 'api/usuarios/*';
    }
}
