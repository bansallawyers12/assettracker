<?php

namespace App\Policies;

use App\Models\EmailTemplate;
use App\Models\User;

class EmailTemplatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EmailTemplate $emailTemplate): bool
    {
        return $emailTemplate->is_system || (int) $emailTemplate->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->canMutatePortfolio();
    }

    public function update(User $user, EmailTemplate $emailTemplate): bool
    {
        return $user->canMutatePortfolio()
            && ! $emailTemplate->is_system
            && (int) $emailTemplate->user_id === (int) $user->id;
    }

    public function delete(User $user, EmailTemplate $emailTemplate): bool
    {
        return $this->update($user, $emailTemplate);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EmailTemplate $emailTemplate): bool
    {
        return $this->update($user, $emailTemplate);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EmailTemplate $emailTemplate): bool
    {
        return $this->delete($user, $emailTemplate);
    }
}
