<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opcion_endpoint', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->unique()->default(DB::raw('uuid_generate_v4()'));
            $table->unsignedInteger('id_opcion');
            $table->unsignedInteger('id_endpoint');

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();

            $table->boolean('deleted')->default(false);
            $table->timestampTz('deleted_at')->nullable();

            $table->unique(['id_opcion', 'id_endpoint'], 'uq_opcion_endpoint');

            // Llaves foráneas
            $table->foreign('id_opcion')->references('id')->on('opcion')->onDelete('restrict');
            $table->foreign('id_endpoint')->references('id')->on('endpoint')->onDelete('restrict');
        });

        DB::statement('ALTER TABLE opcion_endpoint ADD CONSTRAINT check_opcion_endpoint_deleted CHECK (
            (deleted = false AND deleted_at IS NULL)
            OR
            (deleted = true AND deleted_at IS NOT NULL)
        );');
    }

    public function down(): void
    {
        Schema::dropIfExists('opcion_endpoint');
    }
};
