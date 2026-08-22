@props([
    'title' => '',
    'subtitle' => null,
    'entity' => null,
    'entityScopeLabel' => null,
])

<x-app-layout>

    {{-- ── Breadcrumb ──────────────────────────────────────────────── --}}
    <div class="bg-white border-b border-gray-200 print:hidden">
        <x-app-container class="py-2">
            <nav class="flex items-center gap-1.5 text-sm">
                @if($entity)
                    <a href="{{ route('business-entities.show', $entity) }}"
                       class="text-blue-600 hover:underline font-medium truncate max-w-xs">
                        {{ $entity->legal_name }}
                    </a>
                    <x-lucide-chevron-right class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                @elseif($entityScopeLabel)
                    <span class="text-gray-600 font-medium truncate max-w-md">{{ $entityScopeLabel }}</span>
                    <x-lucide-chevron-right class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                @endif
                <a href="{{ route('financial-reports.index') }}"
                   class="text-blue-600 hover:underline font-medium shrink-0">Reports</a>
                <x-lucide-chevron-right class="h-3.5 w-3.5 text-gray-400 shrink-0" />
                <span class="text-gray-600 truncate">{{ $title }}</span>
            </nav>
        </x-app-container>
    </div>

    {{-- ── Page title ──────────────────────────────────────────────── --}}
    <div class="bg-white border-b border-gray-200">
        <x-app-container class="py-4">
            <h1 class="text-xl font-semibold text-gray-900">{{ $title }}</h1>
        </x-app-container>
    </div>

    {{-- ── Filter toolbar ──────────────────────────────────────────── --}}
    @if(isset($filters) && $filters->isNotEmpty())
    <div class="bg-gray-50 border-b border-gray-200 print:hidden">
        <x-app-container class="py-3">
            {{ $filters }}
        </x-app-container>
    </div>
    @endif

    {{-- ── Report body ─────────────────────────────────────────────── --}}
    <x-app-container class="py-6 print:px-0 print:py-4">
        <div class="bg-white shadow-xs rounded-lg border border-gray-200 print:shadow-none print:border-0">

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
    </x-app-container>

</x-app-layout>
