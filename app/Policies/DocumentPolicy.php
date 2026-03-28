<?php

namespace App\Policies;

use App\Models\BusinessEntity;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    private function canAccessEntity(User $user, ?BusinessEntity $entity): bool
    {
        if (! $entity) {
            return false;
        }

        if ($user->isPrimaryAdministrator()) {
            return true;
        }

        return (int) $entity->user_id === (int) $user->id;
    }

    public function view(User $user, Document $document): bool
    {
        return $this->canAccessEntity($user, $document->businessEntity);
    }

    public function update(User $user, Document $document): bool
    {
        return $this->canAccessEntity($user, $document->businessEntity);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->canAccessEntity($user, $document->businessEntity);
    }
}
