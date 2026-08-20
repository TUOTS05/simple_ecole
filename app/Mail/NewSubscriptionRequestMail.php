<?php

namespace App\Mail;

use App\Models\SubscriptionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewSubscriptionRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public SubscriptionRequest $subRequest;

    public function __construct(SubscriptionRequest $subRequest)
    {
        $this->subRequest = $subRequest;
    }

    public function build()
    {
        return $this->subject('🔔 Nouvelle demande d\'essai : ' . $this->subRequest->school->name)
            ->view('emails.superadmin.new-subscription-request');
    }
}
