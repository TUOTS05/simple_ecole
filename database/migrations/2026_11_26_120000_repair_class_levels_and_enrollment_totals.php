<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Réparation des données existantes (aucun changement de structure) :
     *
     * 1. Classes au niveau « CP » dont le nom indique CP1 ou CP2 (ex. « CP1 A » en niveau CP) :
     *    leurs matières, configurées en CP1/CP2, n'étaient pas trouvées pour les bulletins.
     * 2. Classes sans cycle (anciennes données) : le cycle est déduit du niveau, sinon aucune
     *    matière n'était trouvée pour elles.
     * 3. Inscriptions dont le total de scolarité n'avait jamais été calculé (0 FCFA, « reste à
     *    payer » négatif) : total = somme des échéances, payé = somme payée sur ces échéances.
     */
    public function up(): void
    {
        $primaire = ['CP', 'CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'];
        $maternelle = ['TPS', 'PS', 'MS', 'GS'];

        DB::table('school_classes')->where('level', 'CP')->orderBy('id')->each(function ($class) {
            if (preg_match('/(^|[^A-Za-z0-9])CP([12])($|[^0-9])/i', $class->name, $m)) {
                DB::table('school_classes')->where('id', $class->id)->update(['level' => 'CP'.$m[2]]);
            }
        });

        DB::table('school_classes')->whereNull('cycle')->whereIn('level', $primaire)->update(['cycle' => 'primaire']);
        DB::table('school_classes')->whereNull('cycle')->whereIn('level', $maternelle)->update(['cycle' => 'maternelle']);

        DB::table('enrollments')->where('tuition_fee_total', 0)->orderBy('id')->each(function ($enrollment) {
            $installments = DB::table('student_installments')
                ->where('enrollment_id', $enrollment->id)
                ->where('type', 'installment');

            $total = (float) (clone $installments)->sum('amount');
            if ($total <= 0) {
                return;
            }

            $paid = (float) (clone $installments)->sum('paid_amount');
            DB::table('enrollments')->where('id', $enrollment->id)->update([
                'tuition_fee_total' => $total,
                'tuition_fee_paid' => $paid,
                'tuition_fee_remaining' => $total - $paid,
            ]);
        });

        // 4. Ancienne écriture du forfait « premium » → forfait existant « Premium »
        DB::table('schools')->where('subscription_plan', 'premium')->update(['subscription_plan' => 'Premium']);

        // 5. Ancien forfait « basic » (n'existe plus) → « Essentiel », avec ses plafonds
        $essentiel = DB::table('subscription_plans')->where('name', 'Essentiel')->first();
        if ($essentiel) {
            DB::table('schools')->where('subscription_plan', 'basic')->update([
                'subscription_plan' => 'Essentiel',
                'max_students' => $essentiel->max_students,
                'max_users' => $essentiel->max_users,
            ]);
        }
    }

    public function down(): void
    {
        // Réparation de données : rien à annuler.
    }
};
