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
            $table->boolean('aut_2fa_obligatoria')->default(false)->after('req_token_invitacion');
        });

        Schema::table('usuario', function (Blueprint $table) {
            $table->text('aut_2fa_secret')->nullable()->after('fecha_aceptacion');
            $table->boolean('aut_app_autenticacion')->default(false)->after('aut_2fa_secret');
            $table->boolean('aut_email')->default(false)->after('aut_app_autenticacion');
            $table->boolean('aut_passkeys')->default(false)->after('aut_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rol', function (Blueprint $table) {
            $table->dropColumn('aut_2fa_obligatoria');
        });

        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn([
                'aut_2fa_secret',
                'aut_app_autenticacion',
                'aut_email',
                'aut_passkeys',
            ]);
        });
    }
};
