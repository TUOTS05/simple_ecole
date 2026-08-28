<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtraTarif extends Model
{
    protected $fillable = [
        'extra_id', 'school_year_id', 'school_class_id',
        'amount', 'periods_count', 'is_open_ended', 'start_period', 'end_period', 'due_day', 'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'periods_count' => 'integer',
        'is_open_ended' => 'boolean',
        'due_day' => 'integer',
    ];

    public function extra(): BelongsTo
    {
        return $this->belongsTo(Extra::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ExtraSubscription::class);
    }

    public function getTotalAmountAttribute()
    {
        return $this->periods_count ? $this->amount * $this->periods_count : $this->amount;
    }

    /**
     * Liste des périodes (Y-m) par défaut d'un tarif récurrent : celles comprises
     * entre start_period/end_period si définies, sinon periods_count mois à partir
     * du mois courant. Utilisé quand aucune sélection manuelle de périodes n'est
     * fournie (demande parent, promotion d'une inscription en liste d'attente).
     */
    public function defaultPeriods(): array
    {
        if ($this->is_open_ended) {
            return [now()->format('Y-m')];
        }

        if ($this->start_period && $this->end_period) {
            $periods = [];
            $cursor = Carbon::parse($this->start_period.'-01');
            $end = Carbon::parse($this->end_period.'-01');
            while ($cursor->lte($end)) {
                $periods[] = $cursor->format('Y-m');
                $cursor->addMonth();
            }

            return $periods;
        }

        $count = $this->periods_count ?? 1;
        $periods = [];
        $cursor = now()->startOfMonth();
        for ($i = 0; $i < $count; $i++) {
            $periods[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        return $periods;
    }

    /**
     * Crée les échéances (ExtraInstallment) d'un abonnement pour ses périodes par
     * défaut. Utilisé à la demande parent et à la promotion depuis la liste
     * d'attente, où aucune sélection manuelle de périodes n'est faite.
     */
    public function createDefaultInstallmentsFor(ExtraSubscription $subscription): void
    {
        $periods = $subscription->extra->isRecurring() ? $this->defaultPeriods() : ['unique'];

        foreach ($periods as $period) {
            $dueDate = $period === 'unique'
                ? now()
                : Carbon::parse($period.'-01')->day(min($this->due_day, Carbon::parse($period.'-01')->daysInMonth));

            ExtraInstallment::create([
                'extra_subscription_id' => $subscription->id,
                'period' => $period,
                'amount' => $this->amount,
                'paid_amount' => 0,
                'due_date' => $dueDate,
                'status' => 'pending',
            ]);
        }
    }
}
