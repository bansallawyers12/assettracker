<?php

namespace App\Policies;

use App\Models\BusinessEntity;
use App\Models\ContactList;
use App\Models\User;

class ContactListPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, BusinessEntity $businessEntity)
    {
        return $user->can('view', $businessEntity);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BusinessEntity $businessEntity, ContactList $contactList)
    {
        return $contactList->business_entity_id === $businessEntity->id
            && $user->can('view', $businessEntity);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, BusinessEntity $businessEntity)
    {
        return $user->can('update', $businessEntity);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BusinessEntity $businessEntity, ContactList $contactList)
    {
        return $contactList->business_entity_id === $businessEntity->id
            && $user->can('update', $businessEntity);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BusinessEntity $businessEntity, ContactList $contactList)
    {
        return $contactList->business_entity_id === $businessEntity->id
            && $user->can('update', $businessEntity);
    }
}
