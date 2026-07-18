@props([
    'name',
    'id' => null,
    'value' => '',
])

@php
    $fieldId = $id ?? $name;
    $hiddenId = $fieldId . '_submitted';
    $googlePlacesKey = config('services.google.places_api_key');
@endphp

@if (filled($googlePlacesKey))
    {{-- Hidden input: form POST always includes the address. --}}
    <input type="hidden" name="{{ $name }}" id="{{ $hiddenId }}" value="{{ $value }}" />

    <input
        type="text"
        id="{{ $fieldId }}"
        value="{{ $value }}"
        autocomplete="street-address"
        placeholder="Start typing an address"
        data-au-addr-visible
        data-hidden-id="{{ $hiddenId }}"
        {{ $attributes->except(['required', 'name'])->merge(['class' => 'au-address-visible-input']) }}
    />

    <div
        data-au-addr-mount
        data-field-id="{{ $fieldId }}"
        data-hidden-id="{{ $hiddenId }}"
        data-visible-id="{{ $fieldId }}"
        data-initial='@json($value)'
    ></div>
@else
    <input
        type="text"
        name="{{ $name }}"
        id="{{ $fieldId }}"
        value="{{ $value }}"
        placeholder="Start typing an address"
        {{ $attributes->merge([
            'class' => 'au-address-visible-input',
            'autocomplete' => 'street-address',
        ]) }}
    />
@endif

@once('au-address-form-sync')
    @push('scripts')
        <script>
            document.addEventListener(
                'input',
                function (ev) {
                    var visible = ev.target.closest('[data-au-addr-visible]');
                    if (!visible) {
                        return;
                    }

                    var hid = document.getElementById(visible.dataset.hiddenId);
                    if (hid) {
                        hid.value = visible.value || '';
                    }
                },
                true
            );

            document.addEventListener(
                'submit',
                function (ev) {
                    var form = ev.target;
                    if (!form || form.tagName !== 'FORM') {
                        return;
                    }

                    form.querySelectorAll('[data-au-addr-visible]').forEach(function (visible) {
                        var hid = document.getElementById(visible.dataset.hiddenId);
                        if (hid) {
                            hid.value = visible.value || '';
                        }
                    });

                    form.querySelectorAll('[data-au-addr-mount]').forEach(function (div) {
                        var hid = document.getElementById(div.dataset.hiddenId);
                        var visible = document.getElementById(div.dataset.visibleId);
                        var gmp = div.querySelector('gmp-place-autocomplete');
                        var value = visible?.value || gmp?.value || '';

                        if (visible && gmp?.value) {
                            visible.value = gmp.value;
                            value = gmp.value;
                        }

                        if (hid) {
                            hid.value = value;
                        }
                    });
                },
                true
            );
        </script>
    @endpush
@endonce

@once('google-places-autocomplete-sdk')
    @push('scripts')
        @if (filled($googlePlacesKey))
            <script>
                window.__initAuAddressAutocomplete = function () {
                    (async function () {
                        var PlaceAutocompleteElement;
                        try {
                            var placesLib = await google.maps.importLibrary('places');
                            PlaceAutocompleteElement =
                                placesLib.PlaceAutocompleteElement ||
                                google.maps.places.PlaceAutocompleteElement;
                        } catch (e) {
                            console.error('Google Places library failed to load', e);
                            return;
                        }
                        if (!PlaceAutocompleteElement) {
                            console.error(
                                'PlaceAutocompleteElement missing. Enable Places API (New) in Google Cloud for this key.'
                            );
                            return;
                        }

                        function syncAddressFields(div, gmp) {
                            var hid = document.getElementById(div.dataset.hiddenId);
                            var visible = document.getElementById(div.dataset.visibleId);
                            var value = gmp?.value || visible?.value || '';

                            if (visible && value) {
                                visible.value = value;
                            }

                            if (hid) {
                                hid.value = value;
                            }
                        }

                        function mount(div) {
                            if (div.dataset.auAddrMounted) {
                                return;
                            }
                            div.dataset.auAddrMounted = '1';

                            var fieldId = div.dataset.fieldId;
                            var initial = '';
                            try {
                                initial = div.dataset.initial ? JSON.parse(div.dataset.initial) : '';
                            } catch (err) {
                                initial = '';
                            }

                            var el = new PlaceAutocompleteElement({
                                includedRegionCodes: ['au'],
                                placeholder: 'Search with Google Places (optional)',
                            });
                            el.id = fieldId + '_gmp';
                            if (initial) {
                                el.value = initial;
                            }
                            syncAddressFields(div, el);

                            el.addEventListener('input', function () {
                                syncAddressFields(div, el);
                            });

                            el.addEventListener('gmp-select', async function (event) {
                                var placePrediction =
                                    event.placePrediction ||
                                    (event.detail && event.detail.placePrediction);
                                if (!placePrediction) {
                                    return;
                                }
                                var place = placePrediction.toPlace();
                                try {
                                    await place.fetchFields({ fields: ['formattedAddress'] });
                                    if (place.formattedAddress) {
                                        el.value = place.formattedAddress;
                                    }
                                } catch (err) {
                                    console.error('Place fetchFields failed', err);
                                }
                                syncAddressFields(div, el);
                            });

                            div.appendChild(el);
                        }

                        function isMountTargetVisible(div) {
                            if (div.offsetParent !== null) {
                                return true;
                            }

                            var openPanel = div.closest('[data-panel-open="true"]');
                            return Boolean(openPanel);
                        }

                        function mountVisibleTargets() {
                            document.querySelectorAll('[data-au-addr-mount]').forEach(function (div) {
                                if (isMountTargetVisible(div)) {
                                    mount(div);
                                }
                            });
                        }

                        document.addEventListener(
                            'focusin',
                            function (e) {
                                var path = e.composedPath ? e.composedPath() : [e.target];
                                for (var i = 0; i < path.length; i++) {
                                    var n = path[i];
                                    if (n && n.dataset && n.dataset.auAddrMount !== undefined) {
                                        mount(n);
                                        return;
                                    }
                                }
                            },
                            true
                        );

                        document.addEventListener('au:address:refresh', function () {
                            mountVisibleTargets();
                        });

                        mountVisibleTargets();
                    })();
                };
            </script>
            <script
                async
                defer
                src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($googlePlacesKey) }}&amp;libraries=places&amp;loading=async&amp;v=weekly&amp;callback=__initAuAddressAutocomplete"
            ></script>
        @endif
    @endpush
@endonce
