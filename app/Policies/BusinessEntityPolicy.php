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
     *
     * Portfolio is firm-shared: any authenticated user may open any entity.
     * user_id is creator metadata, not an ACL boundary (see README access model).
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
