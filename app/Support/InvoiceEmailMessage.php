<?php

namespace App\Support;

use App\Models\BusinessEntity;
use App\Models\Invoice;

class InvoiceEmailMessage
{
    public static function body(Invoice $invoice, BusinessEntity $businessEntity): string
    {
        $customer = trim((string) $invoice->customer_name);
        $greeting = $customer !== '' ? 'Hello '.$customer.',' : 'Hello,';
        $amount = number_format((float) $invoice->total_amount, 2);
        $currency = trim((string) ($invoice->currency ?: 'AUD'));
        $lines = [
            $greeting,
            '',
            'Please find invoice '.$invoice->invoice_number.' attached.',
            '',
            'Amount: $'.$amount.($currency !== '' ? ' '.$currency : ''),
        ];

        if ($invoice->due_date) {
            $lines[] = 'Due: '.$invoice->due_date->format('d/m/Y');
        }

        $lines[] = '';
        $lines[] = 'Kind regards,';
        $lines[] = $businessEntity->legal_name;

        return implode("\n", $lines);
    }
}
