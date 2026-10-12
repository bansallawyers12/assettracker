<?php

namespace App\Mail;

use App\Support\StoredMailFile;
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
     * Files already stored for the company.
     *
     * @var list<StoredMailFile>
     */
    public $storedFiles;

    /**
     * Attachments kept from the message being forwarded.
     *
     * @var list<StoredMailFile>
     */
    public $forwardedFiles;

    /**
     * @param  array<int, mixed>  $attachmentsData
     * @param  list<StoredMailFile>  $storedFiles
     * @param  list<StoredMailFile>  $forwardedFiles
     */
    public function __construct($subject, $messageBody, $attachmentsData = [], $fromEmail = null, array $storedFiles = [], array $forwardedFiles = [])
    {
        $this->subject = $subject;
        $this->messageBody = $messageBody;
        $this->attachmentsData = $attachmentsData ?? [];
        $this->fromEmail = $fromEmail;
        $this->storedFiles = $storedFiles;
        $this->forwardedFiles = $forwardedFiles;
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

        foreach ($this->storedFiles as $file) {
            $attachments[] = $file->toAttachment();
        }

        foreach ($this->forwardedFiles as $file) {
            $attachments[] = $file->toAttachment();
        }

        return $attachments;
    }
}
