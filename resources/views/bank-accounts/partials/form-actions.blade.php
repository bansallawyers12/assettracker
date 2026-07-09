@props([
    'submitLabel' => 'Save account',
    'workspace' => true,
    'showCancel' => false,
    'cancelUrl' => null,
    'cancelLabel' => 'Cancel',
])

<div class="bank-form-actions">
    @if ($showCancel && $cancelUrl)
        <a href="{{ $cancelUrl }}" class="bank-btn-secondary">{{ $cancelLabel }}</a>
    @endif
    <button type="submit" @if($workspace) data-ws-submit @endif class="bank-btn-primary">
        {{ $submitLabel }}
    </button>
</div>
