<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail au parent déjà inscrit : un nouvel enfant a été ajouté à son espace parent.
 */
class ChildLinkedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $loginUrl;

    /**
     * Noms du parent, de l'élève et de l'école.
     */
    public function __construct(public string $parentName, public string $studentName, public string $schoolName)
    {
        $this->loginUrl = url('/login');
    }

    /**
     * Objet et vue de l'e-mail.
     */
    public function build()
    {
        return $this->subject("🎓 {$this->studentName} a été ajouté(e) à votre espace parent")
            ->view('emails.child-linked');
    }
}
