<?php

namespace App\Services;

use App\Models\BusinessEntity;
use App\Models\EntityPerson;
use App\Models\Note;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CompanyRegisterImporter
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{created: int, updated: int, skipped: int, directors_linked: int, warnings: array<int, string>}
     */
    public function importFromRows(array $rows, int $userId, bool $dryRun = false, bool $skipDirectors = false): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $directorsLinked = 0;
        $warnings = [];

        foreach ($rows as $index => $row) {
            $line = $index + 1;
            $legalName = trim((string) ($row['legal_name'] ?? ''));
            if ($legalName === '') {
                continue;
            }

            $abn = $this->normalizeDigits($row['abn'] ?? null);
            $acn = $this->normalizeDigits($row['acn'] ?? null);
            $trustOf = trim((string) ($row['under_trust_of'] ?? ''));
            $statusLabel = trim((string) ($row['classification'] ?? ''));
            $directorName = trim((string) ($row['director_name'] ?? ''));
            $address = trim((string) ($row['address'] ?? ''));
            $asicRenewal = $this->parseDate($row['asic_renewal'] ?? null);

            $entity = BusinessEntity::query()
                ->when($abn, fn ($q) => $q->where('abn', $abn))
                ->when(! $abn && $acn, fn ($q) => $q->where('acn', $acn))
                ->when(! $abn && ! $acn, fn ($q) => $q->where('legal_name', $legalName))
                ->first();

            if ($entity && (int) $entity->user_id !== $userId) {
                $skipped++;
                $warnings[] = "Row {$line}: skipped — {$legalName} matches entity id {$entity->id} owned by user {$entity->user_id}.";

                continue;
            }

            $noteParts = array_filter([
                $trustOf !== '' ? "Under trust of: {$trustOf}" : null,
                $statusLabel !== '' ? "Classification: {$statusLabel}" : null,
            ]);
            $noteBody = $noteParts !== [] ? implode("\n", $noteParts) : null;

            $payload = [
                'legal_name' => $legalName,
                'entity_type' => 'Company',
                'abn' => $abn,
                'acn' => $acn,
                'registered_address' => $address !== '' ? $address : '—',
                'asic_renewal_date' => $asicRenewal,
                'user_id' => $userId,
                'status' => 'Active',
            ];

            if ($dryRun) {
                $entity ? $updated++ : $created++;

                continue;
            }

            DB::transaction(function () use (
                $entity,
                $payload,
                $noteBody,
                $userId,
                $skipDirectors,
                $directorName,
                &$created,
                &$updated,
                &$directorsLinked
            ): void {
                if ($entity) {
                    $entity->update($payload);
                    $updated++;
                    $be = $entity;
                } else {
                    $be = BusinessEntity::query()->create($payload);
                    $created++;
                }

                if ($noteBody !== null) {
                    $hasNote = Note::query()
                        ->where('business_entity_id', $be->id)
                        ->where('user_id', $userId)
                        ->where('content', $noteBody)
                        ->exists();
                    if (! $hasNote) {
                        Note::query()->create([
                            'business_entity_id' => $be->id,
                            'user_id' => $userId,
                            'content' => $noteBody,
                            'is_reminder' => false,
                        ]);
                    }
                }

                if ($skipDirectors || $directorName === '') {
                    return;
                }

                [$first, $last] = $this->splitPersonName($directorName);
                if ($first === null || $last === null) {
                    return;
                }

                $person = Person::query()->lazy()->first(
                    fn (Person $p) => (string) $p->first_name === $first && (string) $p->last_name === $last
                );

                if (! $person) {
                    $person = Person::query()->create([
                        'first_name' => $first,
                        'last_name' => $last,
                        'status' => 'Active',
                    ]);
                }

                $already = EntityPerson::query()
                    ->where('business_entity_id', $be->id)
                    ->where('person_id', $person->id)
                    ->where('role', 'Director')
                    ->where('role_status', 'Active')
                    ->exists();

                if (! $already) {
                    EntityPerson::query()->create([
                        'business_entity_id' => $be->id,
                        'person_id' => $person->id,
                        'role' => 'Director',
                        'appointment_date' => Carbon::now()->startOfDay(),
                        'role_status' => 'Active',
                    ]);
                    $directorsLinked++;
                }
            });
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'directors_linked' => $directorsLinked,
            'warnings' => $warnings,
        ];
    }

    private function normalizeDigits(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            $digits = preg_replace('/\D/', '', (string) (int) $value);

            return $digits !== '' ? $digits : null;
        }
        $digits = preg_replace('/\D/', '', (string) $value);

        return $digits !== '' ? $digits : null;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof Carbon) {
            return $value;
        }
        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function splitPersonName(string $full): array
    {
        $full = trim($full);
        if ($full === '') {
            return [null, null];
        }
        $parts = preg_split('/\s+/u', $full, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false || $parts === []) {
            return [null, null];
        }
        if (count($parts) === 1) {
            return [$parts[0], $parts[0]];
        }
        $last = array_pop($parts);
        $first = implode(' ', $parts);

        return [$first, $last];
    }
}
