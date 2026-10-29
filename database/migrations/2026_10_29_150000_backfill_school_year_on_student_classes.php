<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les élèves créés par le formulaire d'admission étaient rattachés à leur classe sans année scolaire :
 * ils n'apparaissaient dans aucune liste filtrée par année (cantine, goûter, extras, paiements).
 * L'année manquante est reprise de l'inscription de l'élève dans cette même classe.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('student_school_class')->whereNull('school_year_id')->get(['id', 'student_id', 'school_class_id']);

        foreach ($rows as $row) {
            $yearId = DB::table('enrollments')
                ->where('student_id', $row->student_id)
                ->where('school_class_id', $row->school_class_id)
                ->orderByDesc('id')
                ->value('school_year_id');

            if (! $yearId) {
                continue;
            }

            $duplicate = DB::table('student_school_class')
                ->where('student_id', $row->student_id)
                ->where('school_class_id', $row->school_class_id)
                ->where('school_year_id', $yearId)
                ->exists();

            // Si l'affectation datée existe déjà, la ligne sans année est un doublon.
            $duplicate
                ? DB::table('student_school_class')->where('id', $row->id)->delete()
                : DB::table('student_school_class')->where('id', $row->id)->update(['school_year_id' => $yearId]);
        }
    }

    public function down(): void
    {
        // Rien à annuler : une affectation sans année était une anomalie.
    }
};
