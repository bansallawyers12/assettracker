<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        :root {
            --ink: #111;
            --muted: #666;
            --line: #ddd;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
            background: #f3f4f6;
            line-height: 1.4;
            font-size: 14px;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1.25rem;
            background: #f7f7f7;
            border-bottom: 1px solid #ddd;
        }
        .toolbar p {
            margin: 0;
            font-size: 13px;
            color: #666;
        }
        .toolbar-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.45rem 0.85rem;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            background: #111;
            color: #fff;
        }
        .btn-secondary {
            background: #fff;
            color: #111;
            border-color: #ccc;
        }
        .page {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1.5rem 3rem;
        }
        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            padding: 2rem 2.25rem;
        }
        body.for-pdf { background: #fff; }
        body.for-pdf .page { max-width: none; padding: 0; }
        body.for-pdf .card {
            border: none;
            border-radius: 0;
            box-shadow: none;
            padding: 0;
        }
        table.layout {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 1.75rem;
        }
        table.layout td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        table.header td {
            padding-bottom: 1rem;
            border-bottom: 1px solid #111;
        }
        .brand h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }
        .brand p {
            margin: 0.35rem 0 0;
            color: #666;
            font-size: 13px;
            max-width: 28rem;
        }
        .meta {
            text-align: right;
        }
        .meta .title {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #666;
        }
        .meta .number {
            margin: 0.25rem 0 0;
            font-size: 16px;
            font-weight: 700;
        }
        .meta .status {
            display: block;
            margin-top: 0.35rem;
            font-size: 12px;
            color: #666;
        }
        table.parties td.bill-to { width: 50%; padding-right: 1.5rem; }
        table.parties td.facts-cell { width: 50%; }
        .label {
            display: block;
            margin-bottom: 0.3rem;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #666;
        }
        .name {
            font-size: 15px;
            font-weight: 700;
        }
        .line {
            margin-top: 0.2rem;
            font-size: 13px;
            color: #666;
        }
        table.facts { width: 100%; margin: 0; }
        table.facts td {
            border: none;
            padding: 0 0 0.45rem;
            font-size: 13px;
            vertical-align: top;
        }
        table.facts td.fact-label {
            width: 5.5rem;
            font-weight: 600;
            color: #666;
        }
        table.lines {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        table.lines th {
            text-align: left;
            padding: 0.5rem 0.4rem;
            border-bottom: 1px solid #111;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #666;
        }
        table.lines td {
            padding: 0.65rem 0.4rem;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
            font-size: 13px;
        }
        table.lines th.num,
        table.lines td.num,
        table.totals td.num { text-align: right; }
        table.totals {
            width: 220px;
            margin-left: auto;
            margin-bottom: 0;
            border-collapse: collapse;
        }
        table.totals td {
            border: none;
            padding: 0.25rem 0;
            font-size: 13px;
            color: #666;
        }
        table.totals td.num {
            text-align: right;
            color: #111;
        }
        table.totals tr.total td {
            padding-top: 0.5rem;
            border-top: 1px solid #111;
            color: #111;
            font-size: 15px;
            font-weight: 700;
        }
        .notes {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #ddd;
            font-size: 13px;
        }
        .notes .label { margin-bottom: 0.35rem; }
        .notes p {
            margin: 0;
            white-space: pre-wrap;
        }
        .footer {
            margin-top: 2rem;
            font-size: 11px;
            color: #666;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .page { max-width: none; padding: 0; }
            .card {
                border: none;
                border-radius: 0;
                box-shadow: none;
                padding: 0;
            }
        }
        @media (max-width: 640px) {
            .card { padding: 1.25rem; }
        }
    </style>
</head>
<body @class(['for-pdf' => (bool) ($forPdf ?? false)])>
    @unless ($forPdf ?? false)
    <div class="toolbar no-print">
        <p>Print this page and choose <strong>Save as PDF</strong> to download a PDF copy.</p>
        <div class="toolbar-actions">
            <button type="button" class="btn" onclick="window.print()">Print / Save as PDF</button>
            <a class="btn btn-secondary" href="{{ route('business-entities.invoices.show', [$businessEntity, $invoice]) }}">Back to invoice</a>
        </div>
    </div>
    @endunless

    <div class="page">
        <article class="card">
        <table class="layout header">
            <tr>
                <td class="brand" width="58%">
                    <h1>{{ $businessEntity->legal_name }}</h1>
                    @if ($businessEntity->registered_address)
                        <p>{{ $businessEntity->registered_address }}</p>
                    @endif
                    @if ($businessEntity->abn)
                        <p>ABN {{ \App\Models\BusinessEntity::formatAbn($businessEntity->abn) }}</p>
                    @endif
                    @if ($businessEntity->registered_email || $businessEntity->phone_number)
                        <p>
                            @if ($businessEntity->registered_email){{ $businessEntity->registered_email }}@endif
                            @if ($businessEntity->registered_email && $businessEntity->phone_number) · @endif
                            @if ($businessEntity->phone_number){{ $businessEntity->phone_number }}@endif
                        </p>
                    @endif
                </td>
                <td class="meta" width="42%" align="right">
                    <p class="title">Tax invoice</p>
                    <p class="number">{{ $invoice->invoice_number }}</p>
                    <span class="status">{{ \App\Models\Invoice::$statuses[$invoice->status] ?? ucfirst($invoice->status) }}</span>
                </td>
            </tr>
        </table>

        <table class="layout parties">
            <tr>
                <td class="bill-to">
                    <span class="label">Bill to</span>
                    <div class="name">{{ $invoice->customer_name ?: '—' }}</div>
                    @include('invoices.partials.customer-abn', ['invoice' => $invoice, 'variant' => 'print'])
                    @if ($invoice->lease?->tenant?->email)
                        <div class="line">{{ $invoice->lease->tenant->email }}</div>
                    @endif
                    @if ($invoice->asset)
                        <div class="line">{{ $invoice->asset->name }}</div>
                    @endif
                </td>
                <td class="facts-cell">
                    <table class="facts">
                        <tr>
                            <td class="fact-label">Issued</td>
                            <td>{{ $invoice->issue_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="fact-label">Due</td>
                            <td>{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '—' }}</td>
                        </tr>
                        @if ($invoice->reference)
                            <tr>
                                <td class="fact-label">Reference</td>
                                <td>{{ $invoice->reference }}</td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        <table class="lines">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Account</th>
                    <th class="num">Qty</th>
                    <th class="num">Price</th>
                    <th class="num">GST</th>
                    <th class="num">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoice->lines as $line)
                    <tr>
                        <td>{{ $line->description }}</td>
                        <td>@include('invoices.partials.line-account-label', ['line' => $line])</td>
                        <td class="num">{{ rtrim(rtrim(number_format((float) $line->quantity, 4), '0'), '.') }}</td>
                        <td class="num">{{ number_format((float) $line->unit_price, 2) }}</td>
                        <td class="num">@if ((float) $line->gst_rate > 0){{ number_format((float) $line->gst_rate * 100, 0) }}%@elseif ($invoice->gst_basis === 'manual')GST Free@else 0%@endif</td>
                        <td class="num">{{ number_format((float) $line->line_total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="color: #666;">No line items.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="totals" align="right">
            <tr>
                <td>Subtotal</td>
                <td class="num">{{ number_format((float) $invoice->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>GST</td>
                <td class="num">{{ number_format((float) $invoice->gst_amount, 2) }}</td>
            </tr>
            <tr class="total">
                <td>Total</td>
                <td class="num">{{ number_format((float) $invoice->total_amount, 2) }}</td>
            </tr>
        </table>

        @if ($invoice->notes)
            <div class="notes">
                <span class="label">Notes</span>
                <p>{{ $invoice->notes }}</p>
            </div>
        @endif

        <footer class="footer">
            {{ config('app.name', 'Asset Tracker') }} · {{ now()->format('d/m/Y') }}
        </footer>
        </article>
    </div>
</body>
</html>
