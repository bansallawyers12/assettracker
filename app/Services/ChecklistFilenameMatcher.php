<?php

namespace App\Services;

class ChecklistFilenameMatcher
{
    /**
     * @param  list<string>  $checklists
     * @return array<string, array{checklist: string, confidence: string, score: float, method: string}|null>
     */
    public function matchFiles(array $files, array $checklists): array
    {
        $out = [];
        foreach ($files as $file) {
            $name = is_array($file) ? ($file['name'] ?? '') : (string) $file;
            $out[$name] = $this->findBest($name, $checklists);
        }

        return $out;
    }

    /**
     * @param  list<string>  $checklists
     * @return array{checklist: string, confidence: string, score: float, method: string}|null
     */
    public function findBest(string $fileName, array $checklists): ?array
    {
        if ($fileName === '' || $checklists === []) {
            return null;
        }

        $clean = $this->cleanFileName($fileName);
        $fileLower = strtolower($clean);
        $best = null;
        $bestScore = 0.0;

        foreach ($checklists as $checklist) {
            $cl = strtolower($checklist);
            if ($fileLower === $cl) {
                return ['checklist' => $checklist, 'confidence' => 'high', 'score' => 100.0, 'method' => 'exact'];
            }

            similar_text($fileLower, $cl, $pct);
            if ($pct > 85) {
                return ['checklist' => $checklist, 'confidence' => 'high', 'score' => $pct, 'method' => 'fuzzy'];
            }
            if ($pct > $bestScore && $pct > 55) {
                $bestScore = $pct;
                $best = ['checklist' => $checklist, 'confidence' => $pct > 70 ? 'medium' : 'low', 'score' => $pct, 'method' => 'fuzzy'];
            }

            if (str_contains($fileLower, $cl) || str_contains($cl, $fileLower)) {
                $score = min(100, max(strlen($cl), strlen($fileLower)) > 0 ? 80 : 0);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = ['checklist' => $checklist, 'confidence' => 'medium', 'score' => $score, 'method' => 'contains'];
                }
            }
        }

        return $best;
    }

    private function cleanFileName(string $name): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = preg_replace('/^[^_]+_/', '', $base);
        $base = preg_replace('/_\d{10,}.*$/', '', $base);

        return trim(str_replace(['_', '-'], ' ', $base));
    }
}
