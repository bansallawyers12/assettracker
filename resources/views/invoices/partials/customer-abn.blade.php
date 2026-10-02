@php
    $customerAbn = $invoice->lease?->tenant?->abn;
@endphp
@if ($customerAbn)
    <p @class([
        'mt-0.5 text-sm text-gray-600 dark:text-gray-300 font-mono' => ($variant ?? 'show') === 'show',
        'line' => ($variant ?? 'show') === 'print',
    ])>
        ABN {{ \App\Models\BusinessEntity::formatAbn($customerAbn) }}
    </p>
@endif
