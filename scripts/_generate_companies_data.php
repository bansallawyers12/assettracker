<?php

/**
 * One-off: reads public/Companydetails.xlsx and overwrites scripts/companies_data.php
 * Run: php scripts/_generate_companies_data.php
 */

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

$path = $argv[1] ?? dirname(__DIR__).'/public/Companydetails.xlsx';
if (! is_readable($path)) {
    fwrite(STDERR, "Usage: php scripts/_generate_companies_data.php [path/to/Companydetails.xlsx]\nMissing: {$path}\n");
    exit(1);
}

$spreadsheet = IOFactory::load($path);
$sheet = $spreadsheet->getActiveSheet();
$highestRow = (int) $sheet->getHighestDataRow('D');
$rows = [];

for ($row = 3; $row <= $highestRow; $row++) {
    $legal = trim((string) $sheet->getCell('D'.$row)->getValue());
    if ($legal === '') {
        continue;
    }

    $abn = $sheet->getCell('B'.$row)->getValue();
    $acn = $sheet->getCell('C'.$row)->getValue();

    $abnStr = null;
    if ($abn !== null && $abn !== '') {
        $abnStr = preg_replace('/\D/', '', is_numeric($abn) ? (string) (int) $abn : (string) $abn);
        $abnStr = $abnStr !== '' ? $abnStr : null;
    }

    $acnStr = null;
    if ($acn !== null && $acn !== '') {
        $acnStr = preg_replace('/\D/', '', is_numeric($acn) ? (string) (int) $acn : (string) $acn);
        $acnStr = $acnStr !== '' ? $acnStr : null;
    }

    $trust = trim((string) $sheet->getCell('E'.$row)->getValue());
    $class = trim((string) $sheet->getCell('F'.$row)->getValue());
    $dir = trim((string) $sheet->getCell('G'.$row)->getValue());
    $addr = trim((string) $sheet->getCell('H'.$row)->getValue());

    $asicVal = $sheet->getCell('I'.$row)->getValue();
    $asic = null;
    if ($asicVal !== null && $asicVal !== '') {
        if (is_numeric($asicVal)) {
            try {
                $asic = Carbon::instance(ExcelDate::excelToDateTimeObject((float) $asicVal))->format('Y-m-d');
            } catch (Throwable) {
            }
        } else {
            try {
                $asic = Carbon::parse((string) $asicVal)->format('Y-m-d');
            } catch (Throwable) {
            }
        }
    }

    $rows[] = [
        'legal_name' => $legal,
        'abn' => $abnStr,
        'acn' => $acnStr,
        'under_trust_of' => $trust !== '' ? $trust : null,
        'classification' => $class !== '' ? $class : null,
        'director_name' => $dir !== '' ? $dir : null,
        'address' => $addr !== '' ? $addr : null,
        'asic_renewal' => $asic,
    ];
}

$out = dirname(__DIR__).'/scripts/companies_data.php';
$php = "<?php\n\n";
$php .= "/**\n * Auto-generated from public/Companydetails.xlsx — edit in Excel and re-run:\n *   php scripts/_generate_companies_data.php\n */\n\n";
$php .= 'return '.var_export($rows, true).";\n";

file_put_contents($out, $php);
echo 'Wrote '.count($rows)." rows to scripts/companies_data.php\n";
