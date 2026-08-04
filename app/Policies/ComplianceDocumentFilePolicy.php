<?php

namespace App\Policies;

use App\Models\ComplianceDocumentFile;
use App\Models\User;

class ComplianceDocumentFilePolicy
{
    public function view(User $user, ComplianceDocumentFile $file): bool
    {
        $file->loadMissing('yearRecord.businessEntity');

        return $file->yearRecord?->businessEntity !== null && $user->can('view', $file->yearRecord->businessEntity);
    }

    public function update(User $user, ComplianceDocumentFile $file): bool
    {
        $file->loadMissing('yearRecord.businessEntity');

        return $file->yearRecord?->businessEntity !== null && $user->can('update', $file->yearRecord->businessEntity);
    }

    public function delete(User $user, ComplianceDocumentFile $file): bool
    {
        $file->loadMissing('yearRecord.businessEntity');

        return $file->yearRecord?->businessEntity !== null && $user->can('update', $file->yearRecord->businessEntity);
    }
}
