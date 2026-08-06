<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // ✅ Supprime l'ancien index qui empêchait d'avoir matin ET après-midi
            $table->dropUnique('attendances_student_id_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Sécurité : on remet l'ancien index si on annule la migration
            $table->unique(['student_id', 'date'], 'attendances_student_id_date_unique');
        });
    }
};