<?php

namespace App\Policies;

use App\Models\Commitment;
use App\Models\User;

class CommitmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }
    public function view(User $user, Commitment $commitment): bool
    {
        $commitment->loadMissing('businessEntity');

        return $commitment->businessEntity !== null && $user->can('view', $commitment->businessEntity);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Commitment $commitment): bool
    {
        $commitment->loadMissing('businessEntity');

        return $commitment->businessEntity !== null && $user->can('update', $commitment->businessEntity);
    }

    public function delete(User $user, Commitment $commitment): bool
    {
        $commitment->loadMissing('businessEntity');

        return $commitment->businessEntity !== null && $user->can('update', $commitment->businessEntity);
    }
}
