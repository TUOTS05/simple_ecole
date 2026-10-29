<?php

namespace App\Mail;

use App\Models\ExtraInstallment;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail au parent : une échéance d'extra est en retard.
 */
class ExtraInstallmentLateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $student;

    public $installment;

    /**
     * Élève et échéance concernés.
     */
    public function __construct(Student $student, ExtraInstallment $installment)
    {
        $this->student = $student;
        $this->installment = $installment;
    }

    /**
     * Objet (avec le nom de l'élève) et vue de l'e-mail.
     */
    public function build()
    {
        $studentName = $this->student->first_name.' '.$this->student->last_name;

        return $this->subject('⚠️ Impayé : échéance extra en retard pour '.$studentName)
            ->view('emails.extra-installment-late');
    }
}
