<?php

namespace App\Mail;

use App\Models\ExtraPayment;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail au parent : confirmation d'un paiement d'extra.
 */
class ExtraPaymentConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $student;

    public $payment;

    /**
     * Élève et paiement concernés.
     */
    public function __construct(Student $student, ExtraPayment $payment)
    {
        $this->student = $student;
        $this->payment = $payment;
    }

    /**
     * Objet (avec le nom de l'élève) et vue de l'e-mail.
     */
    public function build()
    {
        $studentName = $this->student->first_name.' '.$this->student->last_name;

        return $this->subject('✅ Paiement enregistré pour '.$studentName)
            ->view('emails.extra-payment-confirmed');
    }
}
