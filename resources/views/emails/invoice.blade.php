<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine ?: ('Invoice '.$invoice->invoice_number) }}</title>
</head>
<body style="margin:0; padding:16px; font-family:Arial, Helvetica, sans-serif; font-size:14px; line-height:1.5; color:#111111;">
    @foreach ($paragraphs as $paragraph)
        <p style="margin:0 0 16px;">{!! nl2br(e(trim($paragraph))) !!}</p>
    @endforeach
</body>
</html>
