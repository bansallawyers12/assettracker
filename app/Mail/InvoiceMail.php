<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Support\StoredMailFile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<StoredMailFile>  $storedFiles
     */
    public function __construct(
        public Invoice $invoice,
        public string $messageBody,
        public string $pdfFilename,
        public string $pdfContents,
        public array $storedFiles = [],
        public ?string $replyToAddress = null,
        public ?string $subjectLine = null,
    ) {}

    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            subject: $this->subjectLine ?: ('Invoice '.$this->invoice->invoice_number),
        );

        $configuredFrom = strtolower(trim((string) config('mail.from.address')));
        $replyTo = strtolower(trim((string) $this->replyToAddress));

        if ($replyTo !== '' && $replyTo !== $configuredFrom) {
            $envelope->replyTo($this->replyToAddress);
        }

        return $envelope;
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $pdf = $this->pdfContents;

        $attachments = [
            Attachment::fromData(fn () => $pdf, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];

        foreach ($this->storedFiles as $file) {
            $attachments[] = $file->toAttachment();
        }

        return $attachments;
    }
}
