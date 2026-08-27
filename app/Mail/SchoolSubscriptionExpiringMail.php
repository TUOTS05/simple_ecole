<?php

namespace App\Mail;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SchoolSubscriptionExpiringMail extends Mailable
{
    use Queueable, SerializesModels;

    public $school;

    public $expiresAt;

    public $isTrial;

    /**
     * Créer une nouvelle instance de message.
     *
     * @param  Carbon|string  $expiresAt  Date d'expiration à afficher (essai ou abonnement payant).
     * @param  bool  $isTrial  true si c'est l'essai gratuit qui expire, false si c'est un abonnement payant.
     */
    public function __construct(School $school, $expiresAt, bool $isTrial = false)
    {
        $this->school = $school;
        $this->expiresAt = $expiresAt;
        $this->isTrial = $isTrial;
    }

    /**
     * Construire le message.
     */
    public function build()
    {
        $subject = $this->isTrial
            ? '⚠️ Rappel : Votre essai gratuit expire dans 30 jours'
            : '⚠️ Rappel : Votre abonnement expire dans 30 jours';

        return $this->subject($subject)
            ->view('emails.school-subscription-expiring');
    }
}
