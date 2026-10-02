<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;

    public $messageBody;

    public $attachmentsData;

    public $fromEmail;

    /**
     * Create a new message instance.
     */
    public function __construct($subject, $messageBody, $attachmentsData = [], $fromEmail = null)
    {
        $this->subject = $subject;
        $this->messageBody = $messageBody;
        $this->attachmentsData = $attachmentsData ?? [];
        $this->fromEmail = $fromEmail;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            subject: $this->subject,
        );

        // Set the from address if provided
        if ($this->fromEmail) {
            $envelope->from($this->fromEmail);
        }

        return $envelope;
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->messageBody,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        foreach ($this->attachmentsData as $attachment) {
            $attachments[] = Attachment::fromPath($attachment->getRealPath())
                ->as($attachment->getClientOriginalName())
                ->withMime($attachment->getMimeType());
        }

        return $attachments;
    }
}
