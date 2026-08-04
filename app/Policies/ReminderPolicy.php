<?php

namespace App\Policies;

use App\Models\Reminder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReminderPolicy
{
    use HandlesAuthorization;

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
    public function view(User $user, Reminder $reminder): bool
    {
        if ((int) $reminder->user_id === (int) $user->id) {
            return true;
        }

        if ($reminder->business_entity_id) {
            $reminder->loadMissing('businessEntity');

            return $reminder->businessEntity ? $user->can('view', $reminder->businessEntity) : false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Reminder $reminder): bool
    {
        if ((int) $reminder->user_id === (int) $user->id) {
            return true;
        }

        if ($reminder->business_entity_id) {
            $reminder->loadMissing('businessEntity');

            return $reminder->businessEntity ? $user->can('update', $reminder->businessEntity) : false;
        }

        return true;
    }

    public function delete(User $user, Reminder $reminder): bool
    {
        return $this->update($user, $reminder);
    }
} 