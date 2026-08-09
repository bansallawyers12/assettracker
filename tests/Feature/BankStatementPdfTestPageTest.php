<?php

use App\Models\User;
use App\Services\BankStatementPdfParseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Process\Process;
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
        ->and($html)->toContain('$bankHints')
        ->and($html)->toContain('Parse PDF')
        ->and($html)->toContain('amount_debit')
        ->and($html)->toContain('amount_credit')
        ->and($html)->toContain('$parsed');

    foreach (array_keys(BankStatementPdfParseService::BANK_HINTS) as $hint) {
        expect(BankStatementPdfParseService::BANK_HINTS)->toHaveKey($hint);
    }

    expect(BankStatementPdfParseService::BANK_HINTS)->toHaveKeys(['auto', 'cba', 'nab', 'macquarie', 'westpac']);
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

it('normalizes every bank into fixed test-page columns', function () {
    $service = new BankStatementPdfParseService;

    $fromDebitCredit = $service->normalizeEntry([
        'date' => '2026-04-02',
        'description' => 'Deposit',
        'amount_debit' => null,
        'amount_credit' => 100,
        'balance' => 500,
    ]);

    $fromSignedOnly = $service->normalizeEntry([
        'date' => '2026-04-03',
        'description' => 'Fee',
        'amount' => -42.5,
        'balance' => 457.5,
    ]);

    foreach (BankStatementPdfParseService::FIXED_COLUMNS as $column) {
        expect($fromDebitCredit)->toHaveKey($column)
            ->and($fromSignedOnly)->toHaveKey($column);
    }

    expect($fromDebitCredit['amount_credit'])->toBe(100.0)
        ->and($fromDebitCredit['amount_debit'])->toBeNull()
        ->and($fromSignedOnly['amount_debit'])->toBe(42.5)
        ->and($fromSignedOnly['amount_credit'])->toBeNull()
        ->and($fromSignedOnly['transaction_type'])->toBe('debit');
});

it('parses westpac wrapped rows via python unit tests', function () {
    $python = PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
    $testFiles = [
        base_path('python/tests/test_westpac_pdf_parser.py'),
        base_path('python/tests/test_fixed_columns_all_banks.py'),
    ];

    foreach ($testFiles as $testFile) {
        if (! is_file($testFile)) {
            continue;
        }

        $process = new Process([$python, $testFile]);
        $process->run();

        expect($process->isSuccessful())->toBeTrue(
            "Python PDF parser tests failed ({$testFile}):\n".$process->getErrorOutput().$process->getOutput()
        );
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
