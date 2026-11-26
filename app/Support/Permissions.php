<?php

namespace App\Support;

/**
 * Catalogue des droits du personnel d'une école.
 *
 * Chaque membre du personnel a un rôle (Enseignant, Comptable, ...) qui lui donne des droits
 * par défaut. Le directeur peut ensuite cocher/décocher des droits pour une personne précise :
 * la liste personnalisée est alors stockée dans users.permissions (null = droits du rôle).
 *
 * Le directeur (school_admin) a toujours tous les droits. La gestion du personnel et de ses
 * droits lui est réservée (non attribuable) pour empêcher qu'un employé s'octroie des droits.
 */
class Permissions
{
    /** Droits attribuables, regroupés pour l'affichage du formulaire. */
    public const GROUPS = [
        'Pilotage' => [
            'dashboard.view' => 'Voir le tableau de bord',
        ],
        'Élèves & scolarité' => [
            'students.manage' => 'Gérer les élèves (fiches, cartes, exports)',
            'parents.view' => "Consulter l'annuaire des parents",
            'enrollments.manage' => 'Gérer les inscriptions',
            'payments.manage' => 'Encaisser les paiements de scolarité',
            'financial.view' => 'Consulter les états financiers et les relances',
        ],
        'Pédagogie' => [
            'classes.manage' => 'Gérer les classes',
            'subjects.manage' => 'Gérer les matières',
            'report_cards.manage' => 'Gérer les bulletins et la fin d\'année',
            'attendances.view' => 'Consulter les présences (et le pointage crèche)',
        ],
        'Communication' => [
            'messages.manage' => 'Messagerie avec les parents',
        ],
        'Services' => [
            'canteen.manage' => 'Cantine scolaire (tarifs, inscriptions, paiements)',
            'gouter.manage' => 'Goûter maternelle',
            'extras.manage' => 'Configurer les extras (catalogue, tarifs, stocks)',
            'extras.billing' => 'Inscriptions, paiements et remboursements des extras',
            'extras.canteen' => 'Extras cantine : présences et menus',
            'extras.transport' => 'Transport : véhicules, circuits, suivi GPS',
        ],
        'Administration' => [
            'settings.manage' => "Paramètres de l'école (années, frais, SMS)",
        ],
    ];

    /** Droit réservé au directeur, jamais attribuable à un membre du personnel. */
    public const STAFF_MANAGE = 'staff.manage';

    /** Rôles du personnel gérés depuis l'écran « Personnel ». */
    public const ROLES = [
        'teacher' => 'Enseignant',
        'secretary' => 'Secrétaire',
        'accountant' => 'Comptable',
        'canteen_manager' => 'Responsable cantine',
        'transport_manager' => 'Responsable transport',
    ];

    /** Droits par défaut de chaque rôle (reprend les accès historiques de chaque rôle). */
    public const ROLE_DEFAULTS = [
        // L'enseignant travaille dans son propre espace (appel, notes) : aucun écran
        // d'administration par défaut, le directeur peut en ouvrir au cas par cas.
        'teacher' => [],
        'secretary' => ['students.manage', 'enrollments.manage', 'classes.manage', 'attendances.view', 'messages.manage'],
        'accountant' => ['enrollments.manage', 'payments.manage', 'extras.billing'],
        'canteen_manager' => ['extras.canteen'],
        'transport_manager' => ['extras.transport'],
    ];

    /**
     * Page d'accueil après connexion : premier écran autorisé dans cet ordre.
     * L'ordre reproduit les pages d'arrivée historiques (comptable → inscriptions, etc.).
     */
    public const HOME_ROUTES = [
        'dashboard.view' => 'app.dashboard',
        'enrollments.manage' => 'app.enrollments.index',
        'payments.manage' => 'app.payments.index',
        'students.manage' => 'app.students.index',
        'extras.canteen' => 'extras.attendances.index',
        'extras.transport' => 'extras.transport.vehicles.index',
        'extras.billing' => 'extras.subscriptions.index',
        'classes.manage' => 'app.classes.index',
        'report_cards.manage' => 'app.report-cards.index',
        'attendances.view' => 'app.attendances.index',
        'messages.manage' => 'app.messages.index',
        'financial.view' => 'app.financial.unpaid_by_class',
        'subjects.manage' => 'app.subjects.index',
        'parents.view' => 'app.parents.index',
        'canteen.manage' => 'canteen.subscriptions.index',
        'gouter.manage' => 'gouter.subscriptions.index',
        'extras.manage' => 'extras.catalogue.index',
        'settings.manage' => 'app.school-years.index',
    ];

    /** @return list<string> */
    public static function all(): array
    {
        return array_merge(...array_values(array_map('array_keys', self::GROUPS)));
    }

    public static function exists(string $permission): bool
    {
        return $permission === self::STAFF_MANAGE || in_array($permission, self::all(), true);
    }

    /** @return list<string> */
    public static function defaultsFor(?string $role): array
    {
        return self::ROLE_DEFAULTS[$role] ?? [];
    }

    public static function roleLabel(?string $role): string
    {
        return self::ROLES[$role] ?? ($role === 'school_admin' ? 'Directeur' : (string) $role);
    }

    /** Ne garde que des droits connus et attribuables, dans l'ordre du catalogue. */
    public static function sanitize(array $permissions): array
    {
        return array_values(array_intersect(self::all(), $permissions));
    }
}
