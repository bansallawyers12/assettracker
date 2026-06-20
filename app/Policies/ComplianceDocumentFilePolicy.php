<?php

namespace App\Policies;

use App\Models\ComplianceDocumentFile;
use App\Models\User;

class ComplianceDocumentFilePolicy
{
    public function view(User $user, ComplianceDocumentFile $file): bool
    {
        return $file->yearRecord?->businessEntity !== null;
    }

    public function update(User $user, ComplianceDocumentFile $file): bool
    {
        return $file->yearRecord?->businessEntity !== null;
    }
}
