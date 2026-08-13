<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('contracts:notify-expiration')->dailyAt('08:00');
Schedule::command('notify:schools-expiring')->dailyAt('08:00');
// Planification des rappels de paiement en retard (tous les jours à 9h00)
Schedule::command('notify:parents-late')->dailyAt('09:00');


Schedule::command('notifications:late-payments')
    ->dailyAt('09:00')
    ->withoutOverlapping();
