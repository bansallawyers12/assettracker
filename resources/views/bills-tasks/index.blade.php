@php
    use App\Models\Transaction as TransactionModel;
    use Illuminate\Support\Carbon;

    $isOverdue = function (?Carbon $d): bool {
        return $d && $d->copy()->startOfDay()->lt(now()->startOfDay());
    };
    $txnDirectionLabel = function ($t): string {
        return TransactionModel::directionFromType((string) $t->transaction_type) === 'income' ? 'Income' : 'Expense';
    };
@endphp

<x-app-layout>
    <div class="py-6 lg:py-8 bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-200 text-sm border border-emerald-200 dark:border-emerald-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 text-sm border border-red-200 dark:border-red-800">{{ session('error') }}</div>
            @endif

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Bills & tasks</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Full lists of unpaid bills, everything with a due date, paid bills, and completed reminders. The dashboard Reminders section shows overdue items plus the next 15 days.</p>
                </div>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to dashboard
                </a>
            </div>

            <div class="flex flex-wrap gap-2 p-1 bg-gray-100 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                @foreach ([
                    'unpaid' => 'Unpaid bills',
                    'due' => 'Due (all)',
                    'paid' => 'Paid',
                    'completed' => 'Completed reminders',
                ] as $key => $label)
                    <a href="{{ route('bills-tasks.index', ['tab' => $key]) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-colors
                           {{ $tab === $key
                               ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm border border-gray-200 dark:border-gray-600'
                               : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                        {{ $label }}
                        <span class="text-xs font-medium tabular-nums opacity-70">({{ $tabCounts[$key] ?? 0 }})</span>
                    </a>
                @endforeach
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                @if ($tab === 'unpaid')
                    <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">Unpaid bills</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">All transactions marked unpaid, including those without a due date.</p>
                    </div>
                    <div class="p-5">
                        @if ($unpaidTransactions->isEmpty())
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No unpaid bills.</p>
                        @else
                            <div class="space-y-3">
                                @foreach ($unpaidTransactions as $t)
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 rounded-xl border border-gray-100 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-700/30">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $t->description ?: 'Transaction' }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                {{ $t->businessEntity?->legal_name ?? 'Entity' }}
                                                @if ($t->vendor_name) · {{ $t->vendor_name }} @endif
                                            </p>
                                            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                                <span class="px-2 py-0.5 rounded-md {{ $txnDirectionLabel($t) === 'Income' ? 'bg-green-50 text-green-800 dark:bg-green-900/30 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-200' }} font-medium">{{ $txnDirectionLabel($t) }}</span>
                                                <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200 font-medium">${{ number_format((float) $t->amount, 2) }}</span>
                                                @if ($t->due_date)
                                                    <span class="px-2 py-0.5 rounded-md {{ $isOverdue($t->due_date) ? 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-200' }} font-medium">
                                                        Due {{ $t->due_date->format('d/m/Y') }}
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300">No due date</span>
                                                @endif
                                            </div>
                                        </div>
                                        <a href="{{ route('business-entities.transactions.edit', [$t->business_entity_id, $t->id]) }}"
                                           class="inline-flex justify-center items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 dark:text-indigo-300 shrink-0">
                                            Edit / pay
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-6">{{ $unpaidTransactions->links() }}</div>
                        @endif
                    </div>
                @elseif ($tab === 'paid')
                    <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">Paid bills</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Transactions marked paid, newest first.</p>
                    </div>
                    <div class="p-5">
                        @if ($paidTransactions->isEmpty())
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No paid transactions on file.</p>
                        @else
                            <div class="space-y-3">
                                @foreach ($paidTransactions as $t)
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 rounded-xl border border-gray-100 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-700/30">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $t->description ?: 'Transaction' }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $t->businessEntity?->legal_name ?? 'Entity' }}</p>
                                            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                                <span class="px-2 py-0.5 rounded-md {{ $txnDirectionLabel($t) === 'Income' ? 'bg-green-50 text-green-800 dark:bg-green-900/30 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-200' }} font-medium">{{ $txnDirectionLabel($t) }}</span>
                                                <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200 font-medium">${{ number_format((float) $t->amount, 2) }}</span>
                                                @if ($t->paid_at)
                                                    <span class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-200 font-medium">Paid {{ $t->paid_at->format('d/m/Y') }}</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300 font-medium">Txn {{ $t->date?->format('d/m/Y') ?? '—' }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <a href="{{ route('business-entities.transactions.edit', [$t->business_entity_id, $t->id]) }}"
                                           class="inline-flex justify-center items-center px-3 py-2 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-gray-200 shrink-0">
                                            View
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-6">{{ $paidTransactions->links() }}</div>
                        @endif
                    </div>
                @elseif ($tab === 'completed')
                    <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">Completed reminders</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Reminders marked complete in the system.</p>
                    </div>
                    <div class="p-5">
                        @if ($completedReminders->isEmpty())
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No completed reminders yet.</p>
                        @else
                            <div class="space-y-3">
                                @foreach ($completedReminders as $r)
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 p-4 rounded-xl border border-gray-100 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-700/30">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $r->title }}</p>
                                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-1 line-clamp-2">{{ $r->content }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                {{ $r->businessEntity?->legal_name ?? '—' }}
                                                @php $done = $r->completed_at ?? $r->updated_at; @endphp
                                                @if ($done) · Completed {{ $done->format('d/m/Y') }} @endif
                                            </p>
                                        </div>
                                        <a href="{{ route('reminders.show', $r) }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 shrink-0">Open</a>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-6">{{ $completedReminders->links() }}</div>
                        @endif
                    </div>
                @else
                    <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">Everything with a due date</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Active reminders, note reminders, unpaid bills with a due date, asset registration dates, and ASIC director due dates — sorted by date.</p>
                    </div>
                    <div class="p-5">
                        @if ($duePaginator->isEmpty())
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No due items found.</p>
                        @else
                            <div class="space-y-3">
                                @foreach ($duePaginator as $row)
                                    @php $d = $row->sort_date instanceof \Carbon\Carbon ? $row->sort_date : null; @endphp
                                    <div class="flex items-start gap-4 p-4 rounded-xl border {{ $isOverdue($d) ? 'border-red-200 dark:border-red-900/40 bg-red-50/40 dark:bg-red-900/10' : 'border-gray-100 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-700/30' }}">
                                        <div class="flex-shrink-0 w-2 h-2 mt-2 rounded-full {{ $isOverdue($d) ? 'bg-red-500' : 'bg-amber-400' }}"></div>
                                        <div class="flex-1 min-w-0">
                                            @if ($row->kind === 'reminder')
                                                @php $r = $row->reminder; @endphp
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 flex flex-wrap items-center gap-2">
                                                    {{ $r->title }}
                                                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-200">REMINDER</span>
                                                </p>
                                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">{{ $r->content }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $r->businessEntity?->legal_name }} · {{ $r->user?->name }}</p>
                                                <span class="inline-flex mt-2 px-2 py-0.5 rounded-md text-xs font-medium bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">{{ $r->next_due_date?->format('d/m/Y') }}</span>
                                            @elseif ($row->kind === 'note')
                                                @php $n = $row->note; @endphp
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 flex flex-wrap items-center gap-2">
                                                    {{ Str::limit($n->content, 120) }}
                                                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200">NOTE</span>
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $n->businessEntity?->legal_name }} · {{ $n->user?->name }}</p>
                                                <span class="inline-flex mt-2 px-2 py-0.5 rounded-md text-xs font-medium bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">{{ $n->reminder_date?->format('d/m/Y') }}</span>
                                            @elseif ($row->kind === 'bill')
                                                @php $t = $row->transaction; @endphp
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 flex flex-wrap items-center gap-2">
                                                    Bill: {{ $t->description ?: 'Unpaid' }}{{ $t->vendor_name ? ' · '.$t->vendor_name : '' }} — ${{ number_format((float) $t->amount, 2) }}
                                                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200">BILL</span>
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t->businessEntity?->legal_name }}</p>
                                                <span class="inline-flex mt-2 px-2 py-0.5 rounded-md text-xs font-medium {{ $isOverdue($t->due_date) ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200' : 'bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200' }}">{{ $t->due_date?->format('d/m/Y') }}</span>
                                            @elseif ($row->kind === 'registration')
                                                @php $a = $row->asset; @endphp
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 flex flex-wrap items-center gap-2">
                                                    Registration due — {{ $a->name }} ({{ $a->asset_type }})
                                                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200">ASSET</span>
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $a->businessEntity?->legal_name }}</p>
                                                <span class="inline-flex mt-2 px-2 py-0.5 rounded-md text-xs font-medium bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-200">{{ $a->registration_due_date?->format('d/m/Y') }}</span>
                                            @elseif ($row->kind === 'asic')
                                                @php $ep = $row->entityPerson; @endphp
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 flex flex-wrap items-center gap-2">
                                                    ASIC due — @if($ep->person){{ trim($ep->person->first_name.' '.$ep->person->last_name) }}@else{{ $ep->role ?? 'Director' }}@endif
                                                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200">ASIC</span>
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $ep->businessEntity?->legal_name }} · {{ $ep->role }}</p>
                                                <span class="inline-flex mt-2 px-2 py-0.5 rounded-md text-xs font-medium bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-200">{{ $ep->asic_due_date?->format('d/m/Y') }}</span>
                                            @endif
                                        </div>
                                        <div class="flex-shrink-0 flex flex-col items-end gap-2 min-w-[8rem]">
                                            @if ($row->kind === 'reminder')
                                                @php $r = $row->reminder; @endphp
                                                <div class="flex flex-wrap gap-1.5 justify-end">
                                                    <a href="{{ route('reminders.show', $r) }}" class="p-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-medium px-2 dark:bg-indigo-900/20 dark:hover:bg-indigo-900/40 dark:text-indigo-300">View</a>
                                                    <form action="{{ route('reminders.complete', $r) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/40 dark:text-emerald-300" title="Mark complete">✓</button>
                                                    </form>
                                                    <form action="{{ route('reminders.extend', $r) }}" method="POST" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="days" value="3">
                                                        <button type="submit" class="p-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:hover:bg-blue-900/40 dark:text-blue-300 text-xs font-medium px-2" title="Extend due date by 3 days">+3d</button>
                                                    </form>
                                                </div>
                                            @elseif ($row->kind === 'note')
                                                @php $n = $row->note; @endphp
                                                <div class="flex flex-wrap gap-1.5 justify-end">
                                                    @if ($n->business_entity_id)
                                                        <a href="{{ route('business-entities.show', $n->business_entity_id) }}" class="p-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-medium px-2 dark:bg-indigo-900/20 dark:text-indigo-300">Entity</a>
                                                    @endif
                                                    @if ($n->asset_id && $n->business_entity_id)
                                                        <a href="{{ route('business-entities.assets.show', [$n->business_entity_id, $n->asset_id]) }}" class="p-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-medium px-2 dark:bg-indigo-900/20 dark:text-indigo-300">Asset</a>
                                                    @endif
                                                    <form action="{{ route('notes.extend', $n) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="p-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:hover:bg-blue-900/40 dark:text-blue-300 text-xs font-medium px-2" title="Extend by 3 days">+3d</button>
                                                    </form>
                                                    <form action="{{ route('notes.finalize', $n) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/40 dark:text-emerald-300" title="Finalize">✓</button>
                                                    </form>
                                                </div>
                                            @elseif ($row->kind === 'bill')
                                                @php $t = $row->transaction; @endphp
                                                <a href="{{ route('business-entities.transactions.edit', [$t->business_entity_id, $t->id]) }}" class="p-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-medium px-2 dark:bg-indigo-900/20 dark:text-indigo-300">Edit</a>
                                            @elseif ($row->kind === 'registration')
                                                @php $a = $row->asset; @endphp
                                                <div class="flex flex-col items-end gap-2">
                                                    <a href="{{ route('business-entities.assets.show', [$a->business_entity_id, $a->id]) }}" class="p-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-medium px-2 dark:bg-indigo-900/20 dark:text-indigo-300">Asset</a>
                                                    @if ($a->business_entity_id)
                                                        <div class="flex flex-wrap gap-2 justify-end">
                                                            <form action="{{ route('assets.finalize-due-date', [$a->business_entity_id, $a->id, 'registration']) }}" method="POST" class="inline">
                                                                @csrf
                                                                <button type="submit" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline underline-offset-2">Finalize</button>
                                                            </form>
                                                            <form action="{{ route('assets.extend-due-date', [$a->business_entity_id, $a->id, 'registration']) }}" method="POST" class="inline">
                                                                @csrf
                                                                <button type="submit" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline underline-offset-2">Extend</button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                </div>
                                            @elseif ($row->kind === 'asic')
                                                @php $ep = $row->entityPerson; @endphp
                                                <div class="flex flex-col items-end gap-2">
                                                    <a href="{{ route('business-entities.show', $ep->business_entity_id) }}" class="p-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-medium px-2 dark:bg-indigo-900/20 dark:text-indigo-300">Entity</a>
                                                    <div class="flex flex-wrap gap-2 justify-end">
                                                        <form action="{{ route('entity-persons.finalize-due-date', $ep->id) }}" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline underline-offset-2">Finalize</button>
                                                        </form>
                                                        <form action="{{ route('entity-persons.extend-due-date', $ep->id) }}" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline underline-offset-2">Extend</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-6">{{ $duePaginator->links() }}</div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
