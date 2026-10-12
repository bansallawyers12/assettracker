<?php

namespace App\Support;

use App\Models\BusinessEntity;
use App\Models\Person;
use App\Models\Tenant;

class OutgoingMailRecipients
{
    /**
     * Addresses the sender can pick: company, contacts, people, and tenants.
     *
     * @return list<array{email: string, label: string}>
     */
    public function options(BusinessEntity $entity, ?int $assetId = null): array
    {
        $options = [];

        $push = function (?string $email, string $label) use (&$options): void {
            $email = trim((string) $email);
            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                return;
            }

            $key = strtolower($email);
            if (isset($options[$key])) {
                return;
            }

            $options[$key] = [
                'email' => $email,
                'label' => $label,
            ];
        };

        if (! $entity->registeredEmailIsPlaceholder()) {
            $push($entity->registered_email, 'Company — '.$entity->legal_name);
        }

        foreach ($entity->contactLists()->orderBy('id')->get() as $contact) {
            $name = trim(($contact->first_name ?? '').' '.($contact->last_name ?? ''));
            $push($contact->email, 'Contact — '.($name !== '' ? $name : 'Contact'));
        }

        $personIds = $entity->persons()->pluck('person_id');
        if ($personIds->isNotEmpty()) {
            foreach (Person::query()->whereIn('id', $personIds)->orderBy('id')->get() as $person) {
                $push($person->email, 'Person — '.$person->displayName());
            }
        }

        $tenants = Tenant::query()
            ->whereHas('asset', fn ($query) => $query->where('business_entity_id', $entity->id))
            ->when($assetId, fn ($query) => $query->where('asset_id', $assetId))
            ->orderBy('name')
            ->get();

        foreach ($tenants as $tenant) {
            $push($tenant->email, 'Tenant — '.($tenant->name ?: 'Tenant'));
        }

        return array_values($options);
    }
}
