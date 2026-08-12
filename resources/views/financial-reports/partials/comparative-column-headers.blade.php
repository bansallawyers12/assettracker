@props([
    'currentLabel',
    'priorLabel',
    'changeLabel' => '$ Change',
])

<tr class="border-b border-gray-200 bg-gray-50 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
    <th class="px-6 py-2 text-left font-semibold normal-case tracking-normal text-gray-600">Account</th>
    <th class="px-4 py-2 text-right tabular-nums font-semibold normal-case tracking-normal">{{ $currentLabel }}</th>
    <th class="px-4 py-2 text-right tabular-nums font-semibold normal-case tracking-normal">{{ $priorLabel }}</th>
    <th class="px-6 py-2 text-right tabular-nums font-semibold normal-case tracking-normal">{{ $changeLabel }}</th>
</tr>
