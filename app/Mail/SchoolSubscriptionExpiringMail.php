<?php

namespace App\Mail;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SchoolSubscriptionExpiringMail extends Mailable
{
    use Queueable, SerializesModels;

    public $school;

    /**
     * Créer une nouvelle instance de message.
     */
    public function __construct(School $school)
    {
        $this->school = $school;
    }

    /**
     * Construire le message.
     */
    public function build()
    {
        return $this->subject('⚠️ Rappel : Votre abonnement expire dans 30 jours')
                    ->view('emails.school-subscription-expiring');
    }
}