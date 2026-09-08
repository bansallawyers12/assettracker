<?php

namespace App\Http\Controllers\Concerns;

use App\Models\BusinessEntity;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

trait EnsuresOperationalBusinessEntity
{
    /**
     * Block accounting, invoices, rent tools, and tracking categories for tenancy/property-manager contacts.
     */
    protected function ensureOperationalForAccounting(BusinessEntity $businessEntity): void
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
