<?php

namespace App\Console\Commands;

use App\Models\ExtraVehicleLocation;
use Illuminate\Console\Command;

/**
 * Supprime les positions GPS de plus de 7 jours, pour limiter la taille de la base.
 */
class PruneVehicleLocations extends Command
{
    protected $signature = 'extras:prune-vehicle-locations';

    protected $description = "Supprime l'historique de position GPS des véhicules de plus de 7 jours.";

    /**
     * Supprime les anciennes positions et affiche leur nombre.
     */
    public function handle()
    {
        $deleted = ExtraVehicleLocation::where('recorded_at', '<', now()->subDays(7))->delete();

        $this->info("🧹 {$deleted} position(s) GPS de plus de 7 jours supprimée(s).");
    }
}
