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
            color: var(--ink);
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
            border-bottom: 1px solid var(--line);
        }
        .toolbar p {
            margin: 0;
            font-size: 13px;
            color: var(--muted);
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
        .top {
            display: flex;
            justify-content: space-between;
            gap: 1.5rem;
            align-items: flex-start;
            margin-bottom: 1.75rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--ink);
        }
        .brand h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }
        .brand p {
            margin: 0.35rem 0 0;
            color: var(--muted);
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
            color: var(--muted);
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
            color: var(--muted);
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.75rem;
        }
        .label {
            display: block;
            margin-bottom: 0.3rem;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .name {
            font-size: 15px;
            font-weight: 700;
        }
        .line {
            margin-top: 0.2rem;
            font-size: 13px;
            color: var(--muted);
        }
        .facts p {
            margin: 0 0 0.45rem;
            font-size: 13px;
        }
        .facts strong {
            display: inline-block;
            min-width: 5.5rem;
            font-weight: 600;
            color: var(--muted);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        th {
            text-align: left;
            padding: 0.5rem 0.4rem;
            border-bottom: 1px solid var(--ink);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--muted);
        }
        td {
            padding: 0.65rem 0.4rem;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
            font-size: 13px;
        }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .totals {
            width: 220px;
            margin-left: auto;
            font-size: 13px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 0.25rem 0;
            color: var(--muted);
        }
        .totals-row span:last-child {
            color: var(--ink);
            font-variant-numeric: tabular-nums;
        }
        .totals-row.total {
            margin-top: 0.35rem;
            padding-top: 0.5rem;
            border-top: 1px solid var(--ink);
            color: var(--ink);
            font-size: 15px;
            font-weight: 700;
        }
        .totals-row.total span { font-weight: 700; color: var(--ink); }
        .notes {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--line);
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
            color: var(--muted);
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
            .top, .grid { display: block; }
            .meta { text-align: left; margin-top: 1rem; }
            .facts { margin-top: 1rem; }
            .card { padding: 1.25rem; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <p>Print this page and choose <strong>Save as PDF</strong> to download a PDF copy.</p>
        <div class="toolbar-actions">
            <button type="button" class="btn" onclick="window.print()">Print / Save as PDF</button>
            <a class="btn btn-secondary" href="{{ route('business-entities.invoices.show', [$businessEntity, $invoice]) }}">Back to invoice</a>
        </div>
    </div>

    <div class="page">
        <article class="card">
        <header class="top">
            <div class="brand">
                <h1>{{ $businessEntity->legal_name }}</h1>
                @if ($businessEntity->registered_address)
                    <p>{{ $businessEntity->registered_address }}</p>
                @endif
                @if ($businessEntity->registered_email || $businessEntity->phone_number)
                    <p>
                        @if ($businessEntity->registered_email){{ $businessEntity->registered_email }}@endif
                        @if ($businessEntity->registered_email && $businessEntity->phone_number) · @endif
                        @if ($businessEntity->phone_number){{ $businessEntity->phone_number }}@endif
                    </p>
                @endif
            </div>
            <div class="meta">
                <p class="title">Tax invoice</p>
                <p class="number">{{ $invoice->invoice_number }}</p>
                <span class="status">{{ \App\Models\Invoice::$statuses[$invoice->status] ?? ucfirst($invoice->status) }}</span>
            </div>
        </header>

        <section class="grid">
            <div>
                <span class="label">Bill to</span>
                <div class="name">{{ $invoice->customer_name ?: '—' }}</div>
                @if ($invoice->lease?->tenant?->email)
                    <div class="line">{{ $invoice->lease->tenant->email }}</div>
                @endif
                @if ($invoice->asset)
                    <div class="line">{{ $invoice->asset->name }}</div>
                @endif
            </div>
            <div class="facts">
                <p><strong>Issued</strong> {{ $invoice->issue_date->format('d/m/Y') }}</p>
                <p><strong>Due</strong> {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '—' }}</p>
                @if ($invoice->reference)
                    <p><strong>Reference</strong> {{ $invoice->reference }}</p>
                @endif
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
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
                        <td class="num">{{ rtrim(rtrim(number_format((float) $line->quantity, 4), '0'), '.') }}</td>
                        <td class="num">{{ number_format((float) $line->unit_price, 2) }}</td>
                        <td class="num">{{ number_format((float) $line->gst_rate * 100, 0) }}%</td>
                        <td class="num">{{ number_format((float) $line->line_total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="color: var(--muted);">No line items.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row"><span>Subtotal</span><span>{{ number_format((float) $invoice->subtotal, 2) }}</span></div>
            <div class="totals-row"><span>GST</span><span>{{ number_format((float) $invoice->gst_amount, 2) }}</span></div>
            <div class="totals-row total"><span>Total</span><span>{{ number_format((float) $invoice->total_amount, 2) }}</span></div>
        </div>

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
