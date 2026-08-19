<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Message extends Model
{
    protected $fillable = [
        'school_id',
        'sender_id',
        'receiver_id',
        'target_info',
        'target_class_id',
        'subject',
        'message',
        'reply',
        'is_read',
        'replied_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'replied_at' => 'datetime',
    ];

    // Relation avec l'école
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    // Relation avec l'expéditeur (Admin ou Parent)
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // ✅ Relation avec le destinataire (Parent)
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // Classe ciblée par une diffusion (si target_type = class)
    public function targetClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'target_class_id');
    }

    /**
     * Messages reçus par l'école de la part des parents (hors diffusions envoyées par l'école elle-même).
     */
    public function scopeReceivedFromParents(Builder $query): Builder
    {
        return $query->whereNull('receiver_id')
            ->whereHas('sender', fn ($q) => $q->where('role', 'parent'));
    }

    /**
     * Messages visibles par un parent donné : ceux qu'il a envoyés, ceux qui lui sont adressés
     * personnellement, et les diffusions de son école ciblant "tous les parents" ou la classe
     * d'un de ses enfants.
     */
    public function scopeVisibleToParent(Builder $query, User $parent): Builder
    {
        $parentId = $parent->id;
        $schoolIds = $parent->children()->pluck('students.school_id')->unique();
        $childClassIds = $schoolIds->isEmpty()
            ? collect()
            : DB::table('student_school_class')
                ->whereIn('student_id', $parent->children()->pluck('students.id'))
                ->pluck('school_class_id')
                ->unique();

        return $query->where(function (Builder $q) use ($parentId, $schoolIds, $childClassIds) {
            $q->where('sender_id', $parentId)
              ->orWhere('receiver_id', $parentId)
              ->orWhere(function (Builder $broadcast) use ($parentId, $schoolIds, $childClassIds) {
                  $broadcast->whereNull('receiver_id')
                      ->where('sender_id', '!=', $parentId)
                      ->whereIn('school_id', $schoolIds)
                      ->where(function (Builder $target) use ($childClassIds) {
                          $target->whereNull('target_class_id')
                                 ->orWhereIn('target_class_id', $childClassIds);
                      });
              });
        });
    }

    /**
     * Messages non lus à afficher dans le badge de notification d'un parent.
     * Les diffusions (partagées entre plusieurs parents) ne sont pas comptées : le statut
     * "is_read" est une colonne unique par message et ne peut pas représenter une lecture
     * par destinataire, donc seuls les messages personnels entrent dans ce compteur.
     */
    public function scopeUnreadForParent(Builder $query, User $parent): Builder
    {
        return $query->where('receiver_id', $parent->id)->where('is_read', false);
    }
}