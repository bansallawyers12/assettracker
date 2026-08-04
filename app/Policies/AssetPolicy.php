<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }
    public function view(User $user, Asset $asset): bool
    {
        $asset->loadMissing('businessEntity');

        return $asset->businessEntity !== null && $user->can('view', $asset->businessEntity);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Asset $asset): bool
    {
        $asset->loadMissing('businessEntity');

        return $asset->businessEntity !== null && $user->can('update', $asset->businessEntity);
    }

    public function delete(User $user, Asset $asset): bool
    {
        $asset->loadMissing('businessEntity');

        return $asset->businessEntity !== null && $user->can('update', $asset->businessEntity);
    }
}
