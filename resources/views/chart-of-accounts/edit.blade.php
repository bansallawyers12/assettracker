@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Edit Account - {{ $chartOfAccount->account_name }}</h1>

        <form method="POST" action="{{ route('business-entities.chart-of-accounts.update', [$businessEntity, $chartOfAccount]) }}" 
              class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="account_code" class="block text-sm font-medium text-gray-700 mb-2">Account Code</label>
                    <input type="text" id="account_code" name="account_code" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="{{ old('account_code', $chartOfAccount->account_code) }}" required>
                    @error('account_code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="account_name" class="block text-sm font-medium text-gray-700 mb-2">Account Name</label>
                    <input type="text" id="account_name" name="account_name" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="{{ old('account_name', $chartOfAccount->account_name) }}" required>
                    @error('account_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="account_type" class="block text-sm font-medium text-gray-700 mb-2">Account Type</label>
                    <select id="account_type" name="account_type" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                        <option value="">Select Account Type</option>
                        @foreach(App\Models\ChartOfAccount::$accountTypes as $key => $value)
                            <option value="{{ $key }}" {{ old('account_type', $chartOfAccount->account_type) == $key ? 'selected' : '' }}>
                                {{ $value }}
                            </option>
                        @endforeach
                    </select>
                    @error('account_type')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="account_category" class="block text-sm font-medium text-gray-700 mb-2">Account Category</label>
                    <input type="text" id="account_category" name="account_category" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="{{ old('account_category', $chartOfAccount->account_category) }}" required>
                    @error('account_category')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="parent_account_id" class="block text-sm font-medium text-gray-700 mb-2">Parent Account (Optional)</label>
                    <select id="parent_account_id" name="parent_account_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">No Parent Account</option>
                        @foreach($parentAccounts as $parent)
                            <option value="{{ $parent->id }}" {{ old('parent_account_id', $chartOfAccount->parent_account_id) == $parent->id ? 'selected' : '' }}>
                                {{ $parent->account_code }} - {{ $parent->account_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_account_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="is_active" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="is_active" name="is_active" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="1" {{ old('is_active', $chartOfAccount->is_active) ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ !old('is_active', $chartOfAccount->is_active) ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('is_active')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea id="description" name="description" rows="3" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $chartOfAccount->description) }}</textarea>
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end mt-6 space-x-4">
                <a href="{{ route('business-entities.chart-of-accounts.index', $businessEntity) }}" 
                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Update Account
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
