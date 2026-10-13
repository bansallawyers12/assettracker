<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Support\StoredMailFile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
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
        $fromAddress = trim((string) config('mail.from.address'));
        $fromName = trim((string) ($this->invoice->businessEntity?->legal_name ?: config('mail.from.name')));

        $envelope = new Envelope(
            from: $fromAddress !== '' ? new Address($fromAddress, $fromName !== '' ? $fromName : null) : null,
            subject: $this->subjectLine ?: ('Invoice '.$this->invoice->invoice_number),
        );

        $configuredFrom = strtolower($fromAddress);
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
            text: 'emails.invoice-text',
            with: [
                'paragraphs' => $this->messageParagraphs(),
            ],
        );
    }

    /**
     * @return list<string>
     */
    public function messageParagraphs(): array
    {
        $chunks = preg_split("/\r\n\r\n|\n\n|\r\r/", trim($this->messageBody));

        if ($chunks === false) {
            return [];
        }

        return array_values(array_filter(
            $chunks,
            fn (string $paragraph): bool => trim($paragraph) !== '',
        ));
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
