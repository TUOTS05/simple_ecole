<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'app_name',
        'app_logo',
        'app_favicon',
        'support_email',
        'support_phone',
        'currency',
        'timezone',
        'date_format',
        'is_maintenance_mode',
        // Ajoutez ici vos autres colonnes si nécessaire
    ];

    protected $casts = [
        'is_maintenance_mode' => 'boolean',
    ];

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