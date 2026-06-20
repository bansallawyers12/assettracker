<?php

namespace App\Services;

class ComplianceFilenameMatcher
{
    /** @var array<string, list<string>> */
    private array $aliases = [
        'Income Tax Return' => ['itr', 'tax return', 'income tax'],
        'Annual Accounts' => ['annual accounts', 'financial statements'],
        'BAS (Annual summary)' => ['bas annual', 'bas summary', 'bas_annual'],
        'BAS Q1 (Jul–Sep)' => ['bas q1', 'bas_q1', 'jul sep'],
        'BAS Q2 (Oct–Dec)' => ['bas q2', 'bas_q2', 'oct dec'],
        'BAS Q3 (Jan–Mar)' => ['bas q3', 'bas_q3', 'jan mar'],
        'BAS Q4 (Apr–Jun)' => ['bas q4', 'bas_q4', 'apr jun'],
        'ASIC Annual Statement' => ['asic', 'annual statement'],
        'Land Tax' => ['land tax', 'landtax', 'land_tax'],
        'Council Rates' => ['council rates', 'council_rates', 'rates notice'],
        'Water Rates' => ['water rates', 'water_rates'],
        'Insurance Certificate' => ['insurance', 'insurance certificate'],
        'Depreciation Schedule' => ['depreciation', 'depreciation schedule'],
    ];

    public function __construct(
        private ChecklistFilenameMatcher $baseMatcher
    ) {}

    /**
     * @param  list<array{name: string, label: string, type_code: string|null}>  $checklistItems
     * @return array<string, array{checklist: string, confidence: string, score: float, method: string}|null>
     */
    public function matchFiles(array $files, array $checklistItems): array
    {
        $labels = array_values(array_unique(array_filter(array_column($checklistItems, 'label'))));
        $labelByCode = [];
        foreach ($checklistItems as $item) {
            if (! empty($item['type_code'])) {
                $labelByCode[$item['type_code']] = $item['label'];
            }
        }

        $out = [];
        foreach ($files as $file) {
            $name = is_array($file) ? ($file['name'] ?? '') : (string) $file;
            $match = $this->matchOne($name, $labels, $labelByCode);
            $out[$name] = $match ? [
                'checklist' => $match['checklist'],
                'confidence' => $match['confidence'],
                'score' => $match['score'],
                'method' => $match['method'],
            ] : null;
        }

        return $out;
    }

    /**
     * @param  list<string>  $labels
     * @param  array<string, string>  $labelByCode
     * @return array{checklist: string, confidence: string, score: float, method: string}|null
     */
    private function matchOne(string $fileName, array $labels, array $labelByCode): ?array
    {
        if ($fileName === '' || $labels === []) {
            return null;
        }

        $clean = strtolower($this->cleanFileName($fileName));

        foreach ($labelByCode as $code => $label) {
            if (str_contains($clean, strtolower($code)) || str_contains($clean, str_replace('_', ' ', strtolower($code)))) {
                return ['checklist' => $label, 'confidence' => 'high', 'score' => 95.0, 'method' => 'type_code'];
            }
        }

        foreach ($this->aliases as $label => $terms) {
            if (! in_array($label, $labels, true)) {
                continue;
            }
            foreach ($terms as $term) {
                if (str_contains($clean, strtolower($term))) {
                    return ['checklist' => $label, 'confidence' => 'high', 'score' => 90.0, 'method' => 'alias'];
                }
            }
        }

        $base = $this->baseMatcher->findBest($fileName, $labels);

        return $base ? [
            'checklist' => $base['checklist'],
            'confidence' => $base['confidence'],
            'score' => $base['score'],
            'method' => $base['method'],
        ] : null;
    }

    private function cleanFileName(string $name): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = preg_replace('/^[^_]+_/', '', $base);
        $base = preg_replace('/_\d{10,}.*$/', '', $base);

        return trim(str_replace(['_', '-'], ' ', $base));
    }
}
