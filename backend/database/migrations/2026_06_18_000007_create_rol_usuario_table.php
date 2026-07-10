<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rol_usuario', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->unique()->default(DB::raw('uuid_generate_v4()'));
            $table->unsignedInteger('id_rol');
            $table->unsignedInteger('id_usuario');

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();

            $table->boolean('deleted')->default(false);
            $table->timestampTz('deleted_at')->nullable();

            $table->unique(['id_rol', 'id_usuario'], 'uq_rol_usuario');

            // Llaves foráneas
            $table->foreign('id_rol')->references('id')->on('rol')->onDelete('restrict');
            $table->foreign('id_usuario')->references('id')->on('usuario')->onDelete('restrict');
        });

        DB::statement('ALTER TABLE rol_usuario ADD CONSTRAINT check_rol_usuario_deleted CHECK (
            (deleted = false AND deleted_at IS NULL)
            OR
            (deleted = true AND deleted_at IS NOT NULL)
        );');
    }

    public function down(): void
    {
        Schema::dropIfExists('rol_usuario');
    }
};
