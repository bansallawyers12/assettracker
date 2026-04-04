<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\BusinessEntity;
use App\Models\EntityPerson;

/**
 * Builds the JSON index for the global header search (entities, assets, persons).
 */
final class HeaderSearchIndex
{
    /**
     * @return list<array{type: string, label: string, sub: string, url: string}>
     */
    public static function build(): array
    {
        $businessEntities = BusinessEntity::query()->operationalEntities()->get();
        $assets = Asset::query()
            ->whereIn('business_entity_id', $businessEntities->modelKeys())
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $persons = EntityPerson::with(['person', 'businessEntity'])->get();
        $uniquePersons = $persons->where('person_id', '!=', null)
            ->groupBy('person_id')
            ->map(function ($entityPersonGroup) {
                $first = $entityPersonGroup->first();

                return [
                    'person' => $first->person,
                    'entityPersons' => $entityPersonGroup,
                ];
            })
            ->values();

        $entityRows = $businessEntities->map(function ($e) {
            $label = trim((string) ($e->legal_name ?? ''));
            if ($label === '') {
                return null;
            }

            return [
                'type' => 'entity',
                'label' => $label,
                'sub' => $e->entity_type ?? '',
                'url' => route('business-entities.show', $e),
            ];
        })->filter()->values();

        $assetRows = $assets->map(function ($a) use ($businessEntities) {
            $label = trim((string) ($a->name ?? ''));
            if ($label === '') {
                return null;
            }

            $entityName = $businessEntities->firstWhere('id', $a->business_entity_id)?->legal_name ?? '';
            $parts = array_filter([$entityName, $a->asset_type ?? '']);

            return [
                'type' => 'asset',
                'label' => $label,
                'sub' => implode(' · ', $parts),
                'url' => route('business-entities.assets.show', [$a->business_entity_id, $a->id]),
            ];
        })->filter()->values();

        $personRows = $uniquePersons->map(function ($pd) {
            $person = $pd['person'] ?? null;
            if ($person === null) {
                return null;
            }

            $label = trim(($person->first_name ?? '').' '.($person->last_name ?? ''));
            if ($label === '') {
                return null;
            }

            $roles = collect($pd['entityPersons'])->pluck('businessEntity.legal_name')->filter()->unique()->join(', ');

            return [
                'type' => 'person',
                'label' => $label,
                'sub' => $roles,
                'url' => route('persons.show', $person),
            ];
        })->filter()->values();

        return $entityRows->concat($assetRows)->concat($personRows)->values()->all();
    }
}
