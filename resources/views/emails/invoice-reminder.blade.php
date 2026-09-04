<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #111;">
    <p>Dear {{ $customerName }},</p>
    <p>This is a friendly reminder regarding invoice <strong>{{ $invoice->invoice_number }}</strong> for
        <strong>${{ number_format($invoice->total_amount, 2) }}</strong> {{ $invoice->currency }}.</p>
    <p>
        @if($invoice->due_date)
            Due date: <strong>{{ $invoice->due_date->format('d/m/Y') }}</strong>.
        @else
            Please arrange payment at your earliest convenience.
        @endif
    </p>
    @if($invoice->reference)
        <p>Reference: {{ $invoice->reference }}</p>
    @endif
    @if($invoice->lines->isNotEmpty())
        <p style="margin-top: 1.25rem; margin-bottom: 0.5rem;"><strong>Line items</strong></p>
        <ul style="padding-left: 1.25rem; margin-top: 0;">
            @foreach ($invoice->lines as $line)
                <li>{{ $line->description }} — ${{ number_format((float) $line->line_total, 2) }}</li>
            @endforeach
        </ul>
    @endif
    <p>Subtotal (ex GST): ${{ number_format((float) $invoice->subtotal, 2) }}<br>
        GST: ${{ number_format((float) $invoice->gst_amount, 2) }}<br>
        <strong>Total: ${{ number_format((float) $invoice->total_amount, 2) }} {{ $invoice->currency }}</strong>
    </p>
    <p>Thank you.</p>
</body>
</html>
