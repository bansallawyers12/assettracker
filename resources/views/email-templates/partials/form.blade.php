@php
    $isEdit = $template !== null;
@endphp

<form
    class="bank-ws-form email-templates-ws-form"
    method="POST"
    action="{{ $isEdit ? route('email-templates.update', $template) : route('email-templates.store') }}"
    data-mode="{{ $isEdit ? 'edit' : 'create' }}"
>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div data-ws-form-errors class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"></div>

    <div class="bank-form-section">
        <p class="bank-form-section-title">{{ $isEdit ? __('Edit template') : __('New template') }}</p>
        <p class="bank-form-section-desc">
            {{ __('Reusable subject and message for compose. Use placeholders like') }}
            <code class="text-xs">@{{recipient_name}}</code>
            {{ __('in the body.') }}
        </p>

        <div class="bank-form-grid mt-4">
            <div class="bank-field bank-form-grid-full">
                <label for="email_template_name" class="bank-field-label">{{ __('Template name') }}</label>
                <input
                    type="text"
                    id="email_template_name"
                    name="name"
                    required
                    maxlength="255"
                    class="bank-field-control"
                    value="{{ old('name', $template?->name) }}"
                    placeholder="{{ __('e.g. Rent reminder') }}"
                />
            </div>

            <div class="bank-field bank-form-grid-full">
                <label for="email_template_subject" class="bank-field-label">{{ __('Email subject') }}</label>
                <input
                    type="text"
                    id="email_template_subject"
                    name="subject"
                    required
                    maxlength="255"
                    class="bank-field-control"
                    value="{{ old('subject', $template?->subject) }}"
                    placeholder="{{ __('Subject line') }}"
                />
            </div>

            <div class="bank-field bank-form-grid-full">
                <label for="email_template_description" class="bank-field-label">{{ __('Message body') }}</label>
                <textarea
                    id="email_template_description"
                    name="description"
                    required
                    rows="12"
                    class="bank-field-control font-mono text-sm leading-relaxed"
                    placeholder="{{ __('Write your template message…') }}"
                >{{ old('description', $template?->description) }}</textarea>
                <p class="bank-field-hint mt-2">
                    {{ __('Placeholders:') }}
                    <code class="text-xs">@{{recipient_name}}</code>,
                    <code class="text-xs">@{{sender_name}}</code>,
                    <code class="text-xs">@{{company_name}}</code>,
                    <code class="text-xs">@{{current_date}}</code>
                </p>
            </div>
        </div>
    </div>

    <div class="bank-form-actions">
        <button type="button" data-entity-panel-close class="bank-btn-secondary">{{ __('Cancel') }}</button>
        <button type="submit" data-ws-submit class="bank-btn-primary">
            {{ $isEdit ? __('Save changes') : __('Create template') }}
        </button>
    </div>
</form>
