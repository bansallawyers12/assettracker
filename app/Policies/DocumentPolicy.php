<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document)
    {
        return true;
    }

    public function update(User $user, Document $document)
    {
        return true;
    }

    public function delete(User $user, Document $document)
    {
        return true;
    }
} 