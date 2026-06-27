@php
    $dash = $dash ?? '—';
    $paidBy = $line['paid_by'] ?? $dash;
    $receivedBy = $line['received_by'] ?? $dash;
    $bankAccount = $line['bank_account'] ?? $dash;
    $transactionType = $line['transaction_type'] ?? $dash;
    $assetName = $line['asset_name'] ?? $dash;
@endphp
<td class="px-4 py-2 text-gray-600 text-xs min-w-28" title="{{ $paidBy !== $dash ? $paidBy : '' }}">
    {{ $paidBy }}
</td>
<td class="px-4 py-2 text-gray-600 text-xs min-w-28" title="{{ $receivedBy !== $dash ? $receivedBy : '' }}">
    {{ $receivedBy }}
</td>
<td class="px-4 py-2 text-gray-600 text-xs min-w-32" title="{{ $bankAccount !== $dash ? $bankAccount : '' }}">
    {{ $bankAccount }}
</td>
<td class="px-4 py-2 text-gray-600 text-xs min-w-28" title="{{ $transactionType !== $dash ? $transactionType : '' }}">
    {{ $transactionType }}
</td>
<td class="px-4 py-2 text-gray-600 text-xs min-w-24" title="{{ $assetName !== $dash ? $assetName : '' }}">
    {{ $assetName }}
</td>
