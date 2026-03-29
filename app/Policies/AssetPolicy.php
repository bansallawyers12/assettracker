<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    private function canAccessViaEntity(Asset $asset): bool
    {
        return $asset->businessEntity !== null;
    }

    /**
     * Determine whether the user can list assets globally (e.g. /assets).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the asset.
     */
    public function view(User $user, Asset $asset): bool
    {
        return $this->canAccessViaEntity($asset);
    }

    /**
     * Determine whether the user can create assets.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the asset.
     */
    public function update(User $user, Asset $asset): bool
    {
        return $this->canAccessViaEntity($asset);
    }

    /**
     * Determine whether the user can delete the asset.
     */
    public function delete(User $user, Asset $asset): bool
    {
        return $this->canAccessViaEntity($asset);
    }
}
