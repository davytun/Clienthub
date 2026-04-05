<?php

namespace App\Mail;

use App\Models\Message;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewMessageMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Message $message,
        public readonly Project $project,
        public readonly bool    $recipientIsClient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New message on project: {$this->project->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.new-message',
        );
    }
}
