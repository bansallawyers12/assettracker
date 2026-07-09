<?php

namespace App\Http\Resources;

use App\Models\BankAccount;
use Illuminate\Http\Resources\Json\JsonResource;

class EntityPersonResource extends JsonResource
{
    public function toArray($request): array
    {
        $person = $this->person;
        $trustee = $this->trusteeEntity;

        $displayName = $person
            ? trim($person->first_name.' '.$person->last_name)
            : ($trustee ? $trustee->legal_name.' (Trustee)' : 'Unknown');

        return [
            'id' => $this->id,
            'business_entity_id' => $this->business_entity_id,
            'person_id' => $this->person_id,
            'entity_trustee_id' => $this->entity_trustee_id,
            'role' => $this->role,
            'role_status' => $this->role_status,
            'appointment_date' => $this->appointment_date?->format('Y-m-d'),
            'appointment_date_display' => $this->appointment_date?->format('d/m/Y'),
            'resignation_date' => $this->resignation_date?->format('Y-m-d'),
            'resignation_date_display' => $this->resignation_date?->format('d/m/Y'),
            'shares_percentage' => $this->shares_percentage,
            'authority_level' => $this->authority_level,
            'asic_due_date' => $this->asic_due_date?->format('Y-m-d'),
            'asic_due_date_display' => $this->asic_due_date?->format('d/m/Y'),
            'display_name' => $displayName,
            'email' => $person?->email,
            'phone_number' => $person?->phone_number,
            'tfn' => $person?->tfn,
            'abn' => $person?->abn,
            'is_corporate_trustee' => (bool) $this->entity_trustee_id,
            'is_appointor' => $this->role === 'Appointor',
            'bank_account_url' => $person
                ? BankAccount::createUrlForHolder(BankAccount::HOLDER_PERSON, $person->id, $this->business_entity_id)
                : null,
            'edit_url' => route('entities.persons.form.edit', [$this->business_entity_id, $this->id]),
            'detail_url' => route('entities.persons.detail', [$this->business_entity_id, $this->id]),
        ];
    }
}
