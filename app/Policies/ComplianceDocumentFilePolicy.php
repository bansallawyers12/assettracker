<?php

namespace App\Policies;

use App\Models\ComplianceDocumentFile;
use App\Models\User;

class ComplianceDocumentFilePolicy
{
    public function view(User $user, ComplianceDocumentFile $file): bool
    {
        $file->loadMissing('yearRecord');

        return $file->yearRecord !== null;
    }

    public function update(User $user, ComplianceDocumentFile $file): bool
    {
        $file->loadMissing('yearRecord');

        return $file->yearRecord !== null;
    }
}
