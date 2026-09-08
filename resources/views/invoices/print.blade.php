<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        :root {
            --ink: #111827;
            --muted: #6b7280;
            --line: #e5e7eb;
            --accent: #4f46e5;
            --surface: #f9fafb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            color: var(--ink);
            background: #eef2ff;
            line-height: 1.45;
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
            background: rgba(255,255,255,0.95);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(8px);
        }
        .toolbar p {
            margin: 0;
            font-size: 0.8125rem;
            color: var(--muted);
        }
        .toolbar-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 0.5rem;
            padding: 0.5rem 0.9rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: #4338ca; }
        .btn-secondary { background: #fff; color: var(--ink); border-color: #d1d5db; }
        .btn-secondary:hover { background: var(--surface); }
        .sheet-wrap { padding: 1.5rem 1rem 2.5rem; }
        .sheet {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            padding: 2rem 2.25rem;
        }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 1.5rem;
            align-items: flex-start;
            border-bottom: 2px solid var(--ink);
            padding-bottom: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .brand h1 {
            margin: 0;
            font-size: 1.35rem;
            letter-spacing: -0.02em;
        }
        .brand p { margin: 0.35rem 0 0; color: var(--muted); font-size: 0.875rem; }
        .meta { text-align: right; }
        .meta .number {
            font-size: 1.125rem;
            font-weight: 700;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }
        .badge {
            display: inline-block;
            margin-top: 0.4rem;
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: #e0e7ff;
            color: #3730a3;
        }
        .badge-draft { background: #f3f4f6; color: #374151; }
        .badge-approved { background: #dbeafe; color: #1e40af; }
        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-void { background: #fee2e2; color: #991b1b; }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .value { font-size: 0.95rem; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0.5rem 0 1.25rem;
            font-size: 0.875rem;
        }
        th {
            text-align: left;
            padding: 0.65rem 0.5rem;
            border-bottom: 2px solid var(--ink);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--muted);
        }
        td {
            padding: 0.7rem 0.5rem;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
        }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .totals {
            width: 260px;
            margin-left: auto;
            font-size: 0.9rem;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 0.35rem 0;
            color: var(--muted);
        }
        .totals-row.grand {
            margin-top: 0.35rem;
            padding-top: 0.65rem;
            border-top: 2px solid var(--ink);
            color: var(--ink);
            font-size: 1.1rem;
            font-weight: 700;
        }
        .notes {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--line);
            font-size: 0.875rem;
        }
        .notes .label { margin-bottom: 0.4rem; }
        .footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid var(--line);
            font-size: 0.75rem;
            color: var(--muted);
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .sheet-wrap { padding: 0; }
            .sheet {
                box-shadow: none;
                border: none;
                border-radius: 0;
                max-width: none;
                padding: 0;
            }
        }
        @media (max-width: 640px) {
            .grid { grid-template-columns: 1fr; }
            .header { flex-direction: column; }
            .meta { text-align: left; }
            .sheet { padding: 1.25rem; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <p>Print this page and choose <strong>Save as PDF</strong> to download a PDF copy.</p>
        <div class="toolbar-actions">
            <button type="button" class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
            <a class="btn btn-secondary" href="{{ route('business-entities.invoices.show', [$businessEntity, $invoice]) }}">Back to invoice</a>
        </div>
    </div>

    <div class="sheet-wrap">
        <article class="sheet">
            <header class="header">
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
                    <div class="number">{{ $invoice->invoice_number }}</div>
                    @php
                        $badgeClass = match ($invoice->status) {
                            'draft' => 'badge-draft',
                            'approved' => 'badge-approved',
                            'paid' => 'badge-paid',
                            'void' => 'badge-void',
                            default => 'badge-draft',
                        };
                    @endphp
                    <div class="badge {{ $badgeClass }}">{{ \App\Models\Invoice::$statuses[$invoice->status] ?? ucfirst($invoice->status) }}</div>
                </div>
            </header>

            <div class="grid">
                <div>
                    <div class="label">Bill to</div>
                    <div class="value"><strong>{{ $invoice->customer_name }}</strong></div>
                    @if ($invoice->lease?->tenant?->email)
                        <div class="value" style="color: var(--muted); margin-top: 0.25rem;">{{ $invoice->lease->tenant->email }}</div>
                    @endif
                    @if ($invoice->asset)
                        <div style="margin-top: 0.75rem;">
                            <div class="label">Property</div>
                            <div class="value">{{ $invoice->asset->name }}</div>
                        </div>
                    @endif
                </div>
                <div>
                    <div class="label">Issue date</div>
                    <div class="value">{{ $invoice->issue_date->format('d/m/Y') }}</div>
                    <div style="margin-top: 0.75rem;">
                        <div class="label">Due date</div>
                        <div class="value">{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '—' }}</div>
                    </div>
                    @if ($invoice->reference)
                        <div style="margin-top: 0.75rem;">
                            <div class="label">Reference</div>
                            <div class="value">{{ $invoice->reference }}</div>
                        </div>
                    @endif
                    <div style="margin-top: 0.75rem;">
                        <div class="label">Currency</div>
                        <div class="value">{{ $invoice->currency }}</div>
                    </div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="num">Qty</th>
                        <th class="num">Unit</th>
                        <th class="num">GST</th>
                        <th class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoice->lines as $line)
                        <tr>
                            <td>{{ $line->description }}</td>
                            <td class="num">{{ rtrim(rtrim(number_format((float) $line->quantity, 4), '0'), '.') }}</td>
                            <td class="num">${{ number_format((float) $line->unit_price, 2) }}</td>
                            <td class="num">{{ number_format((float) $line->gst_rate * 100, 0) }}%</td>
                            <td class="num">${{ number_format((float) $line->line_total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="color: var(--muted);">No line items.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="totals">
                <div class="totals-row"><span>Subtotal</span><span>${{ number_format((float) $invoice->subtotal, 2) }}</span></div>
                <div class="totals-row"><span>GST</span><span>${{ number_format((float) $invoice->gst_amount, 2) }}</span></div>
                <div class="totals-row grand"><span>Total</span><span>${{ number_format((float) $invoice->total_amount, 2) }}</span></div>
            </div>

            @if ($invoice->notes)
                <div class="notes">
                    <div class="label">Notes</div>
                    <div class="value" style="white-space: pre-wrap;">{{ $invoice->notes }}</div>
                </div>
            @endif

            <footer class="footer">
                Generated {{ now()->format('d/m/Y H:i') }} · {{ config('app.name', 'Asset Tracker') }}
            </footer>
        </article>
    </div>
</body>
</html>
