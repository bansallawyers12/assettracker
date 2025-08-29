<?php

namespace App\Policies;

use App\Models\ContactList;
use App\Models\User;
use App\Models\BusinessEntity;

class ContactListPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, BusinessEntity $businessEntity)
    {
        return $user->id === $businessEntity->user_id;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BusinessEntity $businessEntity, ContactList $contactList)
    {
         return $user->id === $businessEntity->user_id && $contactList->business_entity_id === $businessEntity->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, BusinessEntity $businessEntity)
    {
        return $user->id === $businessEntity->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BusinessEntity $businessEntity, ContactList $contactList)
    {
        return $user->id === $businessEntity->user_id && $contactList->business_entity_id === $businessEntity->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BusinessEntity $businessEntity, ContactList $contactList)
    {
        return $user->id === $businessEntity->user_id && $contactList->business_entity_id === $businessEntity->id;
    }
} 