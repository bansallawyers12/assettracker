<?php

namespace App\Http\Controllers\Concerns;

use App\Models\BusinessEntity;

trait EnsuresOperationalBusinessEntity
{
    /**
     * Block accounting, invoices, rent tools, and tracking categories for tenancy/property-manager contacts.
     */
    protected function ensureOperationalForAccounting(BusinessEntity $businessEntity): void
    {
        abort_if(
            $businessEntity->isTenancyContactOnly(),
            403,
            'This action is not available for tenancy or property-manager contacts. Edit the company profile if this should be one of your operating entities.'
        );
    }
}
