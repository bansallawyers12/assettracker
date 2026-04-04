@props([
    'title' => '',
    'subtitle' => null,
    'entity' => null,
    'entityScopeLabel' => null,
])

<x-app-layout>

    {{-- ── Breadcrumb ──────────────────────────────────────────────── --}}
    <div class="bg-white border-b border-gray-200 print:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
            <nav class="flex items-center gap-1.5 text-sm">
                @if($entity)
                    <a href="{{ route('business-entities.show', $entity) }}"
                       class="text-blue-600 hover:underline font-medium truncate max-w-xs">
                        {{ $entity->legal_name }}
                    </a>
                    <svg class="h-3.5 w-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                @elseif($entityScopeLabel)
                    <span class="text-gray-600 font-medium truncate max-w-md">{{ $entityScopeLabel }}</span>
                    <svg class="h-3.5 w-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                @endif
                <a href="{{ route('financial-reports.index') }}"
                   class="text-blue-600 hover:underline font-medium shrink-0">Reports</a>
                <svg class="h-3.5 w-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-gray-600 truncate">{{ $title }}</span>
            </nav>
        </div>
    </div>

    {{-- ── Page title ──────────────────────────────────────────────── --}}
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <h1 class="text-xl font-semibold text-gray-900">{{ $title }}</h1>
        </div>
    </div>

    {{-- ── Filter toolbar ──────────────────────────────────────────── --}}
    @if(isset($filters) && $filters->isNotEmpty())
    <div class="bg-gray-50 border-b border-gray-200 print:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            {{ $filters }}
        </div>
    </div>
    @endif

    {{-- ── Report body ─────────────────────────────────────────────── --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 print:px-0 print:py-4">
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 print:shadow-none print:border-0">

            {{-- Report heading inside the white panel --}}
            <div class="px-6 pt-6 pb-4 border-b border-gray-100 print:px-4">
                <p class="text-base font-bold text-gray-900">{{ $title }}</p>
                @if($entity)
                    <p class="text-sm text-gray-700 mt-0.5 font-medium">{{ $entity->legal_name }}</p>
                @elseif($entityScopeLabel)
                    <p class="text-sm text-gray-700 mt-0.5 font-medium">{{ $entityScopeLabel }}</p>
                @endif
                @if($subtitle)
                    <p class="text-sm text-gray-500 mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>

            {{-- Main slot: report tables / content --}}
            {{ $slot }}

        </div>
    </div>

</x-app-layout>
