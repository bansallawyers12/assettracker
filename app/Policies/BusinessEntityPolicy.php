<?php

namespace App\Policies;

use App\Models\BusinessEntity;
use App\Models\User;

class BusinessEntityPolicy
{
    /**
     * Determine whether the user can view any business entities (e.g. index).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the business entity.
     */
    public function view(User $user, BusinessEntity $businessEntity)
    {
        return true;
    }

    /**
     * Determine whether the user can create business entities.
     */
    public function create(User $user)
    {
        // Allow all authenticated users to create business entities
        return true;
    }

    /**
     * Determine whether the user can update the business entity.
     */
    public function update(User $user, BusinessEntity $businessEntity)
    {
        return true;
    }

    /**
     * Determine whether the user can delete the business entity.
     */
    public function delete(User $user, BusinessEntity $businessEntity)
    {
        return true;
    }
} 