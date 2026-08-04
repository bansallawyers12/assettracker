<?php

namespace App\Policies;

use App\Models\BusinessEntity;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    private function canAccessEntity(User $user, ?BusinessEntity $entity, string $ability = 'view'): bool
    {
        return $entity !== null && $user->can($ability, $entity);
    }

    public function view(User $user, Document $document): bool
    {
        return $this->canAccessEntity($user, $document->businessEntity, 'view');
    }

    public function update(User $user, Document $document): bool
    {
        return $this->canAccessEntity($user, $document->businessEntity, 'update');
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->canAccessEntity($user, $document->businessEntity, 'update');
    }
}
