<?php

namespace App\Mail;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail de bienvenue à une école, avec le mot de passe de son compte administrateur. (Actuellement envoyé nulle part.)
 */
class SchoolWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $school;

    public $password;

    /**
     * Prépare un nouvel e-mail.
     */
    public function __construct(School $school, string $password)
    {
        $this->school = $school;
        $this->password = $password;
    }

    /**
     * Enveloppe de l'e-mail (objet, expéditeur).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenue sur '.config('app.name').' - Activez votre compte',
        );
    }

    /**
     * Contenu de l'e-mail (vue et données).
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.school-welcome',
        );
    }

    /**
     * Pièces jointes de l'e-mail.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
