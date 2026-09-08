<?php

namespace App\Support;

use App\Models\Invoice;
use App\Services\BankStatementMatchSuggester;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InvoicePaymentAllocator
{
    /**
     * Waterfill a credit across selected invoices ordered by oldest due → issue → id.
     *
     * @param  Collection<int, Invoice>|list<Invoice>  $invoices
     * @param  array<int, float>|null  $remainingByInvoiceId
     * @return array{
     *     allocations: list<array{invoice_id: int, amount: float}>,
     *     leftover: float
     * }
     */
    public function propose(float $credit, Collection|array $invoices, ?array $remainingByInvoiceId = null): array
    {
        $credit = round(max(0, $credit), 2);
        $ordered = $this->orderForWaterfill(collect($invoices));

        $allocations = [];
        $remainingCredit = $credit;

        foreach ($ordered as $invoice) {
            if ($remainingCredit <= BankStatementMatchSuggester::AMOUNT_TOLERANCE) {
                break;
            }

            $invoiceId = (int) $invoice->id;
            $remaining = array_key_exists($invoiceId, $remainingByInvoiceId ?? [])
                ? round((float) $remainingByInvoiceId[$invoiceId], 2)
                : $invoice->amountDue();

            if ($remaining <= BankStatementMatchSuggester::AMOUNT_TOLERANCE) {
                continue;
            }

            $apply = round(min($remaining, $remainingCredit), 2);
            if ($apply <= 0) {
                continue;
            }

            $allocations[] = [
                'invoice_id' => $invoiceId,
                'amount' => $apply,
            ];
            $remainingCredit = round($remainingCredit - $apply, 2);
        }

        return [
            'allocations' => $allocations,
            'leftover' => max(0, $remainingCredit),
        ];
    }

    /**
     * Stable pool key: lease when present, otherwise customer name + entity.
     */
    public function poolKey(Invoice $invoice): string
    {
        if ($invoice->lease_id !== null) {
            return 'lease:'.(int) $invoice->lease_id;
        }

        $name = Str::lower(trim((string) $invoice->customer_name));

        return 'name:'.$name.'|entity:'.(int) $invoice->business_entity_id;
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return Collection<int, Invoice>
     */
    public function filterToPool(Collection $invoices, Invoice $anchor): Collection
    {
        $key = $this->poolKey($anchor);

        return $invoices
            ->filter(fn (Invoice $invoice) => $this->poolKey($invoice) === $key)
            ->values();
    }

    /**
     * True when every invoice shares one pool key.
     *
     * @param  Collection<int, Invoice>|list<Invoice>  $invoices
     */
    public function invoicesSharePool(Collection|array $invoices): bool
    {
        $collection = collect($invoices);
        if ($collection->isEmpty()) {
            return false;
        }

        $firstKey = $this->poolKey($collection->first());

        return $collection->every(fn (Invoice $invoice) => $this->poolKey($invoice) === $firstKey);
    }

    /**
     * True when any two invoices share the same remaining (within tolerance).
     *
     * @param  Collection<int, Invoice>|list<Invoice>  $invoices
     * @param  array<int, float>|null  $remainingByInvoiceId
     */
    public function hasAmbiguousRemainings(Collection|array $invoices, ?array $remainingByInvoiceId = null): bool
    {
        $seen = [];

        foreach (collect($invoices) as $invoice) {
            $invoiceId = (int) $invoice->id;
            $remaining = array_key_exists($invoiceId, $remainingByInvoiceId ?? [])
                ? round((float) $remainingByInvoiceId[$invoiceId], 2)
                : $invoice->amountDue();

            if ($remaining <= BankStatementMatchSuggester::AMOUNT_TOLERANCE) {
                continue;
            }

            $bucket = number_format($remaining, 2, '.', '');
            if (isset($seen[$bucket])) {
                return true;
            }
            $seen[$bucket] = true;
        }

        return false;
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return Collection<int, Invoice>
     */
    public function orderForWaterfill(Collection $invoices): Collection
    {
        return $invoices
            ->sort(function (Invoice $a, Invoice $b): int {
                $dueA = $this->dateSortKey($a->due_date);
                $dueB = $this->dateSortKey($b->due_date);
                if ($dueA !== $dueB) {
                    return $dueA <=> $dueB;
                }

                $issueA = $this->dateSortKey($a->issue_date);
                $issueB = $this->dateSortKey($b->issue_date);
                if ($issueA !== $issueB) {
                    return $issueA <=> $issueB;
                }

                return (int) $a->id <=> (int) $b->id;
            })
            ->values();
    }

    private function dateSortKey(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Throwable) {
                return '9999-12-31';
            }
        }

        return '9999-12-31';
    }
}
