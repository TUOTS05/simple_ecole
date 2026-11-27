<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

/**
 * Cloisonnement multi-écoles : vérifie que l'élève appartient à l'école de
 * l'utilisateur, pas une permission CRUD complète (le rôle est déjà filtré
 * par le middleware de route).
 *
 * Crèche : accès au dossier d'un enfant selon ses droits et ses groupes (une auxiliaire ne voit
 * que les enfants des groupes auxquels elle est affectée, en lecture seule).
 */
class StudentPolicy
{
    public function view(User $user, Student $student): bool
    {
        return $user->school_id !== null && $user->school_id === $student->school_id;
    }

    /** Consulter le dossier crèche (profil de prise en charge, personnes autorisées). */
    public function viewCrecheRecord(User $user, Student $student): bool
    {
        if (! $this->view($user, $student)) {
            return false;
        }

        if ($user->hasPermission('students.manage') || $user->hasPermission('creche.manage')) {
            return true;
        }

        if (! $user->hasPermission('creche.view') && ! $user->hasPermission('creche.attendance')) {
            return false;
        }

        $groupIds = $student->classes()->where('cycle', 'creche')->pluck('school_classes.id')->map(fn ($id) => (int) $id)->all();

        return (bool) array_intersect($groupIds, $user->accessibleCrecheClassIds());
    }

    /**
     * Enregistrer l'arrivée, l'absence ou le départ de l'enfant : accès à l'enfant + droit de
     * pointage (« attendances.view » donnait historiquement accès au pointage crèche).
     */
    public function recordCrecheAttendance(User $user, Student $student): bool
    {
        return $this->viewCrecheRecord($user, $student)
            && ($user->hasPermission('creche.attendance') || $user->hasPermission('creche.manage') || $user->hasPermission('attendances.view'));
    }

    /** Saisir le journal quotidien (biberons, repas, sommeil, changes, ...) d'un enfant de ses groupes. */
    public function recordCrecheJournal(User $user, Student $student): bool
    {
        return $this->viewCrecheRecord($user, $student)
            && ($user->hasPermission('creche.journal') || $user->hasPermission('creche.manage'));
    }

    /** Valider, annuler ou compléter les incidents d'un enfant : responsable crèche / direction. */
    public function validateCrecheIncident(User $user, Student $student): bool
    {
        return $this->viewCrecheRecord($user, $student) && $user->hasPermission('creche.incidents.validate');
    }

    /** Modifier le dossier crèche et les personnes autorisées : direction, responsable crèche, secrétariat. */
    public function updateCrecheRecord(User $user, Student $student): bool
    {
        return $this->view($user, $student)
            && ($user->hasPermission('creche.manage') || $user->hasPermission('students.manage'));
    }

    /** Voir la photo de l'enfant (servie depuis le stockage privé) : indispensable au retrait. */
    public function viewPhoto(User $user, Student $student): bool
    {
        return $this->view($user, $student)
            && ($user->hasPermission('students.manage') || $this->viewCrecheRecord($user, $student));
    }

    /** Voir les documents d'inscription : administration seulement (pas les auxiliaires). */
    public function viewDocuments(User $user, Student $student): bool
    {
        return $this->updateCrecheRecord($user, $student);
    }
}
