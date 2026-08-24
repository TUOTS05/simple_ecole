<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ces tables pivot ont été créées dans
     * database/migrations/2026_07_08_140215_create_saas_ecole_all_tables.php
     * mais ne sont référencées par aucun modèle. Les vraies tables pivot
     * utilisées par l'application sont `student_school_class` et
     * `parent_student` (créées dans des migrations séparées).
     */
    public function up(): void
    {
        Schema::dropIfExists('class_student');
        Schema::dropIfExists('student_parent');
    }

    /**
     * Ces tables n'étaient jamais utilisées par l'application (aucun modèle,
     * aucune donnée exploitée) : on ne les recrée pas volontairement.
     */
    public function down(): void
    {
        // Intentionnellement vide : tables mortes, non recréées.
    }
};
