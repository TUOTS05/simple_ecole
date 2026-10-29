<?php

/*
|--------------------------------------------------------------------------
| Modules attribuables aux fonctions administratives
|--------------------------------------------------------------------------
|
| Chaque école crée ses fonctions (Directeur des études, Censeur, Économe…) et coche les
| modules auxquels elles donnent accès. Un membre du personnel (rôle « staff ») n'accède
| qu'aux routes listées dans les modules de sa fonction (voir CheckRole) : tout ce qui
| n'est pas listé ici — paramètres de l'école, documents, règlement, gestion du personnel,
| abonnement — reste réservé à l'administrateur de l'école.
|
| 'routes' : motifs de noms de route (syntaxe Str::is) ouverts par le module.
| 'home'   : page d'arrivée du module dans le menu du personnel.
| 'links'  : (facultatif) plusieurs entrées de menu au lieu d'une seule vers 'home'.
|
*/

return [

    // Ouvert à tout membre du personnel, quelle que soit sa fonction (son propre profil)
    'always' => [
        'app.staff-profile.*',
    ],

    'modules' => [
        'students' => [
            'label' => 'Élèves',
            'description' => 'Liste, fiches et dossiers des élèves, cartes scolaires, exports.',
            'icon' => '👥',
            'home' => 'app.students.index',
            'routes' => ['app.students.*'],
        ],
        'enrollments' => [
            'label' => 'Inscriptions',
            'description' => 'Inscriptions et réinscriptions, échéanciers.',
            'icon' => '📝',
            'home' => 'app.enrollments.index',
            'routes' => ['app.enrollments.*', 'app.installments.*', 'app.students.by-class'],
        ],
        'payments' => [
            'label' => 'Paiements de scolarité',
            'description' => 'Encaissement des paiements et reçus.',
            'icon' => '💳',
            'home' => 'app.payments.index',
            'routes' => ['app.payments.*', 'app.installments.*', 'app.students.by-class'],
        ],
        'financial' => [
            'label' => 'États financiers',
            'description' => 'Impayés par classe et par élève, exports.',
            'icon' => '📈',
            'home' => 'app.financial.unpaid_by_class',
            'routes' => ['app.financial.*'],
        ],
        'fees' => [
            'label' => 'Configuration des frais',
            'description' => 'Frais de scolarité et frais par classe.',
            'icon' => '💰',
            'home' => 'app.class-fees.index',
            'routes' => ['app.fees.*', 'app.class-fees.*'],
        ],
        'report_cards' => [
            'label' => 'Bulletins',
            'description' => 'Création, consultation et impression des bulletins.',
            'icon' => '📄',
            'home' => 'app.report-cards.index',
            'routes' => ['app.report-cards.*'],
        ],
        'attendances' => [
            'label' => 'Présences',
            'description' => 'Suivi des présences et absences (et pointage crèche).',
            'icon' => '✅',
            'home' => 'app.attendances.index',
            'routes' => ['app.attendances.*', 'app.creche-attendances.*'],
        ],
        'end_of_year' => [
            'label' => "Fin d'année & passage",
            'description' => 'Décisions de passage et migration des classes.',
            'icon' => '🎓',
            'home' => 'app.end-of-year.index',
            'routes' => ['app.end-of-year.*'],
        ],
        'classes' => [
            'label' => 'Classes, matières & années',
            'description' => 'Organisation pédagogique : classes, matières, années scolaires.',
            'icon' => '🏫',
            'home' => 'app.classes.index',
            'routes' => ['app.classes.*', 'app.subjects.*', 'app.school-years.*'],
        ],
        'teachers' => [
            'label' => 'Enseignants',
            'description' => 'Comptes enseignants et affectations aux classes.',
            'icon' => '👨‍🏫',
            'home' => 'app.teachers.index',
            'routes' => ['app.teachers.*', 'app.teacher-assignments.*'],
        ],
        'parents' => [
            'label' => 'Annuaire des parents',
            'description' => 'Coordonnées des parents et enfants liés.',
            'icon' => '👪',
            'home' => 'app.parents.index',
            'routes' => ['app.parents.*'],
        ],
        'messages' => [
            'label' => 'Messagerie',
            'description' => 'Messages des parents et diffusions de l\'école.',
            'icon' => '💬',
            'home' => 'app.messages.index',
            'routes' => ['app.messages.*'],
        ],
        'notifications' => [
            'label' => 'Journal des notifications',
            'description' => 'Historique des SMS et notifications envoyés.',
            'icon' => '🔔',
            'home' => 'app.notifications.index',
            'routes' => ['app.notifications.*'],
        ],
        'canteen' => [
            'label' => 'Cantine',
            'description' => 'Tarifs, abonnements et paiements de la cantine.',
            'icon' => '🍽️',
            'home' => 'canteen.subscriptions.index',
            'routes' => ['canteen.*'],
        ],
        'gouter' => [
            'label' => 'Goûter',
            'description' => 'Tarifs, abonnements et paiements du goûter.',
            'icon' => '🥪',
            'home' => 'gouter.subscriptions.index',
            'routes' => ['gouter.*'],
        ],
        'extras_billing' => [
            'label' => 'Extras : inscriptions & paiements',
            'description' => 'Inscriptions aux extras, encaissements, remboursements, factures consolidées et impayés.',
            'icon' => '🧾',
            'home' => 'extras.subscriptions.index',
            'links' => [
                ['label' => 'Inscriptions Extras', 'icon' => '🧩', 'route' => 'extras.subscriptions.index', 'active' => 'extras.subscriptions.*'],
                ['label' => 'Paiements Extras', 'icon' => '💰', 'route' => 'extras.payments.index', 'active' => 'extras.payments.*'],
                ['label' => 'Remboursements Extras', 'icon' => '💸', 'route' => 'extras.refunds.index', 'active' => 'extras.refunds.*'],
            ],
            'routes' => [
                'extras.dashboard', 'extras.subscriptions.*', 'extras.payments.*', 'extras.refunds.*',
                'extras.invoices.*', 'extras.reports.*',
                'extras.classes-by-cycle', 'extras.students-by-class', 'extras.tarif-for-class',
            ],
        ],
        'extras_catalogue' => [
            'label' => 'Extras : catalogue & stocks',
            'description' => 'Catégories, activités, tarifs, plannings et stocks des extras.',
            'icon' => '🧩',
            'home' => 'extras.catalogue.index',
            'routes' => [
                'extras.categories.*', 'extras.catalogue.*', 'extras.tarifs.*', 'extras.schedules.*', 'extras.stocks.*',
            ],
        ],
        'extras_canteen' => [
            'label' => 'Extras : présences & menus',
            'description' => 'Pointage des présences/consommations (QR) et menus de la cantine.',
            'icon' => '✅',
            'home' => 'extras.attendances.index',
            'links' => [
                ['label' => 'Présences / Consommations', 'icon' => '✅', 'route' => 'extras.attendances.index', 'active' => 'extras.attendances.index'],
                ['label' => 'Pointage rapide (QR)', 'icon' => '📷', 'route' => 'extras.attendances.scan', 'active' => 'extras.attendances.scan*'],
                ['label' => 'Menus Cantine', 'icon' => '🍽️', 'route' => 'extras.menus.index', 'active' => 'extras.menus.*'],
            ],
            'routes' => ['extras.attendances.*', 'extras.menus.*'],
        ],
        'transport' => [
            'label' => 'Transport',
            'description' => 'Véhicules, circuits, affectations et suivi GPS.',
            'icon' => '🚐',
            'home' => 'extras.transport.vehicles.index',
            'links' => [
                ['label' => 'Véhicules', 'icon' => '🚐', 'route' => 'extras.transport.vehicles.index', 'active' => 'extras.transport.vehicles.*'],
                ['label' => 'Circuits', 'icon' => '🗺️', 'route' => 'extras.transport.routes.index', 'active' => ['extras.transport.routes.*', 'extras.transport.stops.*']],
                ['label' => 'Affectations élèves', 'icon' => '🎒', 'route' => 'extras.transport.assignments.index', 'active' => 'extras.transport.assignments.*'],
                ['label' => 'Suivi GPS', 'icon' => '📍', 'route' => 'extras.transport.tracking.index', 'active' => 'extras.transport.tracking.*'],
            ],
            'routes' => ['extras.transport.*'],
        ],
    ],

    // Fonctions proposées à la création (l'école peut les modifier ou les supprimer)
    'suggested_functions' => [
        'Directeur des études' => ['students', 'report_cards', 'attendances', 'end_of_year', 'classes', 'teachers', 'parents', 'messages'],
        'Surveillant' => ['students', 'attendances'],
        'Comptable' => ['enrollments', 'payments', 'extras_billing'],
        'Économe' => ['enrollments', 'payments', 'financial', 'fees', 'extras_billing'],
        'Secrétaire' => ['students', 'enrollments', 'parents', 'messages'],
        'Responsable cantine' => ['extras_canteen'],
        'Responsable transport' => ['transport'],
    ],
];
