<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
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
    public function view(User $user, Asset $asset)
    {
        return true;
    }

    /**
     * Determine whether the user can create assets.
     */
    public function create(User $user)
    {
        // Allow all authenticated users to create assets
        return true;
    }

    /**
     * Determine whether the user can update the asset.
     */
    public function update(User $user, Asset $asset)
    {
        return true;
    }

    /**
     * Determine whether the user can delete the asset.
     */
    public function delete(User $user, Asset $asset)
    {
        return true;
    }
} 