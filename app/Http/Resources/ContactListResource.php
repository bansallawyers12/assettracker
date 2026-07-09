<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContactListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'business_entity_id' => $this->business_entity_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => trim($this->first_name.' '.$this->last_name),
            'gender' => $this->gender,
            'email' => $this->email,
            'phone_no' => $this->phone_no,
            'mobile_no' => $this->mobile_no,
            'address' => $this->address,
            'zip_code' => $this->zip_code,
            'edit_url' => route('entities.contact-lists.form.edit', [$this->business_entity_id, $this->id]),
        ];
    }
}
