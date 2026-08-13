<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SchoolAdminWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $schoolName;
    public $email;
    public $password;
    public $loginUrl;

    public function __construct($schoolName, $email, $password)
    {
        $this->schoolName = $schoolName;
        $this->email = $email;
        $this->password = $password;
        $this->loginUrl = url('/login');
    }

    public function build()
    {
        return $this->subject('🎉 Bienvenue sur Simple-School - Vos identifiants de connexion')
                    ->view('emails.school-admin-welcome');
    }
}