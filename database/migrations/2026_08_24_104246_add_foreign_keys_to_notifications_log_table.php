<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table vérifiée vide au moment de l'ajout de ces contraintes
     * (aucune ligne orpheline), donc aucun nettoyage préalable requis.
     *
     * - school_id / student_id : colonnes NOT NULL, cascadeOnDelete()
     *   (cohérent avec le reste du projet : la suppression réelle d'une
     *   école ou d'un élève supprime ses logs de notification).
     * - installment_id / parent_id : colonnes déjà nullable, nullOnDelete()
     *   pour conserver l'historique de notification même si l'échéance
     *   ou le parent référencé est supprimé.
     */
    public function up(): void
    {
        Schema::table('notifications_log', function (Blueprint $table) {
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('installment_id')->references('id')->on('student_installments')->nullOnDelete();
            $table->foreign('parent_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notifications_log', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropForeign(['student_id']);
            $table->dropForeign(['installment_id']);
            $table->dropForeign(['parent_id']);
        });
    }
};
