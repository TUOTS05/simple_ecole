<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Présence d'un enfant de la crèche pour une journée (une ligne par enfant et par jour).
 *
 * Cycle : (aucune ligne) → present [arrivée] → left [départ vérifié]. Une absence s'enregistre
 * à la place d'une arrivée (absent / absent_justified). Règles : une seule présence active à
 * la fois, pas de départ sans arrivée, pas de second départ sans nouvelle arrivée.
 */
class CrecheAttendance extends Model
{
    public const STATUSES = [
        'present' => 'Présent',
        'absent' => 'Absent',
        'absent_justified' => 'Absence justifiée',
        'left' => 'Parti',
    ];

    /** Façon dont la personne qui récupère l'enfant a été vérifiée. */
    public const VERIFICATION_MODES = [
        'list' => 'Choisie dans la liste des personnes autorisées',
        'identity' => 'Liste + identité vérifiée (photo ou pièce)',
        'correction' => 'Correction par la responsable',
    ];

    protected $fillable = [
        'school_id',
        'student_id',
        'school_class_id',
        'date',
        'status',
        'is_late',
        'absence_reason',
        'checked_in_at',
        'checked_in_by',
        'checked_out_at',
        'checked_out_by',
        'dropped_off_by',
        'picked_up_by',
        'pickup_person_id',
        'verification_mode',
        'late_pickup',
        'notes',
        'marked_by',
    ];

    protected $casts = [
        'date' => 'date',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'is_late' => 'boolean',
        'late_pickup' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    public function pickupPerson(): BelongsTo
    {
        return $this->belongsTo(AuthorizedPickup::class, 'pickup_person_id')->withTrashed();
    }

    /** L'enfant est actuellement dans les locaux (arrivé, pas encore reparti). */
    public function isOnSite(): bool
    {
        return $this->status === 'present';
    }

    public function isAbsent(): bool
    {
        return in_array($this->status, ['absent', 'absent_justified'], true);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }
}
