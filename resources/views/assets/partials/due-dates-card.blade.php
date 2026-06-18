@php
    $dueDates = $asset->dueDateReminderItems();
@endphp

@if($dueDates->isNotEmpty())
    <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Due Soon</h4>
        <div class="space-y-1">
            @foreach($dueDates->take(3) as $dueDate)
                @php
                    $isOverdue = $dueDate['date']->isPast() && ! $dueDate['date']->isToday();
                    $colorClasses = [
                        'red' => $isOverdue ? 'text-red-600 bg-red-50 dark:bg-red-900/20' : 'text-red-500',
                        'orange' => $isOverdue ? 'text-orange-600 bg-orange-50 dark:bg-orange-900/20' : 'text-orange-500',
                        'blue' => $isOverdue ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'text-blue-500',
                        'purple' => $isOverdue ? 'text-purple-600 bg-purple-50 dark:bg-purple-900/20' : 'text-purple-500',
                        'green' => $isOverdue ? 'text-green-600 bg-green-50 dark:bg-green-900/20' : 'text-green-500',
                        'yellow' => $isOverdue ? 'text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20' : 'text-yellow-500',
                    ];
                @endphp
                <div class="flex justify-between items-center text-xs">
                    <span class="text-gray-500 dark:text-gray-400">{{ $dueDate['label'] }}</span>
                    <span class="font-medium px-1.5 py-0.5 rounded {{ $colorClasses[$dueDate['color']] ?? 'text-gray-500' }}">
                        {{ $dueDate['date']->format('d/m/Y') }}
                        @if($isOverdue)
                            <span class="ml-1">⚠️</span>
                        @endif
                    </span>
                </div>
            @endforeach
            @if($dueDates->count() > 3)
                <div class="text-xs text-gray-400 text-center pt-1">
                    +{{ $dueDates->count() - 3 }} more
                </div>
            @endif
        </div>
    </div>
@endif
