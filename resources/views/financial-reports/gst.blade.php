@php
    use App\Support\ReportEntityScopeLabel;
    use App\Support\ReportScopeQuery;

    $entity = $report['business_entity'];
    $entities = $report['business_entities'];
    $isConsolidated = $report['is_consolidated'] ?? false;
    $fyStart = \Carbon\Carbon::parse($report['financial_year']['start_date']);
    $fyEnd = \Carbon\Carbon::parse($report['financial_year']['end_date']);
    $fyLabel = $report['financial_year']['label'];
    $layout = $report['layout'];
    $subtitle = $fyStart->format('j M Y') . ' – ' . $fyEnd->format('j M Y');
    $explicitLayout = in_array(request()->query('layout'), ['quarterly', 'annual'], true);
    $entityScopeLabel = $isConsolidated
        ? ReportEntityScopeLabel::format(
            $report['forms_scope'] ?? 'all',
            $report['forms_entity_ids'] ?? [],
            $businessEntities
        )
        : null;
    $reportQuery = function (array $merge = []) use ($report, $explicitLayout) {
        $merge['fy'] = $report['financial_year']['start_date'];
        if ($report['include_unregistered']) {
            $merge['include_unregistered'] = 1;
        }
        if ($explicitLayout && ! array_key_exists('layout', $merge)) {
            $merge['layout'] = $report['layout'];
        }

        return ReportScopeQuery::build(
            $report['forms_scope'] ?? 'all',
            $report['forms_entity_ids'] ?? [],
            $merge
        );
    };
    $visiblePeriods = array_values(array_filter(
        $report['periods'],
        fn (array $period) => in_array($period['key'], $report['visible_period_keys'], true)
    ));
    $periodHeading = function (array $period) use ($layout, $fyLabel): string {
        if ($period['key'] === 'fy' && $layout === 'annual') {
            return 'FY ' . $fyLabel;
        }

        return $period['label'];
    };
    $transactionUrl = function (int $entityId, array $period, string $rowKey): string {
        $params = [
            'entity_id' => $entityId,
            'date_from' => $period['start'],
            'date_to' => $period['end'],
            'payment_status' => 'paid',
            'cash_dated' => 1,
        ];
        $direction = match ($rowKey) {
            'sales_ex_gst', 'gst_collected' => 'income',
            'purchases_ex_gst', 'gst_credits' => 'expense',
            default => null,
        };
        if ($direction !== null) {
            $params['direction'] = $direction;
        }

        return route('transactions.index', $params);
    };
    $showsUnregistered = collect($report['entities'])->contains(fn (array $row) => ! $row['gst_registered']);
@endphp

