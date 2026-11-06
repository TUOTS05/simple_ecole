<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail prévenant une école que son contrat expire dans 30 jours.
 */
class ContractExpiringSoon extends Mailable
{
    use Queueable, SerializesModels;

    public $contract;

    public function __construct(Contract $contract)
    {
        $this->contract = $contract;
    }

    /**
     * Objet et vue de l'e-mail.
     */
    public function build()
    {
        return $this->subject('⚠️ Rappel : Votre abonnement expire dans 30 jours')
            ->view('emails.contract-expiring');
    }
}
