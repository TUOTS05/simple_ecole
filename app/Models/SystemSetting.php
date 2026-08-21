<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SystemSetting extends Model
{
    protected $fillable = [
        'platform_name',
        'support_email',
        'support_phone',
        'support_address',
        'logo',
        'favicon',
        'terms_of_service',
        'privacy_policy',
        'primary_color',
        'secondary_color',
        'maintenance_mode',
        'maintenance_message',
    ];

    protected $casts = [
        'maintenance_mode' => 'boolean',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->favicon ? Storage::disk('public')->url($this->favicon) : null;
    }

    /**
     * Récupère les paramètres système (avec cache sécurisé)
     */
    public static function getSettings(): self
    {
        // On cache les attributs sous forme de tableau pour éviter les problèmes de sérialisation d'objet
        $attributes = Cache::rememberForever('system_settings_data', function () {
            return self::first()?->toArray() ?? [];
        });

        // On hydrate une nouvelle instance avec les données mises en cache
        $instance = new self();
        $instance->setRawAttributes($attributes);
        
        if (!empty($attributes)) {
            $instance->exists = true; // Indique que l'enregistrement existe en BDD
        }

        return $instance;
    }

    /**
     * Invalide le cache après toute modification ou suppression des paramètres
     */
    protected static function booted()
    {
        static::saved(function () {
            Cache::forget('system_settings_data');
        });
        
        static::deleted(function () {
            Cache::forget('system_settings_data');
        });
    }
}