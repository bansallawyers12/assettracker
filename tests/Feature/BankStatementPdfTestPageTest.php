<?php

use App\Models\User;
use App\Services\BankStatementPdfParseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class);

it('registers the local bank statement pdf test routes', function () {
    if (! app()->environment('local')) {
        expect(Route::has('dev.bank-statement-pdf-test.show'))->toBeFalse();

        return;
    }

    expect(Route::has('dev.bank-statement-pdf-test.show'))->toBeTrue()
        ->and(Route::has('dev.bank-statement-pdf-test.parse'))->toBeTrue()
        ->and(route('dev.bank-statement-pdf-test.show'))->toContain('/dev/bank-statement-pdf-test');
});

it('includes bank hint and pdf upload on the dev test page', function () {
    $html = file_get_contents(resource_path('views/dev/bank-statement-pdf-test.blade.php'));

    expect($html)->toContain('name="statement_pdf"')
        ->and($html)->toContain('name="bank_name"')
        ->and($html)->toContain('Parse PDF')
        ->and($html)->toContain('$parsed');
});

it('returns an error when the pdf file path does not exist', function () {
    $service = new BankStatementPdfParseService;
    $result = $service->parse(sys_get_temp_dir().DIRECTORY_SEPARATOR.'missing-bank-statement-'.uniqid().'.pdf', 'auto');

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('PDF file not found')
        ->and($result['entries'])->toBe([]);
});

it('returns structured parser errors from python stdout on failure', function () {
    $service = new BankStatementPdfParseService;
    $temp = tempnam(sys_get_temp_dir(), 'pdf-test-');
    $pdfPath = $temp.'.pdf';
    rename($temp, $pdfPath);
    file_put_contents($pdfPath, 'not-a-real-pdf');

    try {
        $result = $service->parse($pdfPath, 'auto');

        expect($result)->toHaveKey('success')
            ->and($result['success'])->toBeFalse()
            ->and($result)->toHaveKey('entries')
            ->and($result['entries'])->toBeArray()
            ->and($result['error'] ?? null)->not->toBeEmpty();
    } finally {
        if (is_file($pdfPath)) {
            unlink($pdfPath);
        }
    }
});

it('rejects non-pdf uploads on the parse route when local', function () {
    if (! app()->environment('local') || ! Route::has('dev.bank-statement-pdf-test.parse')) {
        expect(true)->toBeTrue();

        return;
    }

    $user = User::factory()->make([
        'id' => 1,
    ]);

    $this->actingAs($user)
        ->withoutMiddleware()
        ->post(route('dev.bank-statement-pdf-test.parse'), [
            'bank_name' => 'auto',
            'statement_pdf' => UploadedFile::fake()->create('statement.txt', 10, 'text/plain'),
        ])
        ->assertSessionHasErrors('statement_pdf');
});
