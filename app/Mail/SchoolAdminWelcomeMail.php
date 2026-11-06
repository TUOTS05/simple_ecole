<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail de bienvenue à l'administrateur d'une école, avec ses identifiants.
 */
class SchoolAdminWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $schoolName;

    public $email;

    public $password;

    public $loginUrl;

    /**
     * Nom de l'école et identifiants de connexion de son administrateur.
     */
    public function __construct($schoolName, $email, $password)
    {
        $this->schoolName = $schoolName;
        $this->email = $email;
        $this->password = $password;
        $this->loginUrl = url('/login');
    }

    /**
     * Objet et vue de l'e-mail.
     */
    public function build()
    {
        return $this->subject('🎉 Bienvenue sur Simple Ecole - Vos identifiants de connexion')
            ->view('emails.school-admin-welcome');
    }
}
