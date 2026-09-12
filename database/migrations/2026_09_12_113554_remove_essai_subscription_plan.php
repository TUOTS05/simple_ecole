<?php

use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Retire le plan "Essai" ajouté par la migration add_essai_subscription_plan : il n'est
     * plus proposé comme choix de forfait (formulaire de demande de compte, page publique).
     * Ne touche pas aux écoles déjà sur ce plan (subscription_plan reste "Essai" en base) ni à
     * School::isTrialActive(), redevenu indépendant du plan (voir revert du commit 521207e).
     */
    public function up(): void
    {
        SubscriptionPlan::where('name', 'Essai')->delete();
    }

    public function down(): void
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
};
