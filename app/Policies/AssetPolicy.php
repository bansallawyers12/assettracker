<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    private function canAccessViaEntity(User $user, Asset $asset): bool
    {
        $entity = $asset->businessEntity;
        if (! $entity) {
            return false;
        }
        if ($user->isPrimaryAdministrator()) {
            return true;
        }

        return (int) $entity->user_id === (int) $user->id;
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
        return $this->canAccessViaEntity($user, $asset);
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
        return $this->canAccessViaEntity($user, $asset);
    }

    /**
     * Determine whether the user can delete the asset.
     */
    public function delete(User $user, Asset $asset): bool
    {
        return $this->canAccessViaEntity($user, $asset);
    }
}
