<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresOperationalBusinessEntity;
use App\Models\BusinessEntity;
use App\Services\EntityTransactionClearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EntityTransactionClearController extends Controller
{
    use EnsuresOperationalBusinessEntity;

    public function __construct(private EntityTransactionClearService $clearService) {}

    public function create(BusinessEntity $businessEntity): View
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);

        $preview = $this->clearService->preview($businessEntity);

        return view('business-entities.transactions.clear', compact('businessEntity', 'preview'));
    }

    public function destroy(Request $request, BusinessEntity $businessEntity): RedirectResponse
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);

        $validated = $request->validate([
            'confirmation' => 'required|string',
            'include_manual_journals' => 'sometimes|boolean',
        ]);

        $expected = trim((string) $businessEntity->legal_name);
        if (trim((string) $validated['confirmation']) !== $expected) {
            throw ValidationException::withMessages([
                'confirmation' => 'Type the entity legal name exactly to confirm.',
            ]);
        }

        $preview = $this->clearService->preview($businessEntity);
        if ($preview['transactions'] === 0 && (! $request->boolean('include_manual_journals') || $preview['manual_journals'] === 0)) {
            return redirect()
                ->route('business-entities.transactions.clear.create', $businessEntity)
                ->with('error', 'There is nothing to clear for this entity.');
        }

        $result = $this->clearService->clear(
            $businessEntity,
            $request->boolean('include_manual_journals')
        );

        $message = sprintf(
            'Cleared %d transaction(s)',
            $result['transactions_deleted']
        );

        if ($result['manual_journals_deleted'] > 0) {
            $message .= sprintf(', %d manual journal(s)', $result['manual_journals_deleted']);
        }

        if ($result['invoices_reset'] > 0) {
            $message .= sprintf(', and reset %d invoice payment link(s)', $result['invoices_reset']);
        }

        $message .= '. Bank statement lines are unmatched again and can be re-imported.';

        return redirect()
            ->route('business-entities.show', $businessEntity->id)
            ->withFragment('tab_transactions')
            ->with('success', $message);
    }
}