<x-report-shell
    title="GST worksheet"
    :subtitle="$subtitle"
    :entity="$entity"
    :entity-scope-label="$entityScopeLabel">

    <x-slot:filters>
        <form method="GET" action="{{ route('financial-reports.gst') }}"
              class="flex flex-wrap items-end gap-3">

            <x-report-entity-scope-picker
                :business-entities="$businessEntities"
                :report="$report"
            />

            <div class="flex flex-col gap-1">
                <label for="fy" class="text-xs font-medium text-gray-600">Financial year</label>
                <select name="fy" id="fy"
                        class="border border-gray-300 rounded-sm text-sm px-2 py-1.5 bg-white focus:ring-blue-500 focus:border-blue-500 min-w-[9rem]">
                    @foreach($report['available_years'] as $year)
                        <option value="{{ $year['start'] }}" @selected($year['start'] === $fyStart->toDateString())>
                            {{ $year['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium text-gray-600">Columns</span>
                <div class="inline-flex overflow-hidden rounded-sm border border-gray-300" data-gst-layout-toggle>
                    <a href="{{ route('financial-reports.gst', $reportQuery(['layout' => 'quarterly'])) }}"
                       data-gst-layout-option="quarterly"
                       @class([
                           'px-3 py-1.5 text-sm',
                           'bg-blue-600 text-white' => $layout === 'quarterly',
                           'bg-white text-gray-700 hover:bg-gray-50' => $layout !== 'quarterly',
                       ])>Quarterly</a>
                    <a href="{{ route('financial-reports.gst', $reportQuery(['layout' => 'annual'])) }}"
                       data-gst-layout-option="annual"
                       @class([
                           'border-l border-gray-300 px-3 py-1.5 text-sm',
                           'bg-blue-600 text-white' => $layout === 'annual',
                           'bg-white text-gray-700 hover:bg-gray-50' => $layout !== 'annual',
                       ])>Annual</a>
                </div>
            </div>

            @if($explicitLayout)
                <input type="hidden" name="layout" value="{{ $layout }}">
            @endif

            <div class="flex flex-col gap-1">
                <span class="text-xs font-medium text-gray-600">Entities</span>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="include_unregistered" value="1"
                           data-gst-include-unregistered
                           @checked($report['include_unregistered'])
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Include entities not registered for GST
                </label>
            </div>

            <div class="flex items-end gap-2 ml-auto">
                <a href="{{ route('financial-reports.gst', $reportQuery(['format' => 'csv'])) }}"
                   class="inline-flex items-center border border-gray-300 bg-white text-gray-700 text-sm font-medium rounded-sm px-3 py-1.5 hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                    Export CSV
                </a>
                <button type="submit"
                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-sm px-4 py-1.5 focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                    Update
                </button>
            </div>
        </form>
    </x-slot:filters>

    <div class="px-6 pt-4 pb-4 text-xs text-gray-600 leading-relaxed border-b border-gray-100" data-gst-layout="{{ $layout }}">
        <p data-gst-cash-basis-notice>
            Figures are cash basis: paid transactions, dated by the payment date, or the transaction date when the payment date is empty.
            A positive net is payable; a negative net is a refund.
            The quarter or year toggle changes the columns on this page. It does not change how an entity lodges.
        </p>
        @if($showsUnregistered)
            <p class="mt-1.5 text-amber-800" data-gst-unregistered-note>
                Entities that are not GST registered stay out of GST collected, GST credits, net GST, and the portfolio GST total.
                An em dash is not a nil BAS. GST still stored on their paid transactions is shown only as “GST recorded on transactions”.
            </p>
        @endif
        @if(count($report['entities']) > 1)
            <p class="mt-1.5" data-gst-portfolio-note>
                The portfolio GST total and Total BAS add GST-registered entities only.
            </p>
        @endif
    </div>

    @if(count($report['entities']) === 0)
        <div class="px-6 py-12 text-center" data-gst-empty>
            <p class="text-sm font-semibold text-gray-900">No GST-registered entities in this scope</p>
            <p class="mt-1.5 text-sm text-gray-500">
                Tick “Include entities not registered for GST” to show the others. Their GST cells stay as an em dash.
            </p>
        </div>
    @else
        <div class="pb-6 space-y-8">
            @foreach($report['entities'] as $entityRow)
                @php
                    $sheetEntity = $entityRow['entity'];
                    $sheetName = $sheetEntity->trading_name ?: $sheetEntity->legal_name;
                @endphp
                <section class="px-6 pt-5" data-gst-entity="{{ $sheetEntity->id }}" data-gst-registered="{{ $entityRow['gst_registered'] ? '1' : '0' }}">
                    <h2 class="text-sm font-semibold text-gray-900">{{ $sheetName }}</h2>
                    @if($sheetEntity->trading_name && $sheetEntity->trading_name !== $sheetEntity->legal_name)
                        <p class="text-xs text-gray-500">{{ $sheetEntity->legal_name }}</p>
                    @endif
                    <p class="text-xs text-gray-500" data-gst-lodgement="{{ $entityRow['lodgement_frequency'] }}">
                        {{ $entityRow['lodgement_label'] }}
                    </p>
                    @unless($entityRow['gst_registered'])
                        <p class="text-xs font-medium text-amber-800" data-gst-not-registered>Not GST registered</p>
                    @endunless
                    @include('financial-reports.partials.gst-worksheet-table', [
                        'visiblePeriods' => $visiblePeriods,
                        'rows' => $report['rows'],
                        'figuresByPeriod' => $entityRow['periods'],
                        'entityId' => (int) $sheetEntity->id,
                        'periodHeading' => $periodHeading,
                        'transactionUrl' => $transactionUrl,
                    ])
                </section>
            @endforeach

            @if($report['portfolio'])
                <section class="px-6" data-gst-portfolio>
                    <h2 class="text-sm font-semibold text-gray-900">Portfolio total</h2>
                    <p class="text-xs text-gray-500">GST collected, credits, net GST, and Total BAS include GST-registered entities only. Sales, purchases, and PAYG include every entity on this sheet.</p>
                    @include('financial-reports.partials.gst-worksheet-table', [
                        'visiblePeriods' => $visiblePeriods,
                        'rows' => $report['rows'],
                        'figuresByPeriod' => $report['portfolio']['periods'],
                        'entityId' => null,
                        'periodHeading' => $periodHeading,
                        'transactionUrl' => $transactionUrl,
                    ])
                </section>
            @endif
        </div>
    @endif
</x-report-shell>
