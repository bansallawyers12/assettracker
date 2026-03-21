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
    <p>Thank you.</p>
</body>
</html>
