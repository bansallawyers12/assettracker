@if(($report['formation_date_warning']['count'] ?? 0) > 0)
    <div class="mx-6 mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p class="font-medium">
            {{ $report['formation_date_warning']['count'] }}
            {{ $report['formation_date_warning']['count'] === 1 ? 'entity is' : 'entities are' }}
            missing a registration or establishment date.
        </p>
        <p class="mt-1 text-amber-800">
            Financial years before an entity existed are excluded from this report.
            Until a registration or establishment date is set, the entity record created date is used as a fallback.
            Set the date on each entity profile for accurate compliance history.
        </p>
    </div>
@endif
