<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Asegurar la extensión para UUIDs
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');

        Schema::create('rol', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->unique()->default(DB::raw('uuid_generate_v4()'));
            $table->string('codigo', 50)->unique();
            $table->string('nombre_rol', 50)->unique();
            $table->string('descripcion', 255)->nullable();
            
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();
            
            $table->boolean('deleted')->default(false);
            $table->timestampTz('deleted_at')->nullable();
        });

        DB::statement("ALTER TABLE rol ADD CONSTRAINT check_rol_codigo CHECK (trim(codigo) <> '');");
        DB::statement("ALTER TABLE rol ADD CONSTRAINT check_rol_nombre CHECK (trim(nombre_rol) <> '');");
        DB::statement("ALTER TABLE rol ADD CONSTRAINT check_rol_deleted CHECK (
            (deleted = false AND deleted_at IS NULL)
            OR
            (deleted = true AND deleted_at IS NOT NULL)
        );");
    }

    public function down(): void
    {
        Schema::dropIfExists('rol');
    }
};
