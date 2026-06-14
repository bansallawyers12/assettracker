@props([
    'id' => 'transaction_type',
    'name' => 'transaction_type',
    'selected' => '',
    'required' => true,
    'class' => '',
])

<select name="{{ $name }}" id="{{ $id }}" @if($required) required @endif
        class="{{ $class }}"
        data-transaction-type-select>
    <option value="">Select Type</option>
    @foreach (\App\Models\Transaction::typeSelectGroups() as $groupLabel => $types)
        <optgroup label="{{ $groupLabel }}">
            @foreach ($types as $value => $label)
                <option value="{{ $value }}"
                        data-direction="{{ array_key_exists($value, \App\Models\Transaction::$incomeTypes) ? 'income' : 'expense' }}"
                        {{ (string) $selected === (string) $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </optgroup>
    @endforeach
</select>
