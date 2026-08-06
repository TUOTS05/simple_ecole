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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('platform_name')->default('SaaS Ecole'); // Nom de la plateforme
            $table->string('support_email')->nullable(); // Email de support
            $table->string('support_phone')->nullable(); // Téléphone de support
            $table->text('support_address')->nullable(); // Adresse du support
            $table->string('logo')->nullable(); // Logo de la plateforme
            $table->string('favicon')->nullable(); // Favicon
            $table->text('terms_of_service')->nullable(); // Conditions d'utilisation
            $table->text('privacy_policy')->nullable(); // Politique de confidentialité
            $table->string('primary_color')->default('#3B82F6'); // Couleur principale
            $table->string('secondary_color')->default('#10B981'); // Couleur secondaire
            $table->boolean('maintenance_mode')->default(false); // Mode maintenance
            $table->text('maintenance_message')->nullable(); // Message de maintenance
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
