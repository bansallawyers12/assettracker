@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Chart of Accounts - {{ $businessEntity->legal_name }}</h1>
        <a href="{{ route('business-entities.chart-of-accounts.create', $businessEntity) }}" 
           class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Add New Account
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

    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200">
            @foreach($accounts as $account)
                <li class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($account->account_type === 'asset') bg-green-100 text-green-800
                                    @elseif($account->account_type === 'liability') bg-red-100 text-red-800
                                    @elseif($account->account_type === 'equity') bg-blue-100 text-blue-800
                                    @elseif($account->account_type === 'income') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($account->account_type) }}
                                </span>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $account->account_code }} - {{ $account->account_name }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $account->account_category }} | Balance: ${{ number_format($account->current_balance, 2) }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">
                                @if($account->is_active)
                                    <span class="text-green-600">Active</span>
                                @else
                                    <span class="text-red-600">Inactive</span>
                                @endif
                            </span>
                            <div class="flex space-x-1">
                                <a href="{{ route('business-entities.chart-of-accounts.edit', [$businessEntity, $account]) }}" 
                                   class="text-indigo-600 hover:text-indigo-900 text-sm">Edit</a>
                                @if($account->journalLines()->count() == 0)
                                    <form method="POST" action="{{ route('business-entities.chart-of-accounts.destroy', [$businessEntity, $account]) }}" 
                                          class="inline" onsubmit="return confirm('Are you sure you want to delete this account?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 text-sm ml-2">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
