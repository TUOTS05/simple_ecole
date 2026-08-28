<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// contracts:notify-expiration est redondant avec notify:schools-expiring (qui couvre désormais
// aussi bien les abonnements payants que l'essai gratuit, à partir des mêmes dates que School lit
// pour le contrôle d'accès) : le garder planifié doublerait l'email envoyé à l'école.
Schedule::command('notify:schools-expiring')->dailyAt('08:00');
// Planification des rappels de paiement en retard (tous les jours à 9h00)
Schedule::command('notify:parents-late')->dailyAt('09:00');

Schedule::command('notifications:late-payments')
    ->dailyAt('09:00')
    ->withoutOverlapping();

// Extras : rappel avant échéance (J-3) et alerte de retard, une fois par jour.
Schedule::command('notify:extras-upcoming')->dailyAt('08:30');
Schedule::command('notify:extras-late')->dailyAt('09:30');

// Extras : génère l'échéance du mois pour les tarifs à facturation continue
// (cantine, garderie, transport...) le 1er de chaque mois.
Schedule::command('extras:generate-monthly-installments')->monthlyOn(1, '06:00');
