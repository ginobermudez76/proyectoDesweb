<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->unique()->default(DB::raw('uuid_generate_v4()'));
            $table->string('nombre_usuario', 50)->unique();
            $table->string('correo_electronico', 255)->unique();
            $table->string('password_hash', 255);
            $table->string('nombres', 50);
            $table->string('apellidos', 50);
            $table->boolean('activo')->default(false);
            
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();
            
            $table->boolean('deleted')->default(false);
            $table->timestampTz('deleted_at')->nullable();
        });

        DB::statement("ALTER TABLE usuario ADD CONSTRAINT check_usuario_nombre CHECK (trim(nombre_usuario) <> '');");
        DB::statement("ALTER TABLE usuario ADD CONSTRAINT check_usuario_deleted CHECK (
            (deleted = false AND deleted_at IS NULL)
            OR
            (deleted = true AND deleted_at IS NOT NULL)
        );");
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};
