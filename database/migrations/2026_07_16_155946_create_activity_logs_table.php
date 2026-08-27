<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_name'); // Nom de l'utilisateur qui a fait l'action
            $table->string('user_role')->default('super_admin'); // Rôle (super_admin, school_admin)
            $table->string('action'); // Ex: 'created_school', 'renewed_contract'
            $table->string('description'); // Ex: 'A créé l\'école "Groupe Scolaire Chigata"'
            $table->string('ip_address')->nullable(); // Adresse IP pour la sécurité
            $table->timestamps();

            // Index pour accélérer les recherches
            $table->index(['user_role', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
