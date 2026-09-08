<x-app-layout>
    @php
        $spaConfig = [
            'accounts' => $accountsPayload,
            'accountTypes' => $accountTypes,
            'accountCategories' => $accountCategories,
            'storeUrl' => $storeUrl,
            'indexUrl' => $indexUrl,
            'openPanel' => $openPanel,
            'openAccountId' => $openAccountId ? (int) $openAccountId : null,
            'csrfToken' => csrf_token(),
            'flashSuccess' => session('success'),
            'flashError' => session('error'),
        ];
    @endphp

    <div class="py-8 w-full px-4 sm:px-6 lg:px-8"
         x-data="chartOfAccountsSpa(@js($spaConfig))"
         x-init="init()">

        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Accounting</p>
                <h1 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Chart of Accounts</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Shared by all business entities. Balances by entity appear on financial reports.
                </p>
            </div>
            <button type="button"
                    @click="openCreate()"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">
                <x-lucide-plus class="h-4 w-4" aria-hidden="true" />
                Add account
            </button>
        </div>

        <div x-show="toast" x-cloak x-transition
             class="mb-4 rounded-lg border px-4 py-3 text-sm"
             :class="toastTone === 'error'
                ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200'
                : 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200'"
             x-text="toast"></div>

        <div class="mb-5 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Filters</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">Search and filter without leaving this page</p>
            </div>
            <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Search</label>
                    <div class="relative">
                        <x-lucide-search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true" />
                        <input type="search" x-model.debounce.200ms="search"
                               placeholder="Code, name, or description…"
                               class="w-full rounded-lg border-gray-300 py-2 pl-9 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Type</label>
                    <select x-model="typeFilter" class="w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">All types</option>
                        <template x-for="item in Object.entries(accountTypes)" :key="item[0]">
                            <option :value="item[0]" x-text="item[1]"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Status</label>
                    <select x-model="statusFilter" class="w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="all">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 border-t border-gray-100 px-4 py-3 dark:border-gray-800">
                <template x-for="chip in typeChips" :key="chip.key">
                    <button type="button"
                            @click="typeFilter = typeFilter === chip.key ? '' : chip.key"
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset transition-colors"
                            :class="typeFilter === chip.key ? chip.activeClass : chip.idleClass">
                        <span x-text="chip.label"></span>
                        <span class="tabular-nums opacity-80" x-text="chip.count"></span>
                    </button>
                </template>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-2 border-b border-gray-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Accounts</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <span x-text="filteredAccounts.length"></span> of <span x-text="accounts.length"></span> shown
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50/90 dark:bg-gray-800/60">
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th class="px-4 py-3 text-left">
                                <button type="button" @click="toggleSort('account_code')" class="inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                                    Code <span x-text="sortIndicator('account_code')"></span>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left">
                                <button type="button" @click="toggleSort('account_name')" class="inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                                    Account name <span x-text="sortIndicator('account_name')"></span>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left">
                                <button type="button" @click="toggleSort('account_type')" class="inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                                    Type <span x-text="sortIndicator('account_type')"></span>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Category</th>
                            <th class="px-4 py-3 text-right">
                                <button type="button" @click="toggleSort('journal_lines_count')" class="inline-flex w-full items-center justify-end gap-1 text-xs font-semibold uppercase tracking-wide text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                                    Journal lines <span x-text="sortIndicator('journal_lines_count')"></span>
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr x-show="filteredAccounts.length === 0">
                            <td colspan="7" class="px-4 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                                        <x-lucide-book-open class="h-6 w-6 text-gray-400" aria-hidden="true" />
                                    </div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="accounts.length === 0 ? 'No chart of accounts found' : 'No matching accounts'"></p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="accounts.length === 0 ? 'Seed the chart or add an account to get started.' : 'Try clearing search or filters.'"></p>
                                    <button type="button" @click="openCreate()" x-show="accounts.length === 0"
                                            class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500">
                                        Add account
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <template x-for="account in filteredAccounts" :key="account.id">
                            <tr class="transition-colors hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20">
                                <td class="px-4 py-3.5 whitespace-nowrap font-mono text-xs font-semibold text-gray-900 dark:text-gray-100" x-text="account.account_code"></td>
                                <td class="px-4 py-3.5">
                                    <div class="font-medium text-gray-900 dark:text-white" x-text="account.account_name"></div>
                                    <div class="mt-0.5 line-clamp-1 text-xs text-gray-500 dark:text-gray-400" x-show="account.description" x-text="account.description"></div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset"
                                          :class="typeBadgeClass(account.account_type)"
                                          x-text="accountTypes[account.account_type] || account.account_type"></span>
                                </td>
                                <td class="px-4 py-3.5 text-gray-600 dark:text-gray-300" x-text="accountCategories[account.account_category] || account.account_category"></td>
                                <td class="px-4 py-3.5 text-right tabular-nums text-gray-900 dark:text-white" x-text="account.journal_lines_count"></td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset"
                                          :class="account.is_active
                                            ? 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-200 dark:ring-emerald-900'
                                            : 'bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-950/50 dark:text-rose-200 dark:ring-rose-900'"
                                          x-text="account.is_active ? 'Active' : 'Inactive'"></span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="openEdit(account)"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                                                title="Edit account"
                                                :aria-label="'Edit ' + account.account_code">
                                            <x-lucide-pencil class="h-4 w-4" aria-hidden="true" />
                                        </button>
                                        <button type="button" @click="confirmDelete(account)" x-show="account.can_delete"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-200 bg-white text-rose-600 hover:bg-rose-50 dark:border-rose-900 dark:bg-gray-900 dark:text-rose-300 dark:hover:bg-rose-950/40"
                                                title="Delete account"
                                                :aria-label="'Delete ' + account.account_code">
                                            <x-lucide-trash-2 class="h-4 w-4" aria-hidden="true" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Create / Edit panel --}}
        <div x-show="panelOpen" x-cloak
             class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center sm:p-6"
             role="dialog" aria-modal="true" :aria-labelledby="'coa-panel-title'">
            <div class="absolute inset-0 bg-gray-900/60 dark:bg-gray-950/70" @click="closePanel()" aria-hidden="true"></div>
            <div class="relative z-10 flex max-h-[min(90vh,48rem)] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-gray-900 dark:ring-white/10"
                 @keydown.escape.window="closePanel()">
                <div class="flex items-start justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h2 id="coa-panel-title" class="text-lg font-semibold text-gray-900 dark:text-white" x-text="panelMode === 'create' ? 'Add account' : 'Edit account'"></h2>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Changes apply to the shared chart for all entities.</p>
                    </div>
                    <button type="button" @click="closePanel()" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200" aria-label="Close">
                        <x-lucide-x class="h-5 w-5" aria-hidden="true" />
                    </button>
                </div>

                <form @submit.prevent="saveAccount()" class="flex min-h-0 flex-1 flex-col">
                    <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Account code</label>
                                <input type="text" x-model="form.account_code" required maxlength="20"
                                       class="w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                                <p class="mt-1 text-xs text-rose-600" x-show="errors.account_code" x-text="errors.account_code"></p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Account name</label>
                                <input type="text" x-model="form.account_name" required maxlength="255"
                                       class="w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                                <p class="mt-1 text-xs text-rose-600" x-show="errors.account_name" x-text="errors.account_name"></p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Account type</label>
                                <select x-model="form.account_type" required
                                        class="w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                    <option value="">Select type</option>
                                    <template x-for="item in Object.entries(accountTypes)" :key="item[0]">
                                        <option :value="item[0]" x-text="item[1]"></option>
                                    </template>
                                </select>
                                <p class="mt-1 text-xs text-rose-600" x-show="errors.account_type" x-text="errors.account_type"></p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Account category</label>
                                <select x-model="form.account_category" required
                                        class="w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                    <option value="">Select category</option>
                                    <template x-for="item in Object.entries(accountCategories)" :key="item[0]">
                                        <option :value="item[0]" x-text="item[1]"></option>
                                    </template>
                                </select>
                                <p class="mt-1 text-xs text-rose-600" x-show="errors.account_category" x-text="errors.account_category"></p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Parent account</label>
                                <select x-model="form.parent_account_id"
                                        class="w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                    <option value="">No parent account</option>
                                    <template x-for="parent in parentOptions" :key="parent.id">
                                        <option :value="String(parent.id)" x-text="parent.account_code + ' — ' + parent.account_name"></option>
                                    </template>
                                </select>
                                <p class="mt-1 text-xs text-rose-600" x-show="errors.parent_account_id" x-text="errors.parent_account_id"></p>
                            </div>
                            <div x-show="panelMode === 'edit'">
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Status</label>
                                <select x-model="form.is_active"
                                        class="w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Description</label>
                            <textarea x-model="form.description" rows="3"
                                      class="w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                      placeholder="Optional notes"></textarea>
                            <p class="mt-1 text-xs text-rose-600" x-show="errors.description" x-text="errors.description"></p>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Per-entity opening balances are posted via
                            <a href="{{ route('financial-reports.journal-entries.index') }}" class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">Journal entries</a>.
                        </p>
                        <p class="text-xs text-rose-600" x-show="formError" x-text="formError"></p>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-5 py-4 dark:border-gray-800 dark:bg-gray-800/40">
                        <button type="button" @click="closePanel()"
                                class="inline-flex rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                            Cancel
                        </button>
                        <button type="submit" :disabled="saving"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-60">
                            <span x-text="saving ? 'Saving…' : (panelMode === 'create' ? 'Create account' : 'Update account')"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function chartOfAccountsSpa(config) {
            return {
                accounts: config.accounts || [],
                accountTypes: config.accountTypes || {},
                accountCategories: config.accountCategories || {},
                storeUrl: config.storeUrl,
                csrfToken: config.csrfToken,
                search: '',
                typeFilter: '',
                statusFilter: 'all',
                sortColumn: 'account_code',
                sortDir: 'asc',
                panelOpen: false,
                panelMode: 'create',
                editingId: null,
                saving: false,
                toast: '',
                toastTone: 'success',
                toastTimer: null,
                formError: '',
                errors: {},
                form: {
                    account_code: '',
                    account_name: '',
                    account_type: '',
                    account_category: '',
                    parent_account_id: '',
                    description: '',
                    is_active: '1',
                },
                init() {
                    if (config.flashSuccess) {
                        this.showToast(config.flashSuccess, 'success');
                    }
                    if (config.flashError) {
                        this.showToast(config.flashError, 'error');
                    }
                    if (config.openPanel === 'create') {
                        this.openCreate();
                    } else if (config.openPanel === 'edit' && config.openAccountId) {
                        const account = this.accounts.find((item) => Number(item.id) === Number(config.openAccountId));
                        if (account) {
                            this.openEdit(account);
                        }
                    }
                },
                get typeChips() {
                    const styles = {
                        asset: {
                            activeClass: 'bg-emerald-600 text-white ring-emerald-600',
                            idleClass: 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-200 dark:ring-emerald-900',
                        },
                        liability: {
                            activeClass: 'bg-rose-600 text-white ring-rose-600',
                            idleClass: 'bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-950/40 dark:text-rose-200 dark:ring-rose-900',
                        },
                        equity: {
                            activeClass: 'bg-sky-600 text-white ring-sky-600',
                            idleClass: 'bg-sky-50 text-sky-800 ring-sky-200 dark:bg-sky-950/40 dark:text-sky-200 dark:ring-sky-900',
                        },
                        income: {
                            activeClass: 'bg-amber-600 text-white ring-amber-600',
                            idleClass: 'bg-amber-50 text-amber-900 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-200 dark:ring-amber-900',
                        },
                        expense: {
                            activeClass: 'bg-gray-800 text-white ring-gray-800',
                            idleClass: 'bg-gray-100 text-gray-800 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700',
                        },
                    };
                    return Object.entries(this.accountTypes).map(([key, label]) => ({
                        key,
                        label,
                        count: this.accounts.filter((account) => account.account_type === key).length,
                        ...(styles[key] || styles.expense),
                    }));
                },
                get parentOptions() {
                    return this.accounts
                        .filter((account) => account.is_active && Number(account.id) !== Number(this.editingId || 0))
                        .slice()
                        .sort((a, b) => String(a.account_code).localeCompare(String(b.account_code), undefined, { numeric: true }));
                },
                get filteredAccounts() {
                    const q = this.search.trim().toLowerCase();
                    let rows = this.accounts.filter((account) => {
                        if (this.typeFilter && account.account_type !== this.typeFilter) {
                            return false;
                        }
                        if (this.statusFilter === 'active' && !account.is_active) {
                            return false;
                        }
                        if (this.statusFilter === 'inactive' && account.is_active) {
                            return false;
                        }
                        if (!q) {
                            return true;
                        }
                        return [account.account_code, account.account_name, account.description || '']
                            .join(' ')
                            .toLowerCase()
                            .includes(q);
                    });

                    const column = this.sortColumn;
                    const dir = this.sortDir === 'asc' ? 1 : -1;
                    rows = rows.slice().sort((a, b) => {
                        let left = a[column];
                        let right = b[column];
                        if (typeof left === 'number' || typeof right === 'number') {
                            return ((Number(left) || 0) - (Number(right) || 0)) * dir;
                        }
                        left = String(left ?? '').toLowerCase();
                        right = String(right ?? '').toLowerCase();
                        return left.localeCompare(right, undefined, { numeric: true }) * dir;
                    });

                    return rows;
                },
                toggleSort(column) {
                    if (this.sortColumn === column) {
                        this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                        return;
                    }
                    this.sortColumn = column;
                    this.sortDir = 'asc';
                },
                sortIndicator(column) {
                    if (this.sortColumn !== column) {
                        return '';
                    }
                    return this.sortDir === 'asc' ? '↑' : '↓';
                },
                typeBadgeClass(type) {
                    return {
                        asset: 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-200 dark:ring-emerald-900',
                        liability: 'bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-950/50 dark:text-rose-200 dark:ring-rose-900',
                        equity: 'bg-sky-50 text-sky-800 ring-sky-200 dark:bg-sky-950/50 dark:text-sky-200 dark:ring-sky-900',
                        income: 'bg-amber-50 text-amber-900 ring-amber-200 dark:bg-amber-950/50 dark:text-amber-200 dark:ring-amber-900',
                        expense: 'bg-gray-100 text-gray-800 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700',
                    }[type] || 'bg-gray-100 text-gray-800 ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700';
                },
                blankForm() {
                    return {
                        account_code: '',
                        account_name: '',
                        account_type: '',
                        account_category: '',
                        parent_account_id: '',
                        description: '',
                        is_active: '1',
                    };
                },
                openCreate() {
                    this.panelMode = 'create';
                    this.editingId = null;
                    this.form = this.blankForm();
                    this.errors = {};
                    this.formError = '';
                    this.panelOpen = true;
                    this.replaceUrl({ panel: 'create' });
                },
                openEdit(account) {
                    this.panelMode = 'edit';
                    this.editingId = account.id;
                    this.form = {
                        account_code: account.account_code || '',
                        account_name: account.account_name || '',
                        account_type: account.account_type || '',
                        account_category: account.account_category || '',
                        parent_account_id: account.parent_account_id ? String(account.parent_account_id) : '',
                        description: account.description || '',
                        is_active: account.is_active ? '1' : '0',
                    };
                    this.errors = {};
                    this.formError = '';
                    this.panelOpen = true;
                    this.replaceUrl({ panel: 'edit', account: account.id });
                },
                closePanel() {
                    this.panelOpen = false;
                    this.editingId = null;
                    this.errors = {};
                    this.formError = '';
                    this.replaceUrl({});
                },
                replaceUrl(params) {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('panel');
                    url.searchParams.delete('account');
                    Object.entries(params).forEach(([key, value]) => {
                        if (value !== null && value !== undefined && value !== '') {
                            url.searchParams.set(key, String(value));
                        }
                    });
                    window.history.replaceState({}, '', url.toString());
                },
                showToast(message, tone = 'success') {
                    this.toast = message;
                    this.toastTone = tone;
                    if (this.toastTimer) {
                        clearTimeout(this.toastTimer);
                    }
                    this.toastTimer = setTimeout(() => {
                        this.toast = '';
                    }, 4000);
                },
                async saveAccount() {
                    this.saving = true;
                    this.errors = {};
                    this.formError = '';

                    const payload = {
                        account_code: this.form.account_code,
                        account_name: this.form.account_name,
                        account_type: this.form.account_type,
                        account_category: this.form.account_category,
                        parent_account_id: this.form.parent_account_id || null,
                        description: this.form.description || null,
                    };

                    let url = this.storeUrl;
                    let method = 'POST';
                    if (this.panelMode === 'edit') {
                        const current = this.accounts.find((item) => Number(item.id) === Number(this.editingId));
                        if (!current) {
                            this.formError = 'Account no longer exists.';
                            this.saving = false;
                            return;
                        }
                        url = current.update_url;
                        method = 'PUT';
                        payload.is_active = this.form.is_active;
                    }

                    try {
                        const response = await fetch(url, {
                            method,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify(payload),
                        });

                        const data = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            if (data.errors) {
                                this.errors = Object.fromEntries(
                                    Object.entries(data.errors).map(([key, messages]) => [key, Array.isArray(messages) ? messages[0] : messages])
                                );
                            }
                            this.formError = data.message || 'Could not save account.';
                            return;
                        }

                        if (data.account) {
                            const index = this.accounts.findIndex((item) => Number(item.id) === Number(data.account.id));
                            if (index >= 0) {
                                this.accounts.splice(index, 1, data.account);
                            } else {
                                this.accounts.push(data.account);
                            }
                        }

                        this.showToast(data.message || 'Saved.');
                        this.closePanel();
                    } catch (e) {
                        this.formError = 'Network error while saving.';
                    } finally {
                        this.saving = false;
                    }
                },
                async confirmDelete(account) {
                    if (!account.can_delete) {
                        return;
                    }
                    if (!window.confirm('Delete this account? This cannot be undone.')) {
                        return;
                    }

                    try {
                        const response = await fetch(account.destroy_url, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            this.showToast(data.message || 'Could not delete account.', 'error');
                            return;
                        }
                        this.accounts = this.accounts.filter((item) => Number(item.id) !== Number(account.id));
                        this.showToast(data.message || 'Deleted.');
                    } catch (e) {
                        this.showToast('Network error while deleting.', 'error');
                    }
                },
            };
        }
    </script>
</x-app-layout>
