<?php

namespace App\Mail;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SchoolWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $school;

    public $password;

    /**
     * Create a new message instance.
     */
    public function __construct(School $school, string $password)
    {
        $this->school = $school;
        $this->password = $password;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenue sur '.config('app.name').' - Activez votre compte',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.school-welcome',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
