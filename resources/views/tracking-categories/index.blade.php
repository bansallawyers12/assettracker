@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Tracking Categories</h1>
            <p class="text-gray-600 mt-2">{{ $businessEntity->legal_name }}</p>
        </div>
        <a href="{{ route('business-entities.tracking-categories.create', $businessEntity) }}" 
           class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Add Tracking Category
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if($trackingCategories->count() > 0)
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                @foreach($trackingCategories as $category)
                    <li>
                        <div class="px-4 py-4 flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-blue-600 font-semibold">{{ substr($category->name, 0, 1) }}</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="flex items-center">
                                        <h3 class="text-lg font-medium text-gray-900">{{ $category->name }}</h3>
                                        @if(!$category->is_active)
                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Inactive
                                            </span>
                                        @endif
                                    </div>
                                    @if($category->description)
                                        <p class="text-sm text-gray-500 mt-1">{{ $category->description }}</p>
                                    @endif
                                    <p class="text-sm text-gray-500">
                                        {{ $category->subCategories->count() }} sub-categories
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('business-entities.tracking-categories.show', [$businessEntity, $category]) }}" 
                                   class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                    View
                                </a>
                                <a href="{{ route('business-entities.tracking-categories.edit', [$businessEntity, $category]) }}" 
                                   class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                    Edit
                                </a>
                                <form action="{{ route('business-entities.tracking-categories.destroy', [$businessEntity, $category]) }}" 
                                      method="POST" class="inline" 
                                      onsubmit="return confirm('Are you sure you want to delete this tracking category?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        @if($category->subCategories->count() > 0)
                            <div class="px-4 pb-4">
                                <div class="ml-14">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Sub-categories:</h4>
                                    <div class="space-y-1">
                                        @foreach($category->subCategories as $subCategory)
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="text-gray-600">{{ $subCategory->name }}</span>
                                                @if(!$subCategory->is_active)
                                                    <span class="text-red-500 text-xs">(Inactive)</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @else
        <div class="text-center py-12">
            <div class="mx-auto h-12 w-12 text-gray-400">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No tracking categories</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new tracking category.</p>
            <div class="mt-6">
                <a href="{{ route('business-entities.tracking-categories.create', $businessEntity) }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Add Tracking Category
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
