<?php

use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Ajoute un plan "Essai" gratuit, sélectionnable comme n'importe quel autre plan (formulaire
     * de demande de compte, activation par le super admin). Contrairement aux plans payants, il
     * n'attribue pas de subscription_end_date à l'école : c'est le champ trial_ends_at existant
     * qui pilote son échéance (voir SubscriptionController::approveRequest() et
     * School::isTrialActive()), pour qu'une école dessus déclenche bien la bannière "Essai
     * gratuit" et pas une facturation.
     */
    public function up(): void
    {
        SubscriptionPlan::firstOrCreate(
            ['name' => 'Essai'],
            [
                'slug' => 'ESSAI',
                'description' => "Pour découvrir la plateforme avant de choisir un forfait\n\n14 jours gratuits\nJusqu'à 30 élèves\n2 utilisateurs\nGestion des élèves\nInscriptions\nClasses & enseignants\nPrésences & absences\nNotes & bulletins",
                'max_students' => 30,
                'max_users' => 2,
                'max_classes' => 3,
                'monthly_price' => 0,
                'yearly_price' => 0,
                'is_active' => true,
                'sort_order' => -1,
            ]
        );
    }

    public function down(): void
    {
        SubscriptionPlan::where('name', 'Essai')->delete();
    }
};
