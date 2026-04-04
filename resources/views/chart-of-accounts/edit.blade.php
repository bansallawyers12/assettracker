<x-app-layout>
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">Edit account — {{ $chartOfAccount->account_name }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Changes apply to the shared chart for all entities.</p>

        <form method="POST" action="{{ route('chart-of-accounts.update', $chartOfAccount) }}"
              class="bg-white dark:bg-gray-900 shadow-md rounded-lg px-8 pt-6 pb-8 mb-4 ring-1 ring-gray-200 dark:ring-gray-700">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="account_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Account code</label>
                    <input type="text" id="account_code" name="account_code"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           value="{{ old('account_code', $chartOfAccount->account_code) }}" required>
                    @error('account_code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="account_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Account name</label>
                    <input type="text" id="account_name" name="account_name"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           value="{{ old('account_name', $chartOfAccount->account_name) }}" required>
                    @error('account_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="account_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Account type</label>
                    <select id="account_type" name="account_type"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            required>
                        <option value="">Select type</option>
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
                    <label for="account_category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Account category</label>
                    <select id="account_category" name="account_category"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            required>
                        <option value="">Select category</option>
                        @foreach(App\Models\ChartOfAccount::$accountCategories as $key => $label)
                            <option value="{{ $key }}" {{ old('account_category', $chartOfAccount->account_category) == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('account_category')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="parent_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Parent account (optional)</label>
                    <select id="parent_account_id" name="parent_account_id"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">No parent account</option>
                        @foreach($parentAccounts as $parent)
                            <option value="{{ $parent->id }}" {{ (string) old('parent_account_id', $chartOfAccount->parent_account_id) === (string) $parent->id ? 'selected' : '' }}>
                                {{ $parent->account_code }} — {{ $parent->account_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_account_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="is_active" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                    <select id="is_active" name="is_active"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="1" {{ (string) old('is_active', $chartOfAccount->is_active ? '1' : '0') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ (string) old('is_active', $chartOfAccount->is_active ? '1' : '0') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('is_active')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                <textarea id="description" name="description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description', $chartOfAccount->description) }}</textarea>
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end mt-6 space-x-4">
                <a href="{{ route('chart-of-accounts.index') }}"
                   class="rounded-md bg-gray-200 dark:bg-gray-700 px-4 py-2 text-sm font-semibold text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600">
                    Cancel
                </a>
                <button type="submit"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Update account
                </button>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
