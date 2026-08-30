@extends('website.layouts.dashboard')

@section('title', ($listing ? __('Edit Listing') : __('Add Property')).' | '.config('app.name'))
@section('page-title', $listing ? __('Edit Listing') : __('Add Property'))
@section('page-subtitle', $listing ? __('Update the details of your property.') : __('Fill in the details below to list your property.'))

@section('dashboard-content')
    @php
        $isDraft = $listing?->status === 'draft';
        $selectedAmenities = (array) old('utility_flags', $listing?->utility_flags ?? []);
        $customValues = $listing?->customFieldValues->pluck('value', 'custom_field_id') ?? collect();

        $steps = [
            ['key' => 'basic', 'title' => __('Basic Information'), 'hint' => __('Property main details')],
            ['key' => 'location', 'title' => __('Location'), 'hint' => __('Property location details')],
            ['key' => 'features', 'title' => __('Features'), 'hint' => __('Additional features')],
            ['key' => 'media', 'title' => __('Media'), 'hint' => __('Photos & floor plans')],
            ['key' => 'review', 'title' => __('Review'), 'hint' => __('Review & publish')],
        ];

        $inputClass = 'w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10';
        $labelClass = 'block text-sm font-semibold text-slate-800';

        $distressReasons = [
            'default' => __('Defaults / missed payments'),
            'repossession' => __('Repossession / foreclosure'),
            'divorce' => __('Divorce / separation'),
            'relocation' => __('Relocation / job change'),
            'estate' => __('Inheritance / estate'),
            'developer' => __('Developer / business exit'),
            'renovation' => __('Needs full renovation'),
            'cash_needed' => __('Raising cash'),
            'over_encumbered' => __('Over-encumbered'),
        ];
        $closingPeriods = [
            'asap' => __('As soon as possible'),
            '30' => __('Within 30 days'),
            '60' => __('Within 60 days'),
            '90' => __('Within 90 days'),
            'flexible' => __('Flexible'),
        ];
        $negotiationLevels = [
            'very_flexible' => __('Very flexible'),
            'flexible' => __('Flexible on price'),
            'moderate' => __('Moderate'),
            'firm' => __('Firm — close to asking'),
            'fixed' => __('Fixed / non-negotiable'),
        ];
        $sellerType = old('seller_type', $listing?->seller_type ?? 'individual');
        $expectedMarketValue = old('expected_market_value', $listing?->expected_market_value ?? '');
        $distressReason = old('reason_for_sale', $listing?->reason_for_sale ?? '');
        $closingPeriod = old('expected_closing_period', $listing?->expected_closing_period ?? '');
        $negotiationFlexibility = old('negotiation_flexibility', $listing?->negotiation_flexibility ?? '');
        $inspectionAccess = old('inspection_access', $listing?->inspection_access ?? '1');
    @endphp

    <div class="space-y-6" data-purpose="listing-wizard">
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800" role="alert">
                <p class="font-semibold">{{ __('Please fix the following before saving:') }}</p>
                <ul class="mt-2 list-disc space-y-1 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ $listing ? route('owner.listings.update', $listing) : route('owner.listings.store') }}"
            method="POST" enctype="multipart/form-data"
            x-data="{
                step: 'basic',
                message: '',
                steps: {{ Js::from(array_column($steps, 'key')) }},
                get index() { return this.steps.indexOf(this.step) },
                go(key) { this.step = key; this.message = ''; window.scrollTo({ top: 0, behavior: 'smooth' }) },
                fieldsIn(key) { return [...(this.$root.querySelector('[data-step=\'' + key + '\']')?.querySelectorAll('input, select, textarea') ?? [])] },
                // Friendly label for a field, so the message reads in plain language.
                labelFor(field) {
                    return (field.labels?.[0]?.textContent || field.getAttribute('aria-label') || field.getAttribute('placeholder') || 'a required field').replace('*', '').trim();
                },
                // A plain-language reason for one invalid field (empty, too high, too low…).
                reasonFor(field) {
                    const label = this.labelFor(field);
                    const v = field.validity;
                    const num = (s) => (s === '' || s === null ? s : Number(s).toLocaleString());
                    if (v.valueMissing) return 'Please fill in “' + label + '”.';
                    if (v.rangeOverflow) return '“' + label + '” must be ' + num(field.max) + ' or less.';
                    if (v.rangeUnderflow) return '“' + label + '” must be at least ' + num(field.min) + '.';
                    if (v.stepMismatch) return '“' + label + '” must be a whole number.';
                    if (v.tooShort) return '“' + label + '” must be at least ' + field.minLength + ' characters.';
                    if (v.tooLong) return '“' + label + '” must be ' + field.maxLength + ' characters or fewer.';
                    if (v.typeMismatch || v.patternMismatch || v.badInput) return 'Please enter a valid “' + label + '”.';
                    return field.validationMessage || ('Please check “' + label + '”.');
                },
                // Show (or clear) a small reason message directly under a field.
                setFieldError(field, msg) {
                    let el = field.parentElement?.querySelector('[data-field-error]');
                    if (! el) {
                        el = document.createElement('p');
                        el.setAttribute('data-field-error', '');
                        el.className = 'mt-1.5 text-xs text-rose-600';
                        field.parentElement?.appendChild(el);
                    }
                    el.textContent = msg || '';
                },
                clearFieldError(field) {
                    field.classList.remove('!border-red-500');
                    const el = field.parentElement?.querySelector('[data-field-error]');
                    if (el) el.textContent = '';
                },
                // Highlight invalid fields in a step and show the reason under each one.
                validateStep(key) {
                    const fields = this.fieldsIn(key);
                    let firstInvalid = null;
                    fields.forEach((f) => {
                        const ok = f.checkValidity();
                        f.classList.toggle('!border-red-500', ! ok);
                        this.setFieldError(f, ok ? '' : this.reasonFor(f));
                        if (! ok && ! firstInvalid) firstInvalid = f;
                    });
                    if (! firstInvalid) { this.message = ''; return true; }
                    this.message = 'Please fix the highlighted fields below.';
                    firstInvalid.focus();
                    return false;
                },
                next() {
                    if (! this.validateStep(this.step)) return;
                    if (this.index < this.steps.length - 1) this.go(this.steps[this.index + 1]);
                },
                back() { if (this.index > 0) this.go(this.steps[this.index - 1]) },
                submitAs(action) {
                    this.$refs.saveAction.value = action;
                    // Invalid fields on hidden steps block requestSubmit() silently,
                    // so jump to the first invalid field's step, highlight it, then explain.
                    if (! this.$root.checkValidity()) {
                        const invalidStep = this.$root.querySelector(':invalid')?.closest('[data-step]');
                        if (invalidStep) {
                            this.go(invalidStep.dataset.step);
                            this.$nextTick(() => this.validateStep(invalidStep.dataset.step));
                        }
                        return;
                    }
                    this.$root.requestSubmit();
                },
            }"
            @input="clearFieldError($event.target); message = ''"
            @change="clearFieldError($event.target); message = ''"
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @csrf
            @if ($listing) @method('PUT') @endif
            <input type="hidden" name="save_action" x-ref="saveAction" value="publish">

            <div class="grid lg:grid-cols-[260px_1fr]">
                {{-- Step rail --}}
                <ol class="hidden border-e border-slate-200 bg-slate-50/60 p-6 lg:block" aria-label="Steps">
                    @foreach ($steps as $index => $step)
                        <li class="relative {{ $loop->last ? '' : 'pb-8' }}">
                            @unless ($loop->last)
                                <span class="absolute start-4 top-9 h-full w-px bg-slate-200" aria-hidden="true"></span>
                            @endunless
                            <button type="button" @click="go('{{ $step['key'] }}')"
                                class="relative flex w-full items-start gap-3 text-start">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold transition"
                                    :class="step === '{{ $step['key'] }}' ? 'bg-indigo-600 text-white' : (index > {{ $index }} ? 'bg-indigo-100 text-indigo-600' : 'bg-white text-slate-500 ring-1 ring-slate-200')">
                                    {{ $index + 1 }}
                                </span>
                                <span class="min-w-0 pt-0.5">
                                    <span class="block text-sm font-bold transition"
                                        :class="step === '{{ $step['key'] }}' ? 'text-indigo-600' : 'text-slate-700'">{{ $step['title'] }}</span>
                                    <span class="block text-xs text-slate-500">{{ $step['hint'] }}</span>
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ol>

                <div class="min-w-0">
                    {{-- Header --}}
                    <div class="flex flex-col gap-4 border-b border-slate-200 p-6 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            @foreach ($steps as $step)
                                <template x-if="step === '{{ $step['key'] }}'">
                                    <div>
                                        <h2 class="text-lg font-bold text-slate-950">{{ $step['title'] }}</h2>
                                        <p class="mt-1 text-sm text-slate-600">{{ $step['hint'] }}.</p>
                                    </div>
                                </template>
                            @endforeach
                        </div>

                        <div class="flex flex-wrap shrink-0 gap-2">
                            <button type="button" @click="submitAs('draft')"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-500 hover:text-indigo-600">
                                <i class="h-4 w-4" data-lucide="save" aria-hidden="true"></i>{{ __('Save Draft') }}
                            </button>
                            <button type="button" @click="submitAs('publish')"
                                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                <i class="h-4 w-4" data-lucide="send" aria-hidden="true"></i>
                                {{ $listing && ! $isDraft ? __('Save Changes') : __('Publish Property') }}
                            </button>
                        </div>
                    </div>

                    {{-- Friendly validation notice --}}
                    <div x-show="message" x-cloak x-transition
                        class="mx-6 mt-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                        role="alert" aria-live="polite">
                        <i class="mt-0.5 h-4 w-4 shrink-0" data-lucide="info" aria-hidden="true"></i>
                        <span x-text="message"></span>
                    </div>

                    {{-- Step 1: Basic --}}
                    <div class="grid gap-5 p-6 md:grid-cols-2" x-show="step === 'basic'" data-step="basic">
                        <div>
                            <label class="{{ $labelClass }}" for="title">{{ __('Title') }}</label>
                            <input id="title" class="mt-2 {{ $inputClass }}" name="title" value="{{ old('title', $listing?->title) }}"
                                placeholder="{{ __('Enter property title') }}" minlength="5" maxlength="200" required>
                        </div>

                        <div>
                            <label class="{{ $labelClass }}" for="zone_id">{{ __('Zone') }}</label>
                            <select id="zone_id" class="mt-2 {{ $inputClass }}" name="zone_id" required>
                                <option value="">{{ __('Select location') }}</option>
                                @foreach ($zones as $zone)
                                    <option value="{{ $zone->id }}" @selected((int) old('zone_id', $listing?->zone_id) === $zone->id)>{{ $zone->name }} ({{ $zone->type }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="{{ $labelClass }}" for="type">{{ __('Listing type') }}</label>
                            <select id="type" class="mt-2 {{ $inputClass }}" name="type" required>
                                @foreach (\App\Enums\PropertyType::options() as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', $listing?->type ?? 'rent') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="{{ $labelClass }}" for="price">{{ __('Price') }}</label>
                            <input id="price" class="mt-2 {{ $inputClass }}" name="price" type="number" min="0" max="999999999999" step="1"
                                value="{{ old('price', $listing?->price) }}" placeholder="{{ __('Enter price') }}" required>
                        </div>

                        {{-- min/max mirror the DB column limits so the browser blocks out-of-range values before submit. --}}
                        @foreach ([
                            ['bedrooms', __('Bedrooms'), __('e.g. 3'), 0, 255],
                            ['bathrooms', __('Bathrooms'), __('e.g. 2'), 0, 255],
                            ['area_sqft', __('Area (sqft)'), __('e.g. 1450'), 0, 4294967295],
                            ['floor', __('Floor'), __('e.g. 6'), 0, 65535],
                            ['total_floors', __('Total floors'), __('e.g. 10'), 1, 65535],
                            ['advance_months', __('Advance months'), __('e.g. 2'), 0, 24],
                            ['service_charge', __('Service charge'), __('Enter service charge'), 0, 999999999999],
                        ] as [$name, $label, $placeholder, $min, $max])
                            <div>
                                <label class="{{ $labelClass }}" for="{{ $name }}">{{ $label }}</label>
                                <input id="{{ $name }}" class="mt-2 {{ $inputClass }}" name="{{ $name }}" type="number" min="{{ $min }}" max="{{ $max }}" step="1"
                                    value="{{ old($name, $listing?->$name) }}" placeholder="{{ $placeholder }}">
                            </div>
                        @endforeach

                        <div>
                            <label class="{{ $labelClass }}" for="allowed_for">{{ __('Allowed for') }}</label>
                            <select id="allowed_for" class="mt-2 {{ $inputClass }}" name="allowed_for">
                                @foreach (['both', 'family', 'bachelor'] as $value)
                                    <option value="{{ $value }}" @selected(old('allowed_for', $listing?->allowed_for ?? 'both') === $value)>{{ __(ucfirst($value)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-wrap gap-6 md:col-span-2">
                            @foreach ([['furnished', __('Furnished'), 'sofa'], ['parking', __('Parking available'), 'car'], ['is_negotiable', __('Price is negotiable'), 'handshake']] as [$name, $label, $icon])
                                <label class="flex items-center gap-2.5 text-sm font-semibold text-slate-700">
                                    <input type="hidden" name="{{ $name }}" value="0">
                                    <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $listing?->$name))
                                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    <i class="h-4 w-4 text-slate-500" data-lucide="{{ $icon }}" aria-hidden="true"></i>{{ $label }}
                                </label>
                            @endforeach
                        </div>

                        <div class="md:col-span-2">
                            <label class="{{ $labelClass }}" for="description">{{ __('Description') }}</label>
                            <textarea id="description" class="mt-2 {{ $inputClass }}" name="description" rows="6" maxlength="5000"
                                placeholder="{{ __('Describe the property, the neighbourhood, and what makes it a good home.') }}">{{ old('description', $listing?->description) }}</textarea>
                        </div>
                    </div>

                    {{-- Step 2: Location --}}
                    <div class="space-y-5 p-6" x-show="step === 'location'" data-step="location" x-cloak
                        x-data="{
                            lat: {{ Js::from(old('lat', $listing?->lat)) }},
                            lng: {{ Js::from(old('lng', $listing?->lng)) }},
                            locating: false,
                            locate() {
                                if (! navigator.geolocation) return;
                                this.locating = true;
                                navigator.geolocation.getCurrentPosition(
                                    (p) => { this.lat = p.coords.latitude.toFixed(7); this.lng = p.coords.longitude.toFixed(7); this.locating = false; },
                                    () => { this.locating = false; },
                                    { enableHighAccuracy: true, timeout: 10000 },
                                );
                            },
                        }"
                        x-init="$watch('step', (v) => { if (v === 'location' && ! lat && ! lng) locate() })">
                        <div>
                            <label class="{{ $labelClass }}" for="address">{{ __('Address') }}</label>
                            <input id="address" class="mt-2 {{ $inputClass }}" name="address" maxlength="255"
                                value="{{ old('address', $listing?->address) }}" placeholder="{{ __('Enter full address') }}">
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="{{ $labelClass }}" for="lat">{{ __('Latitude') }}</label>
                                <input id="lat" class="mt-2 {{ $inputClass }}" name="lat" x-model="lat" type="number" step="any"
                                    min="-90" max="90" placeholder="e.g. 23.7465">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="lng">{{ __('Longitude') }}</label>
                                <input id="lng" class="mt-2 {{ $inputClass }}" name="lng" x-model="lng" type="number" step="any"
                                    min="-180" max="180" placeholder="e.g. 90.3760">
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="button" @click="locate()" :disabled="locating"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-500 hover:text-indigo-600 disabled:opacity-60">
                                <i class="h-4 w-4" data-lucide="locate-fixed" aria-hidden="true"></i>
                                <span x-text="locating ? '{{ __('Detecting…') }}' : '{{ __('Use my current location') }}'"></span>
                            </button>
                            <a x-show="lat && lng" x-cloak :href="`https://www.google.com/maps?q=${lat},${lng}`" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-50">
                                <i class="h-4 w-4" data-lucide="external-link" aria-hidden="true"></i>{{ __('Check on Google Maps') }}
                            </a>
                        </div>

                        <template x-if="lat && lng">
                            <div class="overflow-hidden rounded-xl border border-slate-200">
                                <iframe class="h-72 w-full border-0" :src="`https://www.google.com/maps?q=${lat},${lng}&z=16&output=embed`"
                                    title="{{ __('Map preview of the property location') }}" loading="lazy"></iframe>
                            </div>
                        </template>
                        <p x-show="!lat || !lng" class="rounded-xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-600">
                            {{ __('Add coordinates to show a map preview. Buyers can then see the property on a map and get directions.') }}
                        </p>
                    </div>

                    {{-- Step 3: Features --}}
                    <div class="space-y-6 p-6" x-show="step === 'features'" data-step="features" x-cloak>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">{{ __('Amenities') }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Select everything this property offers.') }}</p>
                            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($amenities as $key => $amenity)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-indigo-400 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                                        <input type="checkbox" name="utility_flags[]" value="{{ $key }}"
                                            @checked(in_array($key, $selectedAmenities, true))
                                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <i class="h-4 w-4 text-slate-500" data-lucide="{{ $amenity['icon'] }}" aria-hidden="true"></i>
                                        {{ $amenity['label'] }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        @if ($customFields->isNotEmpty())
                            <div class="border-t border-slate-100 pt-6">
                                <h3 class="text-sm font-bold text-slate-800">{{ __('Additional details') }}</h3>
                                <div class="mt-4 grid gap-5 md:grid-cols-2">
                                    @foreach ($customFields as $field)
                                        @php $current = old('custom_fields.'.$field->id, $customValues[$field->id] ?? null); @endphp
                                        <div>
                                            <label class="{{ $labelClass }}" for="custom-{{ $field->id }}">{{ $field->label }}</label>

                                            @if ($field->type === 'select')
                                                <select id="custom-{{ $field->id }}" class="mt-2 {{ $inputClass }}" name="custom_fields[{{ $field->id }}]">
                                                    <option value="">{{ __('Select :field', ['field' => $field->label]) }}</option>
                                                    @foreach ($field->options ?? [] as $option)
                                                        <option value="{{ $option }}" @selected($current === $option)>{{ $option }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif ($field->type === 'checkbox')
                                                <label class="mt-2 flex items-center gap-2.5 text-sm text-slate-700">
                                                    <input type="hidden" name="custom_fields[{{ $field->id }}]" value="">
                                                    <input id="custom-{{ $field->id }}" type="checkbox" name="custom_fields[{{ $field->id }}]" value="1"
                                                        @checked(filled($current) && $current !== '0')
                                                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                    {{ __('Yes') }}
                                                </label>
                                            @elseif ($field->type === 'textarea')
                                                <textarea id="custom-{{ $field->id }}" class="mt-2 {{ $inputClass }}" name="custom_fields[{{ $field->id }}]" rows="3">{{ $current }}</textarea>
                                            @else
                                                <input id="custom-{{ $field->id }}" class="mt-2 {{ $inputClass }}"
                                                    name="custom_fields[{{ $field->id }}]"
                                                    type="{{ in_array($field->type, ['number', 'date'], true) ? $field->type : 'text' }}"
                                                    value="{{ $current }}" placeholder="{{ $field->label }}">
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="border-t border-slate-100 pt-6">
                            <h3 class="text-sm font-bold text-slate-800">{{ __('Seller & deal intelligence') }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ __('These signals power deal scoring and the secretive-deal benefit.') }}</p>
                            <div class="mt-4 grid gap-5 md:grid-cols-2">
                                <div>
                                    <label class="{{ $labelClass }}" for="seller_type">{{ __('Who is listing?') }}</label>
                                    <select id="seller_type" class="mt-2 {{ $inputClass }}" name="seller_type">
                                        <option value="individual" @selected(old('seller_type', $sellerType) === 'individual')>{{ __('Individual homeowner') }}</option>
                                        <option value="investor" @selected(old('seller_type', $sellerType) === 'investor')>{{ __('Investor / flipper') }}</option>
                                        <option value="developer" @selected(old('seller_type', $sellerType) === 'developer')>{{ __('Developer / builder') }}</option>
                                        <option value="institution" @selected(old('seller_type', $sellerType) === 'institution')>{{ __('Institution / bank / REIT') }}</option>
                                        <option value="poa" @selected(old('seller_type', $sellerType) === 'poa')>{{ __('Power of attorney / mandate') }}</option>
                                        <option value="estate" @selected(old('seller_type', $sellerType) === 'estate')>{{ __('Estate / executor') }}</option>
                                        <option value="professional" @selected(old('seller_type', $sellerType) === 'professional')>{{ __('Professional / agency') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}" for="expected_market_value">{{ __('Expected market value') }}</label>
                                    <input id="expected_market_value" class="mt-2 {{ $inputClass }}" type="number" min="0" step="0.01"
                                        name="expected_market_value" value="{{ old('expected_market_value', $expectedMarketValue ?? '') }}"
                                        placeholder="{{ __('Optional — used to validate the discount') }}">
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}" for="reason_for_sale">{{ __('Reason for sale / distress context') }}</label>
                                    <select id="reason_for_sale" class="mt-2 {{ $inputClass }}" name="reason_for_sale">
                                        <option value="">{{ __('Prefer not to say') }}</option>
                                        @foreach ($distressReasons as $key => $label)
                                            <option value="{{ $key }}" @selected(old('reason_for_sale', $distressReason) === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}" for="expected_closing_period">{{ __('Expected closing period') }}</label>
                                    <select id="expected_closing_period" class="mt-2 {{ $inputClass }}" name="expected_closing_period">
                                        <option value="">{{ __('Not specified') }}</option>
                                        @foreach ($closingPeriods as $key => $label)
                                            <option value="{{ $key }}" @selected(old('expected_closing_period', $closingPeriod) === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}" for="negotiation_flexibility">{{ __('Negotiation flexibility') }}</label>
                                    <select id="negotiation_flexibility" class="mt-2 {{ $inputClass }}" name="negotiation_flexibility">
                                        <option value="">{{ __('Not specified') }}</option>
                                        @foreach ($negotiationLevels as $key => $label)
                                            <option value="{{ $key }}" @selected(old('negotiation_flexibility', $negotiationFlexibility) === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mt-2 flex items-center gap-2.5 text-sm text-slate-700">
                                        <input type="hidden" name="inspection_access" value="0">
                                        <input id="inspection_access" type="checkbox" name="inspection_access" value="1"
                                            @checked((bool) old('inspection_access', $inspectionAccess))
                                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        {{ __('I allow on-site inspections (recommended)') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Step 4: Media --}}
                    <div class="space-y-6 p-6" x-show="step === 'media'" data-step="media" x-cloak>
                        <div>
                            <label class="{{ $labelClass }}" for="images">{{ __('Property photos') }}</label>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ __('JPEG, PNG or WebP. Up to :count photos, 5 MB each. The first photo becomes the cover.', ['count' => (int) setting('listing_max_images', 10)]) }}
                            </p>
                            <input id="images" class="mt-3 block w-full rounded-lg border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-600 file:me-4 file:rounded-lg file:border-0 file:bg-indigo-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white"
                                type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>

                            @if ($listing?->galleryImages->isNotEmpty())
                                <div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-5">
                                    @foreach ($listing->galleryImages as $image)
                                        <img class="aspect-[4/3] w-full rounded-lg bg-slate-100 object-cover"
                                            src="{{ $image->url() }}" alt="" loading="lazy">
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="border-t border-slate-100 pt-6">
                            <label class="{{ $labelClass }}" for="floor_plans">{{ __('Floor plans') }}</label>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Optional. Up to 5 floor plan images — they appear in their own tab, not the photo gallery.') }}</p>
                            <input id="floor_plans" class="mt-3 block w-full rounded-lg border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-600 file:me-4 file:rounded-lg file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white"
                                type="file" name="floor_plans[]" accept="image/jpeg,image/png,image/webp" multiple>

                            @if ($listing?->floorPlanImages->isNotEmpty())
                                <div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-5">
                                    @foreach ($listing->floorPlanImages as $floorPlan)
                                        <img class="aspect-[4/3] w-full rounded-lg bg-slate-100 object-contain p-1"
                                            src="{{ $floorPlan->url() }}" alt="" loading="lazy">
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="border-t border-slate-100 pt-6">
                            <label class="{{ $labelClass }}" for="title_documents">{{ __('Title & ownership documents') }}</label>
                            <p class="mt-1 text-sm text-slate-600">{{ __('Optional but boosts verification. Accepted: PDF, JPG or PNG. Up to 8 files.') }}</p>
                            <input id="title_documents" class="mt-3 block w-full rounded-lg border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-600 file:me-4 file:rounded-lg file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white"
                                type="file" name="title_documents[]" accept="application/pdf,image/jpeg,image/png" multiple>
                            @if ($listing?->titleDocuments->isNotEmpty())
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($listing->titleDocuments as $doc)
                                        <a href="{{ $doc->url() }}" target="_blank" rel="noopener"
                                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-indigo-600 hover:border-indigo-400">
                                            <i class="h-4 w-4" data-lucide="file-text" aria-hidden="true"></i>{{ $doc->filename() }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Step 5: Review --}}
                    <div class="space-y-5 p-6" x-show="step === 'review'" data-step="review" x-cloak>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                            <h3 class="text-sm font-bold text-slate-800">{{ __('Before you publish') }}</h3>
                            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                                @foreach ([
                                    __('Your listing is reviewed by our team before it goes live.'),
                                    __('Accurate photos and a clear description get far more enquiries.'),
                                    __('You can save a draft now and finish it later — drafts do not use up your listing quota.'),
                                ] as $note)
                                    <li class="flex items-start gap-2.5">
                                        <i class="mt-0.5 h-4 w-4 shrink-0 text-indigo-600" data-lucide="circle-check" aria-hidden="true"></i>{{ $note }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        @if ($listing)
                            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                                @foreach ([
                                    __('Status') => __(ucfirst($listing->status)),
                                    __('Photos') => $listing->galleryImages->count(),
                                    __('Floor plans') => $listing->floorPlanImages->count(),
                                    __('Amenities') => count($listing->utility_flags ?? []),
                                ] as $label => $value)
                                    <div class="flex justify-between gap-4 rounded-lg border border-slate-200 bg-white px-4 py-3">
                                        <dt class="text-slate-500">{{ $label }}</dt>
                                        <dd class="font-semibold text-slate-900">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif

                        <p class="text-sm text-slate-600">
                            {{ __('Use') }} <span class="font-semibold text-slate-800">{{ __('Save Draft') }}</span> {{ __('to keep working on this later, or') }}
                            <span class="font-semibold text-slate-800">{{ $listing && ! $isDraft ? __('Save Changes') : __('Publish Property') }}</span> {{ __('to send it for review.') }}
                        </p>
                    </div>

                    {{-- Step nav --}}
                    <div class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50/60 px-6 py-4">
                        <button type="button" @click="back()" x-show="index > 0" x-cloak
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-500 hover:text-indigo-600">
                            <i class="h-4 w-4" data-lucide="arrow-left" aria-hidden="true"></i>{{ __('Back') }}
                        </button>
                        <span x-show="index === 0" aria-hidden="true"></span>

                        <p class="text-xs text-slate-500">{{ __('Step') }} <span x-text="index + 1"></span> {{ __('of :total', ['total' => count($steps)]) }}</p>

                        <button type="button" @click="next()" x-show="index < steps.length - 1"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            {{ __('Next') }}<i class="h-4 w-4" data-lucide="arrow-right" aria-hidden="true"></i>
                        </button>
                        <span x-show="index === steps.length - 1" x-cloak aria-hidden="true"></span>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
