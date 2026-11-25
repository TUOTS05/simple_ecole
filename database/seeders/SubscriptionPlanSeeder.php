<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

/**
 * Forfaits tels qu'affichés sur https://simpleecole.top/#pricing (relevé du 25/11/2026),
 * SANS le plan "Essai" (retiré par la migration remove_essai_subscription_plan).
 *
 * Les tarifs sont ensuite gérés par le super admin (Super Admin > Plans d'abonnement) :
 * ce seeder sert à initialiser une base vide (local, recette), il n'est pas lancé
 * automatiquement au déploiement et n'écrase que les plans portant ces slugs.
 *
 * Format de "description" attendu par landing.blade.php :
 *   accroche
 *   (ligne vide)
 *   ligne prix (ignorée à l'affichage, "Sur devis" => plan sur devis)
 *   une fonctionnalité par ligne
 *
 * Le prix annuel est recalculé automatiquement (12 x mensuel) par le modèle.
 */
class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'essentiel',
                'name' => 'Essentiel',
                'monthly_price' => 10000,
                'max_students' => 100,
                'max_users' => 3,
                'is_recommended' => true,
                'sort_order' => 1,
                'tagline' => 'Pour les petites écoles',
                'price_line' => '10 000 FCFA/mois',
                'features' => [
                    "Jusqu'à 100 élèves",
                    '3 utilisateurs',
                    'Gestion des élèves',
                    'Inscriptions & réinscriptions',
                    'Classes & enseignants',
                    'Présences & absences',
                    'Notes & bulletins',
                    'Gestion de la scolarité',
                    'Communication avec les parents',
                    'Tableau de bord',
                    'Support standard',
                ],
            ],
            [
                'slug' => 'croissance',
                'name' => 'Croissance',
                'monthly_price' => 20000,
                'max_students' => 250,
                'max_users' => 5,
                'is_recommended' => false,
                'sort_order' => 2,
                'tagline' => 'Pour les écoles en développement — Le meilleur rapport qualité/prix',
                'price_line' => '20 000 FCFA/mois',
                'features' => [
                    "Jusqu'à 250 élèves",
                    '5 utilisateurs',
                    'Toutes les fonctionnalités essentielles',
                    'Gestion avancée de la scolarité',
                    'Suivi des impayés',
                    'Reçus & états financiers',
                    'Bulletins personnalisables',
                    'Alertes & communication parents',
                    'Statistiques & rapports',
                    'Formation initiale INCLUSE',
                    'Support prioritaire',
                ],
            ],
            [
                'slug' => 'premium',
                'name' => 'Premium',
                'monthly_price' => 35000,
                'max_students' => 500,
                'max_users' => 10,
                'is_recommended' => false,
                'sort_order' => 3,
                'tagline' => 'Pour les écoles importantes',
                'price_line' => '35 000 FCFA/mois',
                'features' => [
                    "Jusqu'à 500 élèves",
                    '10 utilisateurs',
                    'Toutes les fonctionnalités Croissance',
                    'Gestion avancée des droits',
                    'Tableaux de bord de direction',
                    'Rapports avancés',
                    'Suivi des performances',
                    'Communication avancée avec les parents',
                    'Documents personnalisables',
                    'Formation du personnel INCLUSE',
                    'Support prioritaire 7j/7',
                ],
            ],
            [
                'slug' => 'sur-devis',
                'name' => 'Sur Devis',
                'monthly_price' => 0,
                'max_students' => SubscriptionPlan::UNLIMITED,
                'max_users' => SubscriptionPlan::UNLIMITED,
                'is_recommended' => false,
                'sort_order' => 4,
                'tagline' => 'Pour les groupes scolaires',
                'price_line' => 'Sur devis',
                'features' => [
                    'Élèves illimités',
                    'Utilisateurs illimités',
                    'Gestion multi-écoles',
                    'Gestion multi-sites',
                    'Administration centralisée',
                    'Tableau de bord du groupe',
                    'Rapports consolidés',
                    'Personnalisation avancée',
                    'Accompagnement dédié',
                    'Support prioritaire',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                [
                    'name' => $plan['name'],
                    'description' => $plan['tagline']."\n\n".$plan['price_line']."\n".implode("\n", $plan['features']),
                    'monthly_price' => $plan['monthly_price'],
                    'max_students' => $plan['max_students'],
                    'max_users' => $plan['max_users'],
                    // Non affiché ni contrôlé par l'application : pas de plafond de classes.
                    'max_classes' => SubscriptionPlan::UNLIMITED,
                    'is_active' => true,
                    'is_recommended' => $plan['is_recommended'],
                    'sort_order' => $plan['sort_order'],
                ]
            );
        }

        $this->command?->info('✅ '.count($plans).' forfaits (sans Essai) créés / mis à jour.');
    }
}
