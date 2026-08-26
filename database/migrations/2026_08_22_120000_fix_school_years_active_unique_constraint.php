<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * L'ancienne contrainte unique(school_id, is_active) empêchait toute école
     * d'avoir plus d'une année scolaire INACTIVE, en plus de la contrainte
     * voulue (une seule année ACTIVE). On la remplace par une colonne générée
     * qui ne vaut school_id que pour les années actives (NULL sinon) : MySQL
     * autorise plusieurs NULL dans un index unique, donc seule l'unicité des
     * années actives est imposée.
     */
    public function up(): void
    {
        Schema::table('school_years', function (Blueprint $table) {
            // Le FK school_id -> schools.id s'appuyait sur cet index composite
            // (school_id en tête de colonne) : on le remplace par un index simple
            // avant de supprimer l'unique, sinon MySQL refuse le drop (erreur 1553).
            $table->index('school_id');
            $table->dropUnique('school_years_school_id_is_active_unique');
        });

        // VIRTUAL (pas STORED) : MySQL refuse d'ajouter une colonne générée STORED
        // par ALTER TABLE sur une table ayant une clé étrangère (erreur 1215).
        // SQLite n'a pas la fonction IF() : on utilise CASE WHEN, équivalent
        // supporté par les deux moteurs (les tests tournent sur sqlite :memory:).
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement(
                'ALTER TABLE school_years ADD COLUMN active_school_id INTEGER '
                . 'GENERATED ALWAYS AS (CASE WHEN is_active = 1 THEN school_id ELSE NULL END) VIRTUAL'
            );
        } else {
            DB::statement(
                'ALTER TABLE school_years ADD COLUMN active_school_id BIGINT UNSIGNED '
                . 'GENERATED ALWAYS AS (IF(is_active = 1, school_id, NULL)) VIRTUAL'
            );
        }

        Schema::table('school_years', function (Blueprint $table) {
            $table->unique('active_school_id');
        });
    }

    public function down(): void
    {
        Schema::table('school_years', function (Blueprint $table) {
            $table->dropUnique(['active_school_id']);
            $table->dropColumn('active_school_id');
            $table->unique(['school_id', 'is_active']);
            $table->dropIndex(['school_id']);
        });
    }
};
