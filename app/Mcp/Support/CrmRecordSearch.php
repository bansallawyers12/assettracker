<?php

namespace App\Mcp\Support;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\Person;

final class CrmRecordSearch
{
    /**
     * @return list<array<string, mixed>>
     */
    public function search(string $query, string $type, int $limit): array
    {
        $needle = mb_strtolower(trim($query));
        $limit = max(1, min($limit, 50));

        if ($needle === '') {
            return [];
        }

        $types = $type === 'all' ? ['entity', 'asset', 'person'] : [$type];
        $perType = max(1, (int) ceil($limit / count($types)));
        $results = [];

        foreach ($types as $selectedType) {
            $results = array_merge($results, match ($selectedType) {
                'entity' => $this->searchEntities($needle, $perType),
                'asset' => $this->searchAssets($needle, $perType),
                'person' => $this->searchPersons($needle, $perType),
                default => [],
            });
        }

        return array_slice($results, 0, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchEntities(string $needle, int $limit): array
    {
        return BusinessEntity::query()
            ->operationalEntities()
            ->where(function ($query) use ($needle): void {
                $like = $this->escapedLike($needle);
                $query->whereRaw('LOWER(legal_name) LIKE ? ESCAPE \'\\\'', [$like])
                    ->orWhereRaw('LOWER(COALESCE(trading_name, \'\')) LIKE ? ESCAPE \'\\\'', [$like]);
            })
            ->orderBy('legal_name')
            ->limit($limit)
            ->get()
            ->map(fn (BusinessEntity $entity): array => [
                'type' => 'entity',
                'id' => $entity->id,
                'label' => $entity->legal_name,
                'sub' => trim(implode(' · ', array_filter([
                    $entity->entity_type,
                    $entity->trading_name && $entity->trading_name !== $entity->legal_name ? $entity->trading_name : null,
                    $entity->status,
                ]))),
                'status' => $entity->status,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchAssets(string $needle, int $limit): array
    {
        return Asset::query()
            ->with('businessEntity')
            ->whereHas('businessEntity', fn ($query) => $query->operationalEntities())
            ->where(function ($query) use ($needle): void {
                $like = $this->escapedLike($needle);
                $query->whereRaw('LOWER(name) LIKE ? ESCAPE \'\\\'', [$like])
                    ->orWhereRaw('LOWER(COALESCE(address, \'\')) LIKE ? ESCAPE \'\\\'', [$like])
                    ->orWhereRaw('LOWER(COALESCE(registration_number, \'\')) LIKE ? ESCAPE \'\\\'', [$like]);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Asset $asset): array => [
                'type' => 'asset',
                'id' => $asset->id,
                'label' => $asset->name,
                'sub' => trim(implode(' · ', array_filter([
                    $asset->businessEntity?->legal_name,
                    $asset->asset_type,
                    $asset->status,
                ]))),
                'status' => $asset->status,
                'business_entity_id' => $asset->business_entity_id,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function searchPersons(string $needle, int $limit): array
    {
        $matches = [];

        foreach (Person::query()->linkedToOperationalEntities()->with(['businessEntities' => fn ($query) => $query->operationalEntities()])->cursor() as $person) {
            if (! $this->personMatches($person, $needle)) {
                continue;
            }

            $entityNames = $person->businessEntities
                ->pluck('legal_name')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $matches[] = [
                'type' => 'person',
                'id' => $person->id,
                'label' => $person->displayName(),
                'sub' => implode(', ', $entityNames),
                'status' => $person->status,
                'email' => $person->email,
            ];

            if (count($matches) >= $limit) {
                break;
            }
        }

        return $matches;
    }

    private function personMatches(Person $person, string $needle): bool
    {
        return $this->contains($person->displayName(), $needle)
            || $this->contains($person->email, $needle)
            || $this->contains($person->phone_number, $needle);
    }

    private function contains(?string $haystack, string $needle): bool
    {
        if ($haystack === null || $haystack === '') {
            return false;
        }

        return mb_stripos($haystack, $needle) !== false;
    }

    private function escapedLike(string $needle): string
    {
        return '%'.addcslashes($needle, '%_\\').'%';
    }
}
