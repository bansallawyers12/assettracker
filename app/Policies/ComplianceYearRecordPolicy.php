<?php

namespace App\Policies;

use App\Models\ComplianceYearRecord;
use App\Models\User;

class ComplianceYearRecordPolicy
{
    public function view(User $user, ComplianceYearRecord $record): bool
    {
        return $record->businessEntity !== null;
    }

    public function update(User $user, ComplianceYearRecord $record): bool
    {
        return $record->businessEntity !== null;
    }
}
