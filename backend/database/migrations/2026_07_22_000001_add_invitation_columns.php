<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rol', function (Blueprint $table) {
            $table->boolean('req_token_invitacion')->default(false)->after('descripcion');
        });

        Schema::table('usuario', function (Blueprint $table) {
            $table->string('token_invitacion', 64)->nullable()->unique()->after('celular');
            $table->timestamp('fecha_invitacion')->nullable()->after('token_invitacion');
            $table->timestamp('fecha_expiracion_invitacion')->nullable()->after('fecha_invitacion');
            $table->timestamp('fecha_aceptacion')->nullable()->after('fecha_expiracion_invitacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rol', function (Blueprint $table) {
            $table->dropColumn('req_token_invitacion');
        });

        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn([
                'token_invitacion',
                'fecha_invitacion',
                'fecha_expiracion_invitacion',
                'fecha_aceptacion',
            ]);
        });
    }
};
