@php
    /** @var \App\Models\InvoiceLine $line */
    $code = trim((string) ($line->account_code ?? ''));
    $accountLabel = $code === '' ? '—' : $code;
    if ($code !== '' && filled($line->chartOfAccount?->account_name)) {
        $accountLabel = $code.' — '.$line->chartOfAccount->account_name;
    }
@endphp
{{ $accountLabel }}
