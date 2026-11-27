<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\CareEvent;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Journal quotidien : enregistre les événements d'un ou de plusieurs enfants à la fois
 * (mode groupe : un événement par enfant, modifiable ensuite individuellement), les modifie
 * et les supprime (suppression douce, traçable). Chaque opération est journalisée.
 */
class CareJournalService
{
    /** Règles de validation des champs propres à un type d'événement. */
    public function rules(string $type, School $school): array
    {
        $rules = [
            'start_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:1000',
        ];

        // array_merge : la règle propre au type remplace la règle commune (ex. notes obligatoires).
        return array_merge($rules, match ($type) {
            'bottle' => [
                'quantity' => 'required|numeric|gt:0|max:1000',
                'consumed' => 'nullable|numeric|min:0|lte:quantity',
                'milk' => 'nullable|string|max:100',
                'preparation' => 'nullable|string|max:150',
            ],
            'meal', 'snack' => [
                'menu' => 'nullable|string|max:150',
                'appreciation' => ['required', Rule::in(array_keys(CareEvent::APPRECIATIONS))],
            ],
            'sleep' => [
                'end_time' => 'nullable|date_format:H:i|after:start_time',
                'quality' => ['nullable', Rule::in(array_keys(CareEvent::SLEEP_QUALITIES))],
            ],
            'diaper' => [
                'kind' => ['required', Rule::in(array_keys(CareEvent::DIAPER_KINDS))],
                'product' => 'nullable|string|max:150',
            ],
            'hygiene' => [
                'kind' => ['required', 'string', Rule::in((array) $school->setting('creche.hygiene_types'))],
            ],
            'activity' => [
                'category' => ['required', 'string', Rule::in((array) $school->setting('creche.activity_categories'))],
                'title' => 'nullable|string|max:150',
                'duration' => 'nullable|integer|min:1|max:480',
            ],
            'observation', 'other' => [
                'notes' => 'required|string|max:1000',
            ],
            default => [],
        });
    }

    public static function messages(): array
    {
        return [
            'quantity.required' => 'Indiquez la quantité préparée.',
            'quantity.gt' => 'La quantité doit être positive.',
            'consumed.lte' => 'La quantité bue ne peut pas dépasser la quantité préparée.',
            'appreciation.required' => 'Indiquez ce que l\'enfant a mangé.',
            'end_time.after' => 'L\'heure de réveil doit être après l\'heure de coucher.',
            'kind.required' => 'Choisissez le type.',
            'category.required' => 'Choisissez la catégorie d\'activité.',
            'category.in' => 'Cette catégorie n\'existe pas (Crèche › Paramètres).',
            'notes.required' => 'Écrivez l\'observation.',
        ];
    }

    /**
     * Enregistre le même événement pour chaque enfant (un seul enfant ou mode groupe).
     *
     * @param  Collection<int, Student>  $students
     * @return Collection<int, CareEvent>
     */
    public function record(string $type, Collection $students, User $user, array $input): Collection
    {
        return DB::transaction(function () use ($type, $students, $user, $input) {
            return $students->map(function (Student $student) use ($type, $user, $input) {
                $event = new CareEvent(['event_type' => $type, 'event_date' => today()->toDateString()]);
                $event->fill($this->attributesFor($type, $input));
                $event->school_id = $student->school_id;
                $event->student_id = $student->id;
                $event->school_class_id = $student->classes()->where('cycle', 'creche')->value('school_classes.id');
                $event->staff_id = $user->id;
                $event->save();

                ActivityLog::audit(
                    'creche.journal.added',
                    "{$event->type_label} de {$student->first_name} {$student->last_name} à {$event->startLabel()}".($event->summary() ? ' : '.$event->summary() : ''),
                    $student,
                    ['event_id' => $event->id, 'type' => $type, 'group_size' => count($input['student_ids'] ?? [1])]
                );

                return $event;
            });
        });
    }

    public function update(CareEvent $event, User $user, array $input): CareEvent
    {
        $before = $this->snapshot($event);

        $event->fill($this->attributesFor($event->event_type, $input));
        $event->updated_by = $user->id;
        $event->save();

        $student = $event->student;
        ActivityLog::audit(
            'creche.journal.updated',
            "A modifié : {$event->type_label} de {$student->first_name} {$student->last_name}",
            $student,
            ['event_id' => $event->id, 'before' => $before, 'after' => $this->snapshot($event)]
        );

        return $event;
    }

    /** Réveil : fin du sommeil en cours. */
    public function wake(CareEvent $event, User $user, string $time): CareEvent
    {
        $event->end_time = $time;
        $event->updated_by = $user->id;
        $event->save();

        $student = $event->student;
        ActivityLog::audit(
            'creche.journal.updated',
            "Réveil de {$student->first_name} {$student->last_name} à {$time} ({$event->summary()})",
            $student,
            ['event_id' => $event->id, 'end_time' => $time]
        );

        return $event;
    }

    /** Suppression douce : l'événement reste en base, avec qui l'a supprimé (règle 7). */
    public function delete(CareEvent $event, User $user): void
    {
        $event->deleted_by = $user->id;
        $event->save();
        $event->delete();

        $student = $event->student;
        ActivityLog::audit(
            'creche.journal.deleted',
            "A supprimé : {$event->type_label} de {$student->first_name} {$student->last_name} à {$event->startLabel()}",
            $student,
            ['event_id' => $event->id, 'before' => $this->snapshot($event)]
        );
    }

    /** Champs saisis → colonnes et « data » de l'événement. */
    private function attributesFor(string $type, array $input): array
    {
        $attributes = [
            'start_time' => $input['start_time'],
            'notes' => $input['notes'] ?? null,
        ];

        $clean = fn (array $data) => array_filter($data, fn ($value) => $value !== null && $value !== '');

        return $attributes + match ($type) {
            'bottle' => [
                'quantity' => $input['quantity'],
                'unit' => 'ml',
                'data' => $clean(['consumed' => $input['consumed'] ?? null, 'milk' => $input['milk'] ?? null, 'preparation' => $input['preparation'] ?? null]),
            ],
            'meal', 'snack' => [
                'data' => $clean(['menu' => $input['menu'] ?? null, 'appreciation' => $input['appreciation']]),
            ],
            'sleep' => [
                'end_time' => $input['end_time'] ?? null,
                'data' => $clean(['quality' => $input['quality'] ?? null]),
            ],
            'diaper' => [
                'data' => $clean(['kind' => $input['kind'], 'product' => $input['product'] ?? null]),
            ],
            'hygiene' => [
                'data' => ['kind' => $input['kind']],
            ],
            'activity' => [
                'quantity' => $input['duration'] ?? null,
                'unit' => isset($input['duration']) ? 'min' : null,
                'data' => $clean(['category' => $input['category'], 'title' => $input['title'] ?? null]),
            ],
            default => ['data' => null],
        };
    }

    private function snapshot(CareEvent $event): array
    {
        return [
            'start' => $event->startLabel(),
            'end' => $event->endLabel(),
            'summary' => $event->summary(),
            'notes' => $event->notes,
        ];
    }
}
