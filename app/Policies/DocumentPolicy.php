<?php

namespace App\Policies;

use App\Models\BusinessEntity;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    private function canAccessEntity(?BusinessEntity $entity): bool
    {
        return $entity !== null;
    }

    public function view(User $user, Document $document): bool
    {
        return $this->canAccessEntity($document->businessEntity);
    }

    public function update(User $user, Document $document): bool
    {
        return $this->canAccessEntity($document->businessEntity);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->canAccessEntity($document->businessEntity);
    }
}
