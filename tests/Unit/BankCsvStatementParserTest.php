<?php

use App\Services\BankCsvStatementParser;

function writeTempCsv(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'bankcsv_').'.csv';
    file_put_contents($path, $contents);

    return $path;
}

it('auto-detects date description and amount when columns are out of order', function () {
    $path = writeTempCsv(<<<'CSV'
Particulars,Value,Txn Date
Rental income,2500.00,01/07/2026
Council rates,-890.50,02/07/2026
CSV);

    $parser = new BankCsvStatementParser;
    $inspect = $parser->inspectFile($path);

    expect($inspect['success'])->toBeTrue()
        ->and($inspect['suggested_mapping']['date'])->toBe('Txn Date')
        ->and($inspect['suggested_mapping']['description'])->toBe('Particulars')
        ->and($inspect['suggested_mapping']['amount'])->toBe('Value');

    $parsed = $parser->parseFile($path, 'Test Bank', $inspect['suggested_mapping']);

    expect($parsed['success'])->toBeTrue()
        ->and($parsed['entries'])->toHaveCount(2)
        ->and($parsed['entries'][0]['date'])->toBe('2026-07-01')
        ->and($parsed['entries'][0]['description'])->toBe('Rental income')
        ->and((float) $parsed['entries'][0]['amount'])->toBe(2500.0);

    @unlink($path);
});

it('requires date description and an amount source in mapping validation', function () {
    $parser = new BankCsvStatementParser;
    $headers = ['Col A', 'Col B', 'Col C'];

    expect($parser->validateMapping([
        'date' => null,
        'description' => 'Col B',
        'amount' => 'Col C',
    ], $headers))->toContain('Date')
        ->and($parser->validateMapping([
            'date' => 'Col A',
            'description' => null,
            'amount' => 'Col C',
        ], $headers))->toContain('Description')
        ->and($parser->validateMapping([
            'date' => 'Col A',
            'description' => 'Col B',
            'amount' => null,
            'debit' => null,
            'credit' => null,
        ], $headers))->toContain('Amount')
        ->and($parser->validateMapping([
            'date' => 'Col A',
            'description' => 'Col B',
            'amount' => 'Col C',
        ], $headers))->toBeNull();
});

it('parses with an explicit mapping when headers are generic', function () {
    $path = writeTempCsv(<<<'CSV'
Column1,Column2,Column3
Office supplies,03/07/2026,-65.40
Interest earned,04/07/2026,22.15
CSV);

    $parser = new BankCsvStatementParser;
    $mapping = [
        'date' => 'Column2',
        'description' => 'Column1',
        'amount' => 'Column3',
        'debit' => null,
        'credit' => null,
        'reference' => null,
        'balance' => null,
    ];

    $parsed = $parser->parseFile($path, '', $mapping);

    expect($parsed['success'])->toBeTrue()
        ->and($parsed['entries'])->toHaveCount(2)
        ->and($parsed['entries'][0]['description'])->toBe('Office supplies')
        ->and($parsed['entries'][0]['date'])->toBe('2026-07-03')
        ->and((float) $parsed['entries'][0]['amount'])->toBe(-65.4);

    @unlink($path);
});
