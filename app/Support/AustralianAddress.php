<?php

namespace App\Support;

/**
 * Display-only normalisation for Australian street addresses.
 * Does not mutate stored values — list/detail UIs can render consistently
 * when source data mixes Google Places, ALL CAPS, and free-text entry.
 */
class AustralianAddress
{
    /** @var list<string> */
    private const STATE_CODES = ['ACT', 'NSW', 'NT', 'QLD', 'SA', 'TAS', 'VIC', 'WA'];

    /**
     * Map common street-type spellings to a short, consistent form.
     *
     * @var array<string, string>
     */
    private const STREET_TYPES = [
        'street' => 'St',
        'st' => 'St',
        'st.' => 'St',
        'road' => 'Rd',
        'rd' => 'Rd',
        'rd.' => 'Rd',
        'avenue' => 'Ave',
        'ave' => 'Ave',
        'ave.' => 'Ave',
        'drive' => 'Dr',
        'dr' => 'Dr',
        'dr.' => 'Dr',
        'circuit' => 'Cct',
        'cct' => 'Cct',
        'place' => 'Pl',
        'pl' => 'Pl',
        'pl.' => 'Pl',
        'crescent' => 'Cres',
        'cres' => 'Cres',
        'highway' => 'Hwy',
        'hwy' => 'Hwy',
        'parade' => 'Pde',
        'pde' => 'Pde',
        'boulevard' => 'Blvd',
        'blvd' => 'Blvd',
        'terrace' => 'Tce',
        'tce' => 'Tce',
        'court' => 'Ct',
        'ct' => 'Ct',
        'ct.' => 'Ct',
        'lane' => 'Lane',
        'ln' => 'Lane',
    ];

    public static function formatForDisplay(?string $address): string
    {
        if ($address === null) {
            return '';
        }

        $normalized = preg_replace('/\s+/u', ' ', trim($address)) ?? '';
        if ($normalized === '') {
            return '';
        }

        // List pages are AU-only — drop trailing country noise.
        $normalized = preg_replace('/,?\s*Australia\.?\s*$/iu', '', $normalized) ?? $normalized;
        $normalized = trim($normalized, " \t\n\r\0\x0B,");

        if ($normalized === '') {
            return '';
        }

        $normalized = self::insertMissingCommas($normalized);

        $parts = array_map('trim', explode(',', $normalized));
        $parts = array_values(array_filter($parts, fn (string $part) => $part !== ''));

        $formattedParts = array_map(
            fn (string $part) => self::formatPart($part),
            $parts
        );

        return implode(', ', $formattedParts);
    }

    /**
     * When addresses lack commas ("6 LISBON STREET GLEN WAVERLEY VIC 3150"),
     * insert them before suburb / state+postcode when the pattern is clear.
     */
    private static function insertMissingCommas(string $address): string
    {
        if (str_contains($address, ',')) {
            return $address;
        }

        $states = implode('|', self::STATE_CODES);
        if (! preg_match('/^(.*?)\s+('.$states.')\s+(\d{4})$/iu', $address, $m)) {
            return $address;
        }

        $beforeState = trim($m[1]);
        $state = strtoupper($m[2]);
        $postcode = $m[3];

        // Split street vs suburb on the last street-type token when present.
        $streetTypes = implode('|', array_map('preg_quote', array_keys(self::STREET_TYPES)));
        if (preg_match('/^(.*?)\b('.$streetTypes.')\b\s+(.+)$/iu', $beforeState, $streetMatch)) {
            $street = trim($streetMatch[1].' '.$streetMatch[2]);
            $suburb = trim($streetMatch[3]);

            return $street.', '.$suburb.', '.$state.' '.$postcode;
        }

        return $beforeState.', '.$state.' '.$postcode;
    }

    private static function formatPart(string $part): string
    {
        $tokens = preg_split('/\s+/u', $part) ?: [];
        $formatted = [];

        foreach ($tokens as $token) {
            $formatted[] = self::formatToken($token);
        }

        return implode(' ', $formatted);
    }

    private static function formatToken(string $token): string
    {
        // Preserve unit/street numbers like 8/278 or 706/343.
        if (preg_match('/^\d+(?:\/\d+)?$/u', $token)) {
            return $token;
        }

        if (preg_match('/^\d{4}$/u', $token)) {
            return $token;
        }

        $upper = strtoupper($token);
        if (in_array($upper, self::STATE_CODES, true)) {
            return $upper;
        }

        $lower = strtolower(rtrim($token, '.'));
        if (isset(self::STREET_TYPES[$lower])) {
            return self::STREET_TYPES[$lower];
        }

        // Title-case words; keep internal punctuation (O'Brien, St.).
        if (str_contains($token, "'")) {
            return implode("'", array_map(
                fn (string $bit) => $bit === '' ? '' : mb_convert_case($bit, MB_CASE_TITLE, 'UTF-8'),
                explode("'", $token)
            ));
        }

        return mb_convert_case($token, MB_CASE_TITLE, 'UTF-8');
    }
}
