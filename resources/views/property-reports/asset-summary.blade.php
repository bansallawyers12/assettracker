@php
    use App\Support\ReportEntityScopeLabel;
    use App\Support\ReportScopeQuery;

    $totals   = $report['totals'];
    $active   = $report['active'];
    $disposed = $report['disposed'];
    $formRoute = route('financial-reports.asset-summary');
    $entityScopeLabel = ReportEntityScopeLabel::format(
        $formsScope,
        $formsEntityIds,
        $businessEntities,
        'All reporting properties'
    );
    $reportQuery = function (array $merge = []) use ($formsScope, $formsEntityIds, $showDisposed) {
        $q = ReportScopeQuery::build($formsScope, $formsEntityIds, $merge);
        if ($showDisposed) {
            $q['show_disposed'] = 1;
        }
        return $q;
    };

    /**
     * Row background class based on row state.
     */
    $rowClass = function (array $row): string {
        if ($row['is_vacant']) return 'bg-gray-50';
        return '';
    };
@endphp

<x-report-shell
    title="Asset summary"
    subtitle="Property register — ownership, tenants &amp; loan details"
    :entity-scope-label="$entityScopeLabel"
    :full-width="true">

    <x-slot:filters>
        <form method="GET" action="{{ $formRoute }}" class="flex flex-wrap items-end gap-3">

            <x-report-entity-scope-picker
                :business-entities="$businessEntities"
                :forms-scope="$formsScope"
                :forms-entity-ids="$formsEntityIds"
            />

            {{-- Include disposed --}}
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="show_disposed" value="1"
                           {{ $showDisposed ? 'checked' : '' }}
                           class="rounded-sm border-gray-300 text-blue-600 focus:ring-blue-500">
                    Include previous properties
                </label>
            </div>

            <div class="flex items-end gap-2 ml-auto">
                <a href="{{ route('financial-reports.index') }}"
                   class="text-sm text-blue-600 hover:underline px-2 py-1.5">All reports</a>
                <button type="submit"
                        class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-sm px-4 py-1.5">
                    Update
                </button>
            </div>
        </form>
    </x-slot:filters>

    @if (session('error'))
        <div class="mx-6 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    {{-- ── Summary tiles ─────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 {{ $showDisposed ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }} gap-4 px-6 py-5 border-b border-gray-100 bg-gray-50/70">

        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Active properties</p>
            <p class="text-2xl font-bold text-gray-800 mt-0.5">{{ $totals['active_count'] }}</p>
        </div>

        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-green-600">Currently rented</p>
            <p class="text-2xl font-bold text-green-700 mt-0.5">{{ $totals['rented_count'] }}</p>
        </div>

        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-500">Vacant</p>
            <p class="text-2xl font-bold {{ $totals['vacant_count'] > 0 ? 'text-amber-600' : 'text-gray-400' }} mt-0.5">
                {{ $totals['vacant_count'] }}
            </p>
        </div>

        @if($showDisposed)
            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-wide text-red-500">Previous / Disposed</p>
                <p class="text-2xl font-bold text-red-600 mt-0.5">{{ $totals['disposed_count'] }}</p>
            </div>
        @endif
    </div>

    {{-- ── Active Properties Table ────────────────────────────────── --}}
    @if(empty($active))
        <div class="px-6 py-16 text-center text-gray-400">
            <p class="text-sm">No active properties found for the selected scope.</p>
        </div>
    @else
        <div class="px-4 sm:px-6 pb-2 overflow-x-auto">
            <table class="min-w-full text-sm border-collapse mt-4">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50/80">
                        <th class="py-2.5 pr-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-7">#</th>
                        <th class="py-2.5 px-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[180px]">Property Address</th>
                        <th class="py-2.5 px-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[160px]">Owning Entity</th>
                        <th class="py-2.5 px-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[160px]">Trustee / Trust</th>
                        <th class="py-2.5 px-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[160px]">Status</th>
                        <th class="py-2.5 px-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[140px]">Currently Used By</th>
                        <th class="py-2.5 px-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[100px]">Rent (ROI)</th>
                        {{-- Phase 2 finance columns --}}
                        <th class="py-2.5 px-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[110px]">Loan Provider</th>
                        <th class="py-2.5 px-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[100px]">Loan Payment</th>
                        <th class="py-2.5 px-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[110px]">Loan Balance</th>
                        <th class="py-2.5 px-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[110px]">Need to Put</th>
                        <th class="py-2.5 px-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[160px]">Loan Repayment</th>
                        <th class="py-2.5 px-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[100px]">Direct Debit</th>
                        <th class="py-2.5 px-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[130px]">Rent Paid By</th>
                        <th class="py-2.5 px-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[90px]">Purchased</th>
                        <th class="py-2.5 px-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[100px]">Land Tax</th>
                        <th class="py-2.5 px-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[90px]">LT Due</th>
                        <th class="py-2.5 px-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide min-w-[60px]">SRO</th>
                        <th class="py-2.5 pl-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-16"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($active as $i => $row)
                        @php
                            $a        = $row['asset'];
                            $bg       = $row['is_vacant'] ? 'bg-gray-50' : '';
                            $ltDue    = $row['land_tax_due_date'];
                            $ltOverdue = $ltDue && $ltDue->copy()->startOfDay()->lt(now()->startOfDay());
                            $ltSoon   = $ltDue && ! $ltOverdue && $ltDue->copy()->startOfDay()->lte(now()->addDays(15)->startOfDay());
                        @endphp
                        <tr class="hover:bg-blue-50/30 transition-colors {{ $bg }}">
                            <td class="py-2 pr-2 text-gray-400 tabular-nums text-xs">{{ $i + 1 }}</td>

                            {{-- Address --}}
                            <td class="py-2 px-2">
                                <a href="{{ route('business-entities.assets.show', [$a->business_entity_id, $a->id]) }}"
                                   class="font-medium text-blue-600 hover:underline text-xs leading-snug">
                                    {{ $a->address ?: $a->name }}
                                </a>
                                @if($a->address && $a->name !== $a->address)
                                    <p class="text-xs text-gray-400 truncate max-w-[200px]">{{ $a->name }}</p>
                                @endif
                                <span class="inline-block text-xs text-gray-400 italic">{{ $a->asset_type }}</span>
                            </td>

                            {{-- Owning entity --}}
                            <td class="py-2 px-2 text-xs text-gray-700 leading-snug">
                                {{ $row['entity_name'] ?: '—' }}
                            </td>

                            {{-- Trustee / Trust --}}
                            <td class="py-2 px-2 text-xs text-gray-600 leading-snug">
                                {{ $row['trustee_label'] ?: '—' }}
                            </td>

                            {{-- Status --}}
                            <td class="py-2 px-2">
                                <span class="text-xs {{ $row['is_vacant'] ? 'text-amber-600' : 'text-gray-700' }}">
                                    {{ $row['status_label'] }}
                                </span>
                            </td>

                            {{-- Currently used by --}}
                            <td class="py-2 px-2 text-xs {{ $row['is_vacant'] ? 'text-amber-500 italic' : 'text-gray-700' }}">
                                {{ $row['occupant_label'] }}
                                @if($row['re_managed'] && $row['re_company'])
                                    <br><span class="text-gray-400">via {{ $row['re_company'] }}</span>
                                @endif
                            </td>

                            {{-- Rent --}}
                            <td class="py-2 px-2 text-right tabular-nums text-xs font-medium text-gray-800">
                                {{ $row['rent_label'] }}
                            </td>

                            {{-- Phase 2: Loan Provider --}}
                            <td class="py-2 px-2 text-xs text-gray-600">
                                @if($row['loan_provider'])
                                    {{ $row['loan_provider'] }}
                                @else
                                    <a href="{{ route('business-entities.assets.edit', [$a->business_entity_id, $a->id]) }}"
                                       class="text-gray-300 hover:text-blue-500 transition-colors" title="Add loan details on asset edit">
                                        <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </a>
                                @endif
                            </td>

                            {{-- Phase 2: Loan Payment --}}
                            <td class="py-2 px-2 text-right tabular-nums text-xs text-gray-600">
                                {{ $row['loan_payment_amount'] !== null ? '$'.number_format($row['loan_payment_amount'], 0) : '—' }}
                            </td>

                            {{-- Phase 2: Loan Balance --}}
                            <td class="py-2 px-2 text-right tabular-nums text-xs text-gray-600">
                                {{ $row['loan_balance'] !== null ? '$'.number_format($row['loan_balance'], 0) : '—' }}
                            </td>

                            {{-- Phase 2: Need to Put --}}
                            <td class="py-2 px-2 text-right tabular-nums text-xs text-gray-600">
                                {{ $row['equity_required'] !== null ? '$'.number_format($row['equity_required'], 0) : '—' }}
                            </td>

                            {{-- Loan repayment account (BSB + Acc) --}}
                            <td class="py-2 px-2 text-xs text-gray-600 font-mono">
                                @if($row['loan_repayment_bsb'] || $row['loan_repayment_account_number'])
                                    @if($row['loan_repayment_bsb'])BSB {{ $row['loan_repayment_bsb'] }}@endif
                                    @if($row['loan_repayment_account_number'])
                                        <span class="js-acc-masked"> Acc: {{ $row['loan_repayment_account_number'] }}</span>
                                        @if($row['loan_repayment_bank_account_id'])
                                            <button type="button"
                                                    class="js-reveal-acc text-indigo-600 hover:underline ml-1 print:hidden"
                                                    data-reveal-url="{{ route('bank-accounts.reveal-account-number', $row['loan_repayment_bank_account_id']) }}?context=asset_summary_report">
                                                Reveal
                                            </button>
                                            <span class="js-acc-revealed hidden"></span>
                                        @endif
                                    @endif
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>

                            {{-- Phase 2: Direct Debit --}}
                            <td class="py-2 px-2 text-right tabular-nums text-xs text-gray-600">
                                {{ $row['direct_debit_amount'] !== null ? '$'.number_format($row['direct_debit_amount'], 0) : '—' }}
                            </td>

                            {{-- Phase 2: Rent Paid By --}}
                            <td class="py-2 px-2 text-xs text-gray-600">
                                {{ $row['rent_paid_by'] ?: '—' }}
                            </td>

                            {{-- Date of Purchase --}}
                            <td class="py-2 px-2 text-xs text-gray-600 whitespace-nowrap">
                                {{ $row['acquisition_date'] ? $row['acquisition_date']->format('M Y') : '—' }}
                            </td>

                            {{-- Land Tax Amount --}}
                            <td class="py-2 px-2 text-right tabular-nums text-xs {{ $ltOverdue ? 'text-red-700 font-semibold' : ($ltSoon ? 'text-yellow-700 font-semibold' : 'text-gray-600') }}">
                                {{ $row['land_tax_amount'] !== null ? '$'.number_format($row['land_tax_amount'], 0) : '—' }}
                            </td>

                            {{-- Land Tax Due Date --}}
                            <td class="py-2 px-2 text-xs whitespace-nowrap {{ $ltOverdue ? 'text-red-600 font-medium' : ($ltSoon ? 'text-yellow-600 font-medium' : 'text-gray-500') }}">
                                {{ $ltDue ? $ltDue->format('d/m/Y') : '—' }}
                                @if($ltOverdue) <span title="Overdue">⚠</span> @endif
                            </td>

                            {{-- SRO Updated --}}
                            <td class="py-2 px-2 text-center">
                                @if($row['sro_updated'])
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-100 text-green-700" title="SRO Updated">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-2 pl-2">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('business-entities.assets.show', [$a->business_entity_id, $a->id]) }}"
                                       class="text-gray-400 hover:text-blue-600 transition-colors" title="View asset">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('assets.financials', [$a->business_entity_id, $a->id]) }}"
                                       class="text-gray-400 hover:text-teal-600 transition-colors" title="View financials">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                {{-- Totals footer --}}
                @if(count($active) > 1)
                    <tfoot>
                        <tr class="border-t-2 border-gray-300 bg-gray-50 font-semibold">
                            <td colspan="6" class="py-2.5 px-2 text-xs text-gray-500 uppercase tracking-wide">
                                {{ count($active) }} properties
                            </td>
                            <td class="py-2.5 px-2 text-right tabular-nums text-xs text-gray-700">
                                {{-- Rent total: sum of monthly-equivalent amounts --}}
                                @php
                                    $rentTotal = collect($active)->sum(fn ($r) => $r['rent_amount'] ?? 0);
                                @endphp
                                @if($rentTotal > 0)
                                    ${{ number_format($rentTotal, 0) }}
                                @endif
                            </td>
                            <td class="py-2.5 px-2"></td>
                            <td class="py-2.5 px-2 text-right tabular-nums text-xs text-gray-700">
                                @php $pmtTotal = collect($active)->sum(fn ($r) => $r['loan_payment_amount'] ?? 0); @endphp
                                @if($pmtTotal > 0) ${{ number_format($pmtTotal, 0) }} @endif
                            </td>
                            <td class="py-2.5 px-2 text-right tabular-nums text-xs text-gray-700">
                                @php $balTotal = collect($active)->sum(fn ($r) => $r['loan_balance'] ?? 0); @endphp
                                @if($balTotal > 0) ${{ number_format($balTotal, 0) }} @endif
                            </td>
                            <td class="py-2.5 px-2 text-right tabular-nums text-xs text-gray-700">
                                @php $eqTotal = collect($active)->sum(fn ($r) => $r['equity_required'] ?? 0); @endphp
                                @if($eqTotal > 0) ${{ number_format($eqTotal, 0) }} @endif
                            </td>
                            <td colspan="4" class="py-2.5 px-2"></td>
                            <td class="py-2.5 px-2 text-right tabular-nums text-xs text-gray-700">
                                @if($totals['total_land_tax']) ${{ number_format($totals['total_land_tax'], 0) }} @endif
                            </td>
                            <td colspan="2" class="py-2.5 px-2"></td>
                            <td class="py-2.5 px-2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- Phase 2 hint banner (shown while loan columns are empty) --}}
        @php $hasAnyLoanData = collect($active)->contains(fn ($r) => filled($r['loan_provider'])); @endphp
        @if(! $hasAnyLoanData)
            <div class="mx-6 mb-4 rounded-md border border-blue-100 bg-blue-50 px-4 py-2.5 text-xs text-blue-700 flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 shrink-0 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>
                    Loan provider, balance, rent account, and related columns will be filled once Phase 2 finance fields
                    are added to each asset. Click the <span class="font-medium">+</span> icon in the Loan Provider column
                    to go to the asset edit page.
                </span>
            </div>
        @endif
    @endif

    {{-- ── Previous / Disposed Properties ────────────────────────── --}}
    @if($showDisposed && ! empty($disposed))
        <div class="border-t-4 border-red-200 mt-2 pb-6">
            <div class="px-6 py-3 bg-red-50/60 border-b border-red-100">
                <h3 class="text-sm font-semibold text-red-800">Previous Properties ({{ count($disposed) }})</h3>
                <p class="text-xs text-red-600 mt-0.5">Sold or disposed assets</p>
            </div>

            <div class="px-4 sm:px-6 overflow-x-auto">
                <table class="min-w-full text-sm border-collapse mt-3">
                    <thead>
                        <tr class="border-b border-red-100 bg-red-50/40">
                            <th class="py-2 pr-2 text-left text-xs font-semibold text-red-400 uppercase tracking-wide w-7">#</th>
                            <th class="py-2 px-2 text-left text-xs font-semibold text-red-400 uppercase tracking-wide min-w-[180px]">Property Address</th>
                            <th class="py-2 px-2 text-left text-xs font-semibold text-red-400 uppercase tracking-wide min-w-[160px]">Owning Entity</th>
                            <th class="py-2 px-2 text-left text-xs font-semibold text-red-400 uppercase tracking-wide min-w-[160px]">Trustee / Trust</th>
                            <th class="py-2 px-2 text-left text-xs font-semibold text-red-400 uppercase tracking-wide min-w-[120px]">Status</th>
                            <th class="py-2 px-2 text-left text-xs font-semibold text-red-400 uppercase tracking-wide min-w-[140px]">Last Used By</th>
                            <th class="py-2 px-2 text-right text-xs font-semibold text-red-400 uppercase tracking-wide min-w-[100px]">Rent (ROI)</th>
                            <th class="py-2 px-2 text-left text-xs font-semibold text-red-400 uppercase tracking-wide min-w-[110px]">Loan Provider</th>
                            <th class="py-2 px-2 text-right text-xs font-semibold text-red-400 uppercase tracking-wide min-w-[100px]">Loan Payment</th>
                            <th class="py-2 px-2 text-right text-xs font-semibold text-red-400 uppercase tracking-wide min-w-[110px]">Loan Balance</th>
                            <th class="py-2 px-2 text-left text-xs font-semibold text-red-400 uppercase tracking-wide min-w-[90px]">Purchased</th>
                            <th class="py-2 px-2 text-right text-xs font-semibold text-red-400 uppercase tracking-wide min-w-[100px]">Land Tax</th>
                            <th class="py-2 pl-2 text-left text-xs font-semibold text-red-400 uppercase tracking-wide w-12"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-red-50">
                        @foreach($disposed as $i => $row)
                            @php $a = $row['asset']; @endphp
                            <tr class="bg-red-50 hover:bg-red-100/50 transition-colors">
                                <td class="py-2 pr-2 text-red-300 tabular-nums text-xs">{{ $i + 1 }}</td>

                                <td class="py-2 px-2">
                                    <a href="{{ route('business-entities.assets.show', [$a->business_entity_id, $a->id]) }}"
                                       class="font-medium text-red-600 hover:underline text-xs leading-snug">
                                        {{ $a->address ?: $a->name }}
                                    </a>
                                    <span class="block text-xs text-red-400 italic">{{ $a->asset_type }}</span>
                                </td>

                                <td class="py-2 px-2 text-xs text-red-700">{{ $row['entity_name'] ?: '—' }}</td>

                                <td class="py-2 px-2 text-xs text-red-600">{{ $row['trustee_label'] ?: '—' }}</td>

                                <td class="py-2 px-2 text-xs text-red-700 font-medium">{{ $row['status_label'] }}</td>

                                <td class="py-2 px-2 text-xs text-red-600">{{ $row['occupant_label'] }}</td>

                                <td class="py-2 px-2 text-right tabular-nums text-xs text-red-600">
                                    {{ $row['rent_label'] }}
                                </td>

                                <td class="py-2 px-2 text-xs text-red-600">
                                    {{ $row['loan_provider'] ?: '—' }}
                                </td>

                                <td class="py-2 px-2 text-right tabular-nums text-xs text-red-600">
                                    {{ $row['loan_payment_amount'] !== null ? '$'.number_format($row['loan_payment_amount'], 0) : '—' }}
                                </td>

                                <td class="py-2 px-2 text-right tabular-nums text-xs text-red-600">
                                    {{ $row['loan_balance'] !== null ? '$'.number_format($row['loan_balance'], 0) : '—' }}
                                </td>

                                <td class="py-2 px-2 text-xs text-red-600 whitespace-nowrap">
                                    {{ $row['acquisition_date'] ? $row['acquisition_date']->format('M Y') : '—' }}
                                </td>

                                <td class="py-2 px-2 text-right tabular-nums text-xs text-red-600">
                                    {{ $row['land_tax_amount'] !== null ? '$'.number_format($row['land_tax_amount'], 0) : '—' }}
                                </td>

                                <td class="py-2 pl-2">
                                    <a href="{{ route('business-entities.assets.show', [$a->business_entity_id, $a->id]) }}"
                                       class="text-red-300 hover:text-red-600 transition-colors" title="View asset">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif(! $showDisposed && $totals['disposed_count'] > 0)
        <div class="mx-6 mb-5 mt-2 rounded-md border border-gray-200 bg-gray-50 px-4 py-2.5 text-xs text-gray-500 flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>
                {{ $totals['disposed_count'] }} previous / disposed {{ $totals['disposed_count'] === 1 ? 'property' : 'properties' }} hidden.
                <a href="{{ $formRoute }}?{{ http_build_query($reportQuery(['show_disposed' => 1])) }}"
                   class="text-blue-600 hover:underline font-medium">Show previous properties</a>
            </span>
        </div>
    @endif

    @push('scripts')
    <script>
    document.addEventListener('click', async function (event) {
        const button = event.target.closest('.js-reveal-acc');
        if (!button || button.disabled) {
            return;
        }

        const cell = button.closest('td');
        const masked = cell?.querySelector('.js-acc-masked');
        const revealed = cell?.querySelector('.js-acc-revealed');
        if (!masked || !revealed) {
            return;
        }

        button.disabled = true;
        button.textContent = '…';

        try {
            const response = await fetch(button.dataset.revealUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Request failed');
            }

            const data = await response.json();
            masked.classList.add('hidden');
            button.classList.add('hidden');
            revealed.textContent = ' Acc: ' + data.account_number;
            revealed.classList.remove('hidden');
        } catch (error) {
            button.disabled = false;
            button.textContent = 'Reveal';
            alert('Could not reveal account number.');
        }
    });
    </script>
    @endpush

</x-report-shell>
