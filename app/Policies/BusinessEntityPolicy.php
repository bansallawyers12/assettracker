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
     * Portfolio remains firm-shared for all app roles: any authenticated user may open any entity.
     * Mutations are gated by app role (see update/create/delete).
     */
    public function view(User $user, BusinessEntity $businessEntity): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create business entities.
     */
    public function create(User $user): bool
    {
        return $user->canMutatePortfolio();
    }

    /**
     * Determine whether the user can update the business entity.
     */
    public function update(User $user, BusinessEntity $businessEntity): bool
    {
        return $user->canMutatePortfolio();
    }

    /**
     * Determine whether the user can record a dashboard transaction for the entity.
     *
     * Viewers may post income/expense from the dashboard but cannot edit entity settings
     * or mutate other portfolio records (see update/create/delete).
     */
    public function recordTransaction(User $user, BusinessEntity $businessEntity): bool
    {
        return $this->view($user, $businessEntity);
    }

    /**
     * Determine whether the user can delete the business entity.
     */
    public function delete(User $user, BusinessEntity $businessEntity): bool
    {
        return $user->canMutatePortfolio();
    }
}
