<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endpoint', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->unique()->default(DB::raw('uuid_generate_v4()'));
            $table->string('nombre_endpoint', 50)->unique();
            $table->string('metodo', 10);
            $table->string('url', 255);
            $table->boolean('rbac_enabled')->default(false);
            
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();
            
            $table->boolean('deleted')->default(false);
            $table->timestampTz('deleted_at')->nullable();

            $table->unique(['url', 'metodo'], 'uq_endpoint_url_metodo');
        });

        DB::statement("ALTER TABLE endpoint ADD CONSTRAINT check_endpoint_metodo CHECK (metodo IN ('GET', 'POST', 'PUT', 'PATCH', 'DELETE'));");
        DB::statement("ALTER TABLE endpoint ADD CONSTRAINT check_endpoint_deleted CHECK (
            (deleted = false AND deleted_at IS NULL)
            OR
            (deleted = true AND deleted_at IS NOT NULL)
        );");
    }

    public function down(): void
    {
        Schema::dropIfExists('endpoint');
    }
};
