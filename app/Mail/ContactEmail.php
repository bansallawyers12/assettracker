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
     * @param  array<int, mixed>  $attachmentsData
     */
    public function __construct($subject, $messageBody, $attachmentsData = [], $fromEmail = null)
    {
        $this->subject = $subject;
        $this->messageBody = $messageBody;
        $this->attachmentsData = $attachmentsData ?? [];
        $this->fromEmail = $fromEmail;
    }

    /**
     * Gmail SMTP accepts only the authenticated account as From.
     * A different composer address is sent as Reply-To.
     */
    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            subject: $this->subject,
        );

        $configuredFrom = strtolower(trim((string) config('mail.from.address')));
        $replyTo = strtolower(trim((string) $this->fromEmail));

        if ($replyTo !== '' && $replyTo !== $configuredFrom) {
            $envelope->replyTo($this->fromEmail);
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
