<?php

namespace Tests\Unit;

use App\Support\DocumentUploadValidation;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DocumentUploadValidationTest extends TestCase
{
    public function test_allows_jpeg_when_php_reports_octet_stream(): void
    {
        $file = UploadedFile::fake()->create('WhatsApp Image 2026-07-17.jpeg', 100, 'application/octet-stream');

        $this->assertTrue(DocumentUploadValidation::isAllowed($file));
    }

    public function test_allows_png_with_standard_mime(): void
    {
        $file = UploadedFile::fake()->image('scan.png');

        $this->assertTrue(DocumentUploadValidation::isAllowed($file));
    }

    public function test_allows_heic_extension(): void
    {
        $file = UploadedFile::fake()->create('photo.heic', 100, 'application/octet-stream');

        $this->assertTrue(DocumentUploadValidation::isAllowed($file));
    }

    public function test_rejects_unknown_extension(): void
    {
        $file = UploadedFile::fake()->create('archive.zip', 100, 'application/zip');

        $this->assertFalse(DocumentUploadValidation::isAllowed($file));
    }
}
