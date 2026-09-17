<?php

namespace App\Support;

use ZipArchive;

/**
 * Minimal first-sheet XLSX reader for bank statement imports (no PhpSpreadsheet).
 * Supports shared strings, inline strings, and numeric cells.
 */
class BankStatementXlsxReader
{
    /**
     * @return list<list<string>>
     */
    public function readRows(string $filePath): array
    {
        if (! is_file($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException('Could not open Excel workbook. Save as .xlsx or export CSV.');
        }

        try {
            $sharedStrings = $this->parseSharedStrings($zip->getFromName('xl/sharedStrings.xml') ?: '');
            $sheetPath = $this->firstSheetPath($zip);
            $sheetXml = $zip->getFromName($sheetPath);
            if ($sheetXml === false || $sheetXml === '') {
                throw new \RuntimeException('Excel workbook has no readable worksheet.');
            }

            return $this->parseSheetRows($sheetXml, $sharedStrings);
        } finally {
            $zip->close();
        }
    }

    /**
     * @return list<string>
     */
    private function parseSharedStrings(string $xml): array
    {
        if ($xml === '') {
            return [];
        }

        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($document === false) {
            return [];
        }

        $document->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $strings = [];
        foreach ($document->xpath('//m:si') ?: [] as $si) {
            $si->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $parts = $si->xpath('.//m:t') ?: [];
            $text = '';
            foreach ($parts as $part) {
                $text .= (string) $part;
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private function firstSheetPath(ZipArchive $zip): string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbook === false || $rels === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $previous = libxml_use_internal_errors(true);
        $workbookXml = simplexml_load_string($workbook);
        $relsXml = simplexml_load_string($rels);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($workbookXml === false || $relsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbookXml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbookXml->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $sheets = $workbookXml->xpath('//m:sheets/m:sheet') ?: [];
        $first = $sheets[0] ?? null;
        if ($first === null) {
            return 'xl/worksheets/sheet1.xml';
        }

        $attributes = $first->attributes('r', true);
        $relationshipId = (string) ($attributes['id'] ?? '');
        if ($relationshipId === '') {
            return 'xl/worksheets/sheet1.xml';
        }

        $relsXml->registerXPathNamespace('pr', 'http://schemas.openxmlformats.org/package/2006/relationships');
        foreach ($relsXml->xpath('//pr:Relationship') ?: [] as $relationship) {
            if ((string) $relationship['Id'] !== $relationshipId) {
                continue;
            }

            $target = ltrim((string) $relationship['Target'], '/');
            if (str_starts_with($target, 'xl/')) {
                return $target;
            }

            return 'xl/'.$target;
        }

        return 'xl/worksheets/sheet1.xml';
    }

    /**
     * @param  list<string>  $sharedStrings
     * @return list<list<string>>
     */
    private function parseSheetRows(string $sheetXml, array $sharedStrings): array
    {
        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($sheetXml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($document === false) {
            throw new \RuntimeException('Could not parse Excel worksheet.');
        }

        $document->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = [];

        foreach ($document->xpath('//m:sheetData/m:row') ?: [] as $row) {
            $cells = [];
            $maxIndex = -1;

            foreach ($row->c ?? [] as $cell) {
                $reference = (string) ($cell['r'] ?? '');
                $index = $this->columnIndexFromReference($reference);
                if ($index === null) {
                    continue;
                }

                $maxIndex = max($maxIndex, $index);
                $cells[$index] = $this->cellValue($cell, $sharedStrings);
            }

            if ($maxIndex < 0) {
                continue;
            }

            $values = [];
            for ($i = 0; $i <= $maxIndex; $i++) {
                $values[] = $cells[$i] ?? '';
            }

            if ($this->rowIsEmpty($values)) {
                continue;
            }

            $rows[] = $values;
        }

        return $rows;
    }

    /**
     * @param  list<string>  $sharedStrings
     */
    private function cellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) ($cell['t'] ?? '');

        if ($type === 's') {
            $index = (int) ($cell->v ?? -1);

            return $sharedStrings[$index] ?? '';
        }

        if ($type === 'inlineStr') {
            $cell->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $parts = $cell->xpath('.//m:t') ?: [];
            $text = '';
            foreach ($parts as $part) {
                $text .= (string) $part;
            }

            return $text;
        }

        if ($type === 'b') {
            return ((string) ($cell->v ?? '0')) === '1' ? 'TRUE' : 'FALSE';
        }

        return trim((string) ($cell->v ?? ''));
    }

    private function columnIndexFromReference(string $reference): ?int
    {
        if (! preg_match('/^([A-Z]+)/i', $reference, $matches)) {
            return null;
        }

        $letters = strtoupper($matches[1]);
        $index = 0;
        for ($i = 0, $length = strlen($letters); $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    /**
     * @param  list<string>  $values
     */
    private function rowIsEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if (trim($value) !== '') {
                return false;
            }
        }

        return true;
    }
}
