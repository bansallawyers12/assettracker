<?php

namespace App\Http\Controllers\Concerns;

use App\Models\BusinessEntity;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

trait EnsuresOperationalBusinessEntity
{
    /**
     * Block accounting mutations (bank links, new invoices, etc.) for closed, inactive, or tenancy-only entities.
     */
    protected function ensureOperationalForAccounting(BusinessEntity $businessEntity): void
    {
        $this->ensureAccountingMutationsAllowed($businessEntity);
    }

    /**
     * Allow viewing invoice lists and posted records; block only tenancy/property-manager contacts.
     */
    protected function ensureAccountingReadable(BusinessEntity $businessEntity): void
    {
        if (! $businessEntity->isTenancyContactOnly()) {
            return;
        }

        $this->abortOperationalRestriction(
            403,
            'This action is not available for tenancy or property-manager contacts. Edit the company profile if this should be one of your operating entities.'
        );
    }

    /**
     * Block creating or changing accounting records for closed, inactive, or tenancy-only entities.
     */
    protected function ensureAccountingMutationsAllowed(BusinessEntity $businessEntity): void
    {
        $this->ensureNotClosed($businessEntity);
        $this->ensureNotInactive($businessEntity);

        if (! $businessEntity->isTenancyContactOnly()) {
            return;
        }

        $this->abortOperationalRestriction(
            403,
            'This action is not available for tenancy or property-manager contacts. Edit the company profile if this should be one of your operating entities.'
        );
    }

    /**
     * Ensure entity is open (not closed) for mutation operations.
     */
    protected function ensureNotClosed(BusinessEntity $businessEntity): void
    {
        if (! $businessEntity->isClosed()) {
            return;
        }

        $this->abortOperationalRestriction(
            403,
            'This entity is closed, so bank links, transactions, assets, and other changes are blocked. Reopen it from Edit company profile by setting Status to Active.'
        );
    }

    protected function ensureNotInactive(BusinessEntity $businessEntity): void
    {
        if (! $businessEntity->isInactive()) {
            return;
        }

        $this->abortOperationalRestriction(
            403,
            'This company is inactive. Mark it active again from Edit company profile before creating or changing invoices and other accounting records.'
        );
    }

    protected function abortOperationalRestriction(int $status, string $message): void
    {
        /** @var Request|null $request */
        $request = request();

        if ($request instanceof Request && $request->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'status' => false,
                'message' => $message,
            ], $status));
        }

        abort($status, $message);
    }
}
