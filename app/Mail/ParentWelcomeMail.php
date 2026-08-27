<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ParentWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $parentName;

    public $studentName;

    public $schoolName;

    public $email;

    public $password;

    public $loginUrl;

    public function __construct($parentName, $studentName, $schoolName, $email, $password)
    {
        $this->parentName = $parentName;
        $this->studentName = $studentName;
        $this->schoolName = $schoolName;
        $this->email = $email;
        $this->password = $password;
        $this->loginUrl = url('/login');
    }

    public function build()
    {
        return $this->subject('🎓 Bienvenue sur Simple Ecole - Votre espace parent')
            ->view('emails.parent-welcome');
    }
}
