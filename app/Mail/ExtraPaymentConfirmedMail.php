<?php

namespace App\Mail;

use App\Models\ExtraPayment;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExtraPaymentConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $student;

    public $payment;

    public function __construct(Student $student, ExtraPayment $payment)
    {
        $this->student = $student;
        $this->payment = $payment;
    }

    public function build()
    {
        $studentName = $this->student->first_name.' '.$this->student->last_name;

        return $this->subject('✅ Paiement enregistré pour '.$studentName)
            ->view('emails.extra-payment-confirmed');
    }
}
