<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Présence crèche : arrivée et départ enregistrés enfant par enfant, avec la personne qui
 * récupère l'enfant (vérifiée dans la liste des personnes autorisées) et les retards.
 * Statuts : present, absent, absent_justified, left (parti). Le retard à l'arrivée est un
 * indicateur (is_late) pour ne pas être perdu quand l'enfant part.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creche_attendances', function (Blueprint $table) {
            $table->boolean('is_late')->default(false)->after('status');
            $table->string('absence_reason', 255)->nullable()->after('is_late');
            $table->foreignId('checked_in_by')->nullable()->after('checked_in_at')->constrained('users')->nullOnDelete();
            $table->foreignId('checked_out_by')->nullable()->after('checked_out_at')->constrained('users')->nullOnDelete();
            $table->foreignId('pickup_person_id')->nullable()->after('picked_up_by')->constrained('authorized_pickups')->nullOnDelete();
            $table->string('verification_mode', 20)->nullable()->after('pickup_person_id');
            $table->boolean('late_pickup')->default(false)->after('verification_mode');

            $table->index(['school_id', 'date']);
        });

        // Un enfant déjà reparti (heure de départ saisie) a désormais le statut « left ».
        DB::table('creche_attendances')->where('status', 'present')->whereNotNull('checked_out_at')->update(['status' => 'left']);
    }

    public function down(): void
    {
        DB::table('creche_attendances')->where('status', 'left')->update(['status' => 'present']);
        DB::table('creche_attendances')->where('status', 'absent_justified')->update(['status' => 'absent']);

        // MySQL remplace l'index de la clé étrangère school_id par l'index (school_id, date) :
        // il faut lui en redonner un avant de pouvoir supprimer ce dernier.
        $needsSchoolIndex = ! Schema::hasIndex('creche_attendances', 'creche_attendances_school_id_foreign');

        Schema::table('creche_attendances', function (Blueprint $table) use ($needsSchoolIndex) {
            $table->dropForeign(['checked_in_by']);
            $table->dropForeign(['checked_out_by']);
            $table->dropForeign(['pickup_person_id']);
            if ($needsSchoolIndex) {
                $table->index('school_id', 'creche_attendances_school_id_foreign');
            }
            $table->dropIndex(['school_id', 'date']);
            $table->dropColumn(['is_late', 'absence_reason', 'checked_in_by', 'checked_out_by', 'pickup_person_id', 'verification_mode', 'late_pickup']);
        });
    }
};
