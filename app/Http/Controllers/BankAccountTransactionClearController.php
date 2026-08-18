<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresOperationalBusinessEntity;
use App\Models\BankAccount;
use App\Models\BusinessEntity;
use App\Services\BankAccountTransactionClearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BankAccountTransactionClearController extends Controller
{
    use EnsuresOperationalBusinessEntity;

    public function __construct(private BankAccountTransactionClearService $clearService) {}

    public function create(BusinessEntity $businessEntity, BankAccount $bankAccount): View
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);
        $this->ensureBankAccountCanBeClearedForEntity($bankAccount, $businessEntity);

        $preview = $this->clearService->preview($businessEntity, $bankAccount);
        $confirmationPhrase = $this->confirmationPhrase($bankAccount);

        return view('business-entities.bank-accounts.transactions.clear', compact(
            'businessEntity',
            'bankAccount',
            'preview',
            'confirmationPhrase',
        ));
    }

    public function destroy(Request $request, BusinessEntity $businessEntity, BankAccount $bankAccount): RedirectResponse
    {
        $this->authorize('update', $businessEntity);
        $this->ensureOperationalForAccounting($businessEntity);
        $this->ensureBankAccountCanBeClearedForEntity($bankAccount, $businessEntity);

        $validated = $request->validate([
            'confirmation' => 'required|string',
        ]);

        $expected = $this->confirmationPhrase($bankAccount);
        if (trim((string) $validated['confirmation']) !== $expected) {
            throw ValidationException::withMessages([
                'confirmation' => 'Type the bank account name exactly to confirm.',
            ]);
        }

        $preview = $this->clearService->preview($businessEntity, $bankAccount);
        if ($preview['transactions'] === 0) {
            return redirect()
                ->route('business-entities.bank-accounts.transactions.clear.create', [$businessEntity, $bankAccount])
                ->with('error', 'There are no bank transactions to clear for this account.');
        }

        $result = $this->clearService->clear($businessEntity, $bankAccount);

        $message = sprintf(
            'Cleared %d transaction(s) from %s',
            $result['transactions_deleted'],
            $bankAccount->entityWorkspaceLabel($businessEntity)
        );

        if ($result['invoices_reset'] > 0) {
            $message .= sprintf(' and reset %d invoice payment link(s)', $result['invoices_reset']);
        }

        $message .= '. Matched statement lines are now unmatched.';

        return redirect()
            ->route('business-entities.show', [
                'business_entity' => $businessEntity->id,
                'open_bank_transactions' => $bankAccount->id,
            ])
            ->withFragment('tab_bank_accounts')
            ->with('success', $message);
    }

    private function confirmationPhrase(BankAccount $bankAccount): string
    {
        $name = trim((string) $bankAccount->account_name);

        return $name !== '' ? $name : trim((string) $bankAccount->transactionAccountLabel());
    }

    private function ensureBankAccountCanBeClearedForEntity(BankAccount $bankAccount, BusinessEntity $businessEntity): void
    {
        abort_unless(
            $bankAccount->isAccessibleByCurrentUser() && $bankAccount->canUseForTransaction($businessEntity),
            404
        );
    }
}
