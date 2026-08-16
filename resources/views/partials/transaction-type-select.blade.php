@props([
    'id' => 'transaction_type',
    'name' => 'transaction_type',
    'selected' => '',
    'required' => true,
    'class' => '',
    'groups' => null,
])

@php
    $typeGroups = $groups ?? \App\Models\Transaction::typeSelectGroups();
@endphp

<select name="{{ $name }}" id="{{ $id }}" @if($required) required @endif
        class="{{ $class }}"
        data-transaction-type-select
        data-tomselect
        data-tomselect-search="false">
    <option value="">Select Type</option>
    @foreach ($typeGroups as $groupLabel => $types)
        <optgroup label="{{ $groupLabel }}">
            @foreach ($types as $value => $label)
                @php
                    $optionDirection = array_key_exists($value, \App\Models\Transaction::$incomeTypes)
                        ? 'income'
                        : (array_key_exists($value, \App\Models\Transaction::$transferTypes) ? 'both' : 'expense');
                @endphp
                <option value="{{ $value }}"
                        data-direction="{{ $optionDirection }}"
                        {{ (string) $selected === (string) $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </optgroup>
    @endforeach
</select>
