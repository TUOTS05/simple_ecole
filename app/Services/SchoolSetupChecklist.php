<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolRegulation;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;

/**
 * Parcours de mise en route d'une école, dans l'ordre où le faire : chaque étape indique si elle est
 * faite et mène à la page correspondante. Affiché sur le tableau de bord de l'administration.
 */
class SchoolSetupChecklist
{
    /**
     * @return list<array{title: string, help: string, done: bool, required: bool, route: string}>
     */
    public function stepsFor(School $school): array
    {
        $year = SchoolYear::where('school_id', $school->id)->where('is_active', true)->first();
        $classes = SchoolClass::where('school_id', $school->id);

        return [
            [
                'title' => "Compléter le profil de l'école",
                'help' => 'Adresse, téléphone et logo, repris sur les documents.',
                'done' => filled($school->address) && filled($school->phone),
                'required' => true,
                'route' => 'app.profile.edit',
            ],
            [
                'title' => "Ouvrir l'année scolaire",
                'help' => 'Toutes les inscriptions et tous les paiements y sont rattachés.',
                'done' => (bool) $year,
                'required' => true,
                'route' => 'app.school-years.index',
            ],
            [
                'title' => 'Créer les classes',
                'help' => 'Par cycle et par niveau, avec leur capacité.',
                'done' => (clone $classes)->exists(),
                'required' => true,
                'route' => 'app.classes.index',
            ],
            [
                'title' => 'Fixer les frais de scolarité de chaque classe',
                'help' => "Montant annuel, frais d'inscription et nombre d'échéances.",
                'done' => (clone $classes)->exists() && ! (clone $classes)->where(fn ($q) => $q->whereNull('total_tuition')->orWhere('total_tuition', 0))->exists(),
                'required' => true,
                'route' => 'app.class-fees.index',
            ],
            [
                'title' => 'Saisir les matières et leurs coefficients',
                'help' => 'Nécessaires aux notes et aux bulletins.',
                'done' => $year && Subject::where('school_id', $school->id)->where('school_year_id', $year->id)->exists(),
                'required' => true,
                'route' => 'app.subjects.index',
            ],
            [
                'title' => 'Créer les comptes enseignants',
                'help' => "Chaque enseignant fait l'appel et saisit les notes de ses classes.",
                'done' => User::where('school_id', $school->id)->where('role', 'teacher')->exists(),
                'required' => true,
                'route' => 'app.teachers.index',
            ],
            [
                'title' => 'Affecter les enseignants aux classes',
                'help' => 'Un enseignant ne voit que les classes qui lui sont affectées.',
                'done' => $year && TeacherAssignment::where('school_id', $school->id)->where('school_year_id', $year->id)->exists(),
                'required' => true,
                'route' => 'app.teacher-assignments.index',
            ],
            [
                'title' => 'Inscrire les élèves',
                'help' => 'Une admission à la fois, ou import de toute une liste depuis Excel.',
                'done' => Student::where('school_id', $school->id)->exists(),
                'required' => true,
                'route' => 'app.students.import',
            ],
            [
                'title' => 'Personnaliser les documents',
                'help' => 'En-tête, couleurs, cachet et signature des bulletins, cartes et reçus.',
                'done' => filled($school->settings['documents'] ?? null),
                'required' => false,
                'route' => 'app.settings.documents',
            ],
            [
                'title' => 'Publier le règlement intérieur',
                'help' => 'Les parents le lisent et l\'acceptent depuis leur espace.',
                'done' => SchoolRegulation::where('school_id', $school->id)->exists(),
                'required' => false,
                'route' => 'app.regulations.index',
            ],
        ];
    }
}
