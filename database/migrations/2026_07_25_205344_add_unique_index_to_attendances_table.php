<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // ✅ Création d'un index unique combiné pour permettre le "upsert"
            // Cela garantit qu'un élève ne peut avoir qu'un seul statut par classe, date et période
            $table->unique(
                ['school_class_id', 'student_id', 'date', 'period'], 
                'attendances_unique_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendances_unique_index');
        });
    }
};