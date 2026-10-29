<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Fonction administrative propre à une école (Directeur des études, Censeur, Économe…) et
 * modules auxquels elle donne accès. Voir config/school_permissions.php.
 */
class StaffFunction extends Model
{
    protected $fillable = [
        'name', 'description', 'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Modules accordés, limités à ceux qui existent encore dans la configuration.
     */
    public function modules(): array
    {
        return array_values(array_intersect($this->permissions ?? [], array_keys(config('school_permissions.modules'))));
    }

    public function grants(string $module): bool
    {
        return in_array($module, $this->modules(), true);
    }
}
