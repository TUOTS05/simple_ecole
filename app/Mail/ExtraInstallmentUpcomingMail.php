<?php

namespace App\Mail;

use App\Models\ExtraInstallment;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExtraInstallmentUpcomingMail extends Mailable
{
    use Queueable, SerializesModels;

    public $student;

    public $installment;

    public function __construct(Student $student, ExtraInstallment $installment)
    {
        $this->student = $student;
        $this->installment = $installment;
    }

    public function build()
    {
        $studentName = $this->student->first_name.' '.$this->student->last_name;

        return $this->subject('📅 Rappel : échéance à venir pour '.$studentName)
            ->view('emails.extra-installment-upcoming');
    }
}
