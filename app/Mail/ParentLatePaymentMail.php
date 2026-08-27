<?php

namespace App\Mail;

use App\Models\Student;
use App\Models\StudentInstallment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ParentLatePaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $student;

    public $installment;

    /**
     * Créer une nouvelle instance de message.
     */
    public function __construct(Student $student, StudentInstallment $installment)
    {
        $this->student = $student;
        $this->installment = $installment;
    }

    /**
     * Construire le message.
     */
    public function build()
    {
        $studentName = $this->student->first_name.' '.$this->student->last_name;

        return $this->subject('⚠️ Rappel : Échéance en retard pour '.$studentName)
            ->view('emails.parent-late-payment');
    }
}
