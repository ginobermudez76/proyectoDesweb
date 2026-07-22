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
        Schema::create('webauthn_credentials', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('id_usuario')->constrained('usuario')->onDelete('cascade');
            $table->text('credential_id'); // Base64URL string of credential ID
            $table->text('public_key');    // COSE encoded public key
            $table->unsignedBigInteger('counter')->default(0);
            $table->string('name', 100)->default('Passkey');
            $table->boolean('deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webauthn_credentials');
    }
};
