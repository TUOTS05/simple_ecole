<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('student_school_class', function (Blueprint $table) {
            // Ajouter l'année scolaire à la pivot
            $table->foreignId('school_year_id')
                  ->nullable()
                  ->constrained('school_years')
                  ->nullOnDelete()
                  ->after('school_class_id');

            // La clé étrangère student_id s'appuie sur l'index unique qu'on s'apprête
            // à remplacer : on ajoute un index simple pour qu'elle reste couverte.
            $table->index('student_id', 'student_school_class_student_id_index');

            // Mettre à jour la contrainte d'unicité pour inclure l'année scolaire
            $table->dropUnique(['student_id', 'school_class_id']);
            $table->unique(['student_id', 'school_class_id', 'school_year_id'], 'student_school_class_unique');
        });
    }

    public function down(): void {
        Schema::table('student_school_class', function (Blueprint $table) {
            $table->dropUnique('student_school_class_unique');
            $table->unique(['student_id', 'school_class_id']);
            $table->dropIndex('student_school_class_student_id_index');
            $table->dropForeign(['school_year_id']);
            $table->dropColumn('school_year_id');
        });
    }
};