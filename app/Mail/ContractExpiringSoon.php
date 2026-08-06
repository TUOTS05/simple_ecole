<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractExpiringSoon extends Mailable
{
    use Queueable, SerializesModels;

    public $contract;

    public function __construct(Contract $contract)
    {
        $this->contract = $contract;
    }

    public function build()
    {
        return $this->subject('⚠️ Rappel : Votre abonnement expire dans 30 jours')
                    ->view('emails.contract-expiring');
    }
}