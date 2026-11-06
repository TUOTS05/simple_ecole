<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les totaux de scolarité des inscriptions (tuition_fee_total, tuition_fee_paid, tuition_fee_remaining)
 * n'étaient jamais renseignés : le tableau de bord du parent, sa fiche élève et le détail d'une
 * inscription affichaient 0 FCFA. Ils sont recalculés ici à partir des échéances de scolarité, comme le
 * fait désormais Enrollment::recalculateFees().
 */
return new class extends Migration
{
    public function up(): void
    {
        $sum = fn (string $column) => "(SELECT COALESCE(SUM($column), 0) FROM student_installments
            WHERE student_installments.enrollment_id = enrollments.id AND student_installments.type <> 'registration')";

        DB::statement("UPDATE enrollments SET
            tuition_fee_total = {$sum('amount')},
            tuition_fee_paid = {$sum('paid_amount')},
            tuition_fee_remaining = {$sum('amount')} - {$sum('paid_amount')}");
    }

    public function down(): void
    {
        // Rien à annuler : les anciennes valeurs (0) étaient fausses.
    }
};
