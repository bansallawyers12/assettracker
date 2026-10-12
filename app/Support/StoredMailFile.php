<?php

namespace App\Support;

use Illuminate\Mail\Mailables\Attachment;

class StoredMailFile
{
    public function __construct(
        public string $name,
        public string $mime,
        public string $contents,
    ) {}

    public function toAttachment(): Attachment
    {
        $contents = $this->contents;

        return Attachment::fromData(fn () => $contents, $this->name)
            ->withMime($this->mime !== '' ? $this->mime : 'application/octet-stream');
    }
}
