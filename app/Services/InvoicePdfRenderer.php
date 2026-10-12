<?php

namespace App\Services;

use App\Models\BusinessEntity;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class InvoicePdfRenderer
{
    public function binary(BusinessEntity $businessEntity, Invoice $invoice): string
    {
        $invoice->loadMissing(['lines.chartOfAccount', 'lease.tenant', 'asset', 'businessEntity']);

        return Pdf::loadView('invoices.print', [
            'businessEntity' => $businessEntity,
            'invoice' => $invoice,
            'forPdf' => true,
        ])->output();
    }

    public function filename(Invoice $invoice): string
    {
        $safe = Str::of((string) $invoice->invoice_number)
            ->replaceMatches('/[^\w.\-]+/', '-')
            ->trim('-')
            ->toString();

        if ($safe === '') {
            $safe = 'invoice-'.$invoice->id;
        }

        return $safe.'.pdf';
    }
}
