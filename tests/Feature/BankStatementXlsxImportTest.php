<?php

use App\Services\BankCsvStatementParser;
use App\Support\BankStatementXlsxReader;
use Tests\TestCase;

uses(TestCase::class);

function writeTempXlsx(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'bankxlsx_').'.xlsx';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $shared = [];
    $sharedIndex = [];
    $sheetRows = '';
    $rowNumber = 1;

    foreach ($rows as $row) {
        $cells = '';
        $col = 0;
        foreach ($row as $value) {
            $colLetter = '';
            $n = $col;
            do {
                $colLetter = chr(65 + ($n % 26)).$colLetter;
                $n = intdiv($n, 26) - 1;
            } while ($n >= 0);

            $ref = $colLetter.$rowNumber;
            $text = (string) $value;
            if (! array_key_exists($text, $sharedIndex)) {
                $sharedIndex[$text] = count($shared);
                $shared[] = $text;
            }
            $idx = $sharedIndex[$text];
            $cells .= '<c r="'.$ref.'" t="s"><v>'.$idx.'</v></c>';
            $col++;
        }
        $sheetRows .= '<row r="'.$rowNumber.'">'.$cells.'</row>';
        $rowNumber++;
    }

    $sharedXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($shared).'" uniqueCount="'.count($shared).'">';
    foreach ($shared as $text) {
        $sharedXml .= '<si><t>'.htmlspecialchars($text, ENT_XML1).'</t></si>';
    }
    $sharedXml .= '</sst>';

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        .'<sheetData>'.$sheetRows.'</sheetData></worksheet>';

    $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
        .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        .'<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>';

    $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
        .'</Relationships>';

    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        .'<Default Extension="xml" ContentType="application/xml"/>'
        .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        .'<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
        .'</Types>';

    $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        .'</Relationships>';

    $zip->addFromString('[Content_Types].xml', $contentTypes);
    $zip->addFromString('_rels/.rels', $rootRels);
    $zip->addFromString('xl/workbook.xml', $workbookXml);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $relsXml);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->addFromString('xl/sharedStrings.xml', $sharedXml);
    $zip->close();

    return $path;
}

it('reads the first worksheet of an xlsx bank statement', function () {
    $path = writeTempXlsx([
        ['Date', 'Description', 'Amount'],
        ['01/07/2026', 'Rental income', '2500.00'],
        ['02/07/2026', 'Council rates', '-890.50'],
    ]);

    $rows = (new BankStatementXlsxReader)->readRows($path);

    expect($rows)->toHaveCount(3)
        ->and($rows[0])->toBe(['Date', 'Description', 'Amount'])
        ->and($rows[1][1])->toBe('Rental income');

    @unlink($path);
});

it('parses xlsx bank statements through the csv parser facade', function () {
    $path = writeTempXlsx([
        ['Date', 'Description', 'Amount'],
        ['01/07/2026', 'Rental income', '2500.00'],
        ['02/07/2026', 'Council rates', '-890.50'],
    ]);

    $parser = new BankCsvStatementParser;
    $inspect = $parser->inspectFile($path);

    expect($inspect['success'])->toBeTrue()
        ->and($inspect['suggested_mapping']['date'])->toBe('Date')
        ->and($inspect['suggested_mapping']['description'])->toBe('Description')
        ->and($inspect['suggested_mapping']['amount'])->toBe('Amount');

    $parsed = $parser->parseFile($path, 'Test Bank', $inspect['suggested_mapping']);

    expect($parsed['success'])->toBeTrue()
        ->and($parsed['entries'])->toHaveCount(2)
        ->and($parsed['entries'][0]['date'])->toBe('2026-07-01')
        ->and((float) $parsed['entries'][0]['amount'])->toBe(2500.0);

    @unlink($path);
});

it('accepts xlsx in bank import upload validation markup', function () {
    $importController = file_get_contents(app_path('Http/Controllers/BankAccountImportController.php'));
    $panel = file_get_contents(resource_path('views/bank-accounts/partials/reconciliation-panel.blade.php'));
    $parser = file_get_contents(app_path('Services/BankCsvStatementParser.php'));

    expect($importController)->toContain("'statement_file' => 'required|file|mimes:csv,txt,xlsx|max:10240'")
        ->and($panel)->toContain('.xlsx')
        ->and($parser)->toContain("'xlsx'")
        ->and($parser)->toContain('BankStatementXlsxReader');
});
