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
    {{-- Hidden input: form POST always includes the address (gmp-place-autocomplete often does not). --}}
    <input type="hidden" name="{{ $name }}" id="{{ $hiddenId }}" value="{{ $value }}" />

    <div
        data-au-addr-mount
        data-field-id="{{ $fieldId }}"
        data-hidden-id="{{ $hiddenId }}"
        data-initial='@json($value)'
        {{ $attributes->except('required') }}
    ></div>
@else
    <input
        type="text"
        name="{{ $name }}"
        id="{{ $fieldId }}"
        value="{{ $value }}"
        {{ $attributes->merge([
            'autocomplete' => 'street-address',
        ]) }}
    />
@endif

@once('au-address-form-sync')
    @push('scripts')
        <script>
            document.addEventListener(
                'submit',
                function (ev) {
                    var form = ev.target;
                    if (!form || form.tagName !== 'FORM') {
                        return;
                    }
                    form.querySelectorAll('[data-au-addr-mount]').forEach(function (div) {
                        var hid = document.getElementById(div.dataset.hiddenId);
                        var gmp = div.querySelector('gmp-place-autocomplete');
                        if (hid && gmp) {
                            hid.value = gmp.value || '';
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

                        function syncHidden(div, gmp) {
                            var hid = document.getElementById(div.dataset.hiddenId);
                            if (hid && gmp) {
                                hid.value = gmp.value || '';
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

                            // Do not set `name` on the widget — the hidden input is the submit target.
                            // Do not use HTML `required` on the widget — it can block submit with no visible error.
                            var el = new PlaceAutocompleteElement({
                                includedRegionCodes: ['au'],
                                placeholder: 'Enter a location',
                            });
                            el.id = fieldId;
                            if (initial) {
                                el.value = initial;
                            }
                            syncHidden(div, el);

                            el.addEventListener('input', function () {
                                syncHidden(div, el);
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
                                syncHidden(div, el);
                            });

                            div.appendChild(el);
                        }

                        function mountVisibleTargets() {
                            document.querySelectorAll('[data-au-addr-mount]').forEach(function (div) {
                                if (div.offsetParent !== null) {
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
