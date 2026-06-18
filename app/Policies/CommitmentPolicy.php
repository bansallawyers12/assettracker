<?php

namespace App\Policies;

use App\Models\Commitment;
use App\Models\User;

class CommitmentPolicy
{
    private function canAccessViaEntity(Commitment $commitment): bool
    {
        return $commitment->businessEntity !== null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Commitment $commitment): bool
    {
        return $this->canAccessViaEntity($commitment);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Commitment $commitment): bool
    {
        return $this->canAccessViaEntity($commitment);
    }

    public function delete(User $user, Commitment $commitment): bool
    {
        return $this->canAccessViaEntity($commitment);
    }
}
