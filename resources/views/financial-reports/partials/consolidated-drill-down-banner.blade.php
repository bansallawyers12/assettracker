@props([
    'report',
    'entitySummaryUrl' => null,
    'reportType' => 'report',
])

@if(($report['is_consolidated'] ?? false) && ($report['entity_breakdown']['entities'] ?? collect())->isNotEmpty())
    <div class="px-6 py-3.5 text-xs leading-relaxed border-b border-amber-200 bg-amber-50 text-amber-950">
        <p class="font-semibold text-amber-900">
            Consolidated {{ $reportType }} — drill down before trusting totals
        </p>
        <ul class="mt-1.5 list-disc pl-4 space-y-0.5 text-amber-900/90">
            <li>Select <strong>one entity</strong> in Entity scope above for a single-entity {{ strtolower($reportType) }}.</li>
            <li>Click any <strong>account line</strong> to open account transactions for that period.</li>
            @if($entitySummaryUrl)
                <li>
                    Open
                    <a href="{{ $entitySummaryUrl }}" class="font-medium text-amber-800 underline hover:text-amber-950">
                        Entity summary
                    </a>
                    for sales, tax, profit, director loans, and cash by entity.
                </li>
            @endif
            <li>Use the <strong>by entity</strong> table below to spot which entities drive the consolidated figures.</li>
        </ul>
    </div>
@endif
