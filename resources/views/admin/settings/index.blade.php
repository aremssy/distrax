<x-admin-layout title="Settings">
    <x-admin-page-header title="Settings" :breadcrumbs="[['label' => 'Settings']]" />

    @php
    $tabs = [
        'general'      => ['label' => 'General',      'icon' => 'cog-6-tooth'],
        'homepage'     => ['label' => 'Homepage',     'icon' => 'home-modern'],
        'localization' => ['label' => 'Localization',  'icon' => 'language'],
        'limits'       => ['label' => 'Limits',        'icon' => 'adjustments'],
        'payments'     => ['label' => 'Payments',      'icon' => 'banknotes'],
        'branding'     => ['label' => 'Branding',      'icon' => 'swatch'],
        'social'       => ['label' => 'Social & App Links', 'icon' => 'share'],
        'templates'    => ['label' => 'Templates',     'icon' => 'document-text'],
        'mail'         => ['label' => 'Mail',           'icon' => 'envelope'],
        'sms'          => ['label' => 'SMS',            'icon' => 'device-phone-mobile'],
        'broadcasting' => ['label' => 'Broadcasting',   'icon' => 'bolt'],
    ];
    @endphp

    {{-- Tab nav --}}
    <div class="mb-6 border-b border-slate-200 dark:border-night-700">
        <nav class="-mb-px flex flex-nowrap gap-1 overflow-x-auto">
            @foreach ($tabs as $key => $meta)
                <a href="{{ route('admin.settings.index', ['tab' => $key]) }}"
                   class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition
                       {{ $tab === $key
                           ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
                           : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:border-slate-300' }}">
                    @include('admin.partials.icon', ['name' => $meta['icon'], 'class' => 'w-4 h-4'])
                    {{ $meta['label'] }}
                </a>
            @endforeach
        </nav>
    </div>

    @if ($tab === 'homepage')
        @include('admin.settings.partials.homepage')
    @else
    <form method="POST" action="{{ route('admin.settings.update', ['tab' => $tab]) }}" enctype="multipart/form-data">
        @csrf
        @method('POST')

        {{-- ── General ───────────────────────────────────────────────────────── --}}
        @if ($tab === 'general')
            <x-admin-card title="Site Information">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <x-admin.form.input name="site_name" label="Site Name" :value="old('site_name', $settings->get('site_name', config('app.name')))" required />
                    <x-admin.form.input name="site_email" label="Contact Email" type="email" :value="old('site_email', $settings->get('site_email'))" />
                    <x-admin.form.input name="site_phone" label="Contact Phone" :value="old('site_phone', $settings->get('site_phone'))" />
                    <x-admin.form.input name="site_address" label="Address" :value="old('site_address', $settings->get('site_address'))" />
                    <x-admin.form.input name="support_hours" label="Support Hours" placeholder="Mon - Fri, 9:00 AM - 6:00 PM" :value="old('support_hours', $settings->get('support_hours'))" />
                    <x-admin.form.input name="support_reply_time" label="Reply Time" placeholder="We reply within 24 hours" :value="old('support_reply_time', $settings->get('support_reply_time'))" />
                    <x-admin.form.input name="office_lat" label="Office Latitude" placeholder="23.7465" :value="old('office_lat', $settings->get('office_lat'))" />
                    <x-admin.form.input name="office_lng" label="Office Longitude" placeholder="90.3760" :value="old('office_lng', $settings->get('office_lng'))" />
                    <div class="md:col-span-2">
                        <x-admin.form.textarea name="meta_description" label="Meta Description" :rows="3">{{ old('meta_description', $settings->get('meta_description')) }}</x-admin.form.textarea>
                    </div>
                </div>
            </x-admin-card>

            <x-admin-card title="Google Maps" class="mt-6">
                <div class="rounded-lg bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-800 p-3 mb-4 text-xs text-indigo-700 dark:text-indigo-300 leading-relaxed">
                    Powers the property search map, office location map, and property detail maps across the site.
                    Requires a key with the <strong>Maps JavaScript API</strong> enabled and billing set up in the
                    Google Cloud Console. This key is embedded in public page source (it is a browser key, not a
                    server secret) — restrict it to your domain(s) in the Google Cloud Console.
                    Leave blank to show a "map unavailable" notice instead of a map.
                </div>
                <x-admin.form.input name="google_maps_api_key" label="API Key"
                    :value="old('google_maps_api_key', $settings->get('google_maps_api_key'))"
                    placeholder="AIzaSy..." autocomplete="off" data-1p-ignore data-lpignore="true" />
            </x-admin-card>

        {{-- ── Localization ──────────────────────────────────────────────────── --}}
        @elseif ($tab === 'localization')
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <x-admin-card title="Languages">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Manage site languages.</p>
                        <a href="{{ route('admin.languages.create') }}" class="btn-sm-primary">+ Add</a>
                    </div>
                    <x-admin.form.select name="default_language" label="Default Language">
                        @foreach ($languages as $lang)
                            <option value="{{ $lang->code }}" @selected(old('default_language', $settings->get('default_language', 'en')) === $lang->code)>
                                {{ $lang->name }} ({{ $lang->code }})
                            </option>
                        @endforeach
                    </x-admin.form.select>
                    <div class="mt-3">
                        <a href="{{ route('admin.languages.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Manage all languages →</a>
                    </div>
                    <div class="mt-1">
                        <a href="{{ route('admin.translations.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Translation editor →</a>
                    </div>
                </x-admin-card>

                <x-admin-card title="Currencies">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Manage currencies.</p>
                        <a href="{{ route('admin.currencies.create') }}" class="btn-sm-primary">+ Add</a>
                    </div>
                    <x-admin.form.select name="default_currency" label="Default Currency">
                        @foreach ($currencies as $cur)
                            <option value="{{ $cur->code }}" @selected(old('default_currency', $settings->get('default_currency', 'BDT')) === $cur->code)>
                                {{ $cur->name }} ({{ $cur->code }})
                            </option>
                        @endforeach
                    </x-admin.form.select>
                    <div class="mt-3">
                        <a href="{{ route('admin.currencies.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Manage all currencies →</a>
                    </div>
                </x-admin-card>
            </div>

        {{-- ── Limits ────────────────────────────────────────────────────────── --}}
        @elseif ($tab === 'limits')
            <x-admin-card title="Post & Feature Limits">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <x-admin.form.input
                        name="free_post_limit"
                        label="Free Post Limit"
                        type="number"
                        min="0"
                        :value="old('free_post_limit', $settings->get('free_post_limit', 0))"
                        hint="0 = free posting disabled" />

                    <x-admin.form.select name="limit_count_rule" label="Count Rule">
                        <option value="active_only"      @selected(old('limit_count_rule', $settings->get('limit_count_rule', 'active_only')) === 'active_only')>Active listings only</option>
                        <option value="include_archived" @selected(old('limit_count_rule', $settings->get('limit_count_rule')) === 'include_archived')>Include archived</option>
                    </x-admin.form.select>

                    <div class="flex items-center gap-3 pt-6">
                        <input type="hidden" name="saved_search_alert_enabled" value="0">
                        <input type="checkbox" id="saved_search_alert_enabled" name="saved_search_alert_enabled" value="1"
                            @checked(old('saved_search_alert_enabled', $settings->get('saved_search_alert_enabled', true)))
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="saved_search_alert_enabled" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            Enable Saved Search Alerts
                        </label>
                    </div>
                </div>
            </x-admin-card>

        {{-- ── Branding ──────────────────────────────────────────────────────── --}}
        @elseif ($tab === 'payments')
            @php
                $gateways = [
                    'stripe' => 'Stripe',
                    'paypal' => 'PayPal',
                    'bkash' => 'bKash',
                    'razorpay' => 'Razorpay',
                    'paystack' => 'Paystack',
                ];
                // Shared attributes that stop the browser's password manager from
                // autofilling these credential fields with the admin's own login.
            @endphp

            <x-admin-card title="Active Payment Gateways">
                <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Choose which gateways are offered to customers at checkout. Only gateways with valid credentials should be enabled.</p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach ($gateways as $key => $label)
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 px-3.5 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50 has-[:checked]:border-brand has-[:checked]:bg-indigo-50/60 dark:border-night-700 dark:text-slate-200 dark:hover:bg-night-800 dark:has-[:checked]:border-brand dark:has-[:checked]:bg-brand/10">
                            <input type="checkbox" name="active_payment_gateways[]" value="{{ $key }}"
                                   @checked(in_array($key, $activeGateways, true))
                                   class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand/30 dark:border-night-600 dark:bg-night-800">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </x-admin-card>

            {{-- A masked secret field: never renders the stored value; leaving it blank keeps the current one. --}}
            @php
                $secretField = fn (string $name, string $label) => ['name' => $name, 'label' => $label, 'set' => $secretConfigured[$name] ?? false];
            @endphp

            <x-admin-card title="Stripe" class="mt-6">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <x-admin.form.input name="stripe_publishable_key" label="Publishable Key" :value="old('stripe_publishable_key', $settings->get('stripe_publishable_key'))" placeholder="pk_live_..." autocomplete="off" data-1p-ignore data-lpignore="true" />
                    @include('admin.settings.partials.secret', $secretField('stripe_secret_key', 'Secret Key'))
                    @include('admin.settings.partials.secret', $secretField('stripe_webhook_secret', 'Webhook Signing Secret'))
                </div>
            </x-admin-card>

            <x-admin-card title="PayPal" class="mt-6">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <x-admin.form.input name="paypal_client_id" label="Client ID" :value="old('paypal_client_id', $settings->get('paypal_client_id'))" autocomplete="off" data-1p-ignore data-lpignore="true" />
                    @include('admin.settings.partials.secret', $secretField('paypal_client_secret', 'Client Secret'))
                    <x-admin.form.input name="paypal_webhook_id" label="Webhook ID" :value="old('paypal_webhook_id', $settings->get('paypal_webhook_id'))" autocomplete="off" data-1p-ignore data-lpignore="true" />
                    <label class="flex items-center gap-2.5 self-end pb-2.5 text-sm text-slate-700 dark:text-slate-200">
                        <input type="checkbox" name="paypal_sandbox" value="1" @checked($settings->get('paypal_sandbox')) class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand/30 dark:border-night-600 dark:bg-night-800">
                        Sandbox mode
                    </label>
                </div>
            </x-admin-card>

            <x-admin-card title="bKash" class="mt-6">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <x-admin.form.input name="bkash_username" label="Username" :value="old('bkash_username', $settings->get('bkash_username'))" autocomplete="off" data-1p-ignore data-lpignore="true" />
                    @include('admin.settings.partials.secret', $secretField('bkash_password', 'Password'))
                    <x-admin.form.input name="bkash_app_key" label="App Key" :value="old('bkash_app_key', $settings->get('bkash_app_key'))" autocomplete="off" data-1p-ignore data-lpignore="true" />
                    @include('admin.settings.partials.secret', $secretField('bkash_app_secret', 'App Secret'))
                    <label class="flex items-center gap-2.5 self-end pb-2.5 text-sm text-slate-700 dark:text-slate-200">
                        <input type="checkbox" name="bkash_sandbox" value="1" @checked($settings->get('bkash_sandbox')) class="h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand/30 dark:border-night-600 dark:bg-night-800">
                        Sandbox mode
                    </label>
                </div>
            </x-admin-card>

            <x-admin-card title="Razorpay" class="mt-6">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <x-admin.form.input name="razorpay_key_id" label="Key ID" :value="old('razorpay_key_id', $settings->get('razorpay_key_id'))" placeholder="rzp_live_..." autocomplete="off" data-1p-ignore data-lpignore="true" />
                    @include('admin.settings.partials.secret', $secretField('razorpay_key_secret', 'Key Secret'))
                    @include('admin.settings.partials.secret', $secretField('razorpay_webhook_secret', 'Webhook Secret'))
                </div>
            </x-admin-card>

            <x-admin-card title="Paystack" class="mt-6">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @include('admin.settings.partials.secret', $secretField('paystack_secret_key', 'Secret Key'))
                </div>
            </x-admin-card>

        @elseif ($tab === 'branding')
            <x-admin-card title="Theme & Colors">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Primary Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="primary_color" value="{{ old('primary_color', $settings->get('primary_color', '#5352ED')) }}"
                                class="h-10 w-20 cursor-pointer rounded border border-slate-200 dark:border-night-700 bg-white dark:bg-night-900 p-1">
                            <x-admin.form.input name="primary_color" :value="old('primary_color', $settings->get('primary_color', '#5352ED'))"
                                class="font-mono" placeholder="#5352ED" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Secondary Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="secondary_color" value="{{ old('secondary_color', $settings->get('secondary_color', '#0EA5E9')) }}"
                                class="h-10 w-20 cursor-pointer rounded border border-slate-200 dark:border-night-700 bg-white dark:bg-night-900 p-1">
                            <x-admin.form.input name="secondary_color" :value="old('secondary_color', $settings->get('secondary_color', '#0EA5E9'))"
                                class="font-mono" placeholder="#0EA5E9" />
                        </div>
                    </div>
                    <x-admin.form.select name="dark_mode_default" label="Default Theme Mode">
                        <option value="system" @selected(old('dark_mode_default', $settings->get('dark_mode_default', 'system')) === 'system')>System preference</option>
                        <option value="light"  @selected(old('dark_mode_default', $settings->get('dark_mode_default')) === 'light')>Always light</option>
                        <option value="dark"   @selected(old('dark_mode_default', $settings->get('dark_mode_default')) === 'dark')>Always dark</option>
                    </x-admin.form.select>
                </div>
            </x-admin-card>

            <x-admin-card title="Site Logo" class="mt-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="flex h-16 w-40 shrink-0 items-center justify-center rounded-lg border border-slate-200 dark:border-night-700 bg-white p-2">
                        <img src="{{ $settings->get('site_logo') ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->get('site_logo')) : asset('assets/logo/logo.svg') }}"
                            alt="Current site logo" class="max-h-full max-w-full object-contain">
                    </div>
                    <div class="flex-1">
                        <label for="site_logo" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Upload New Logo</label>
                        <input id="site_logo" type="file" name="site_logo" accept="image/png,image/jpeg,image/svg+xml,image/webp"
                            class="block w-full text-sm text-slate-600 dark:text-slate-300 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-600 hover:file:bg-indigo-100">
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">PNG, JPG, SVG or WEBP up to 2&nbsp;MB. Applied across the site header, footer and auth pages.</p>
                        @error('site_logo')
                            <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-admin-card>

            <x-admin-card title="Favicon & Share Image" class="mt-6">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg border border-slate-200 dark:border-night-700 bg-white p-2">
                            <img src="{{ $settings->get('site_favicon') ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->get('site_favicon')) : asset('assets/favicon.svg') }}"
                                alt="Current favicon" class="max-h-full max-w-full object-contain">
                        </div>
                        <div class="flex-1">
                            <label for="site_favicon" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Favicon</label>
                            <input id="site_favicon" type="file" name="site_favicon" accept="image/png,image/svg+xml,image/x-icon,image/webp"
                                class="block w-full text-sm text-slate-600 dark:text-slate-300 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-600 hover:file:bg-indigo-100">
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">PNG, SVG, ICO or WEBP up to 1&nbsp;MB. Shown in the browser tab.</p>
                            @error('site_favicon')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div class="flex h-16 w-28 shrink-0 items-center justify-center rounded-lg border border-slate-200 dark:border-night-700 bg-white p-2">
                            <img src="{{ $settings->get('og_image') ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->get('og_image')) : asset('assets/og-image.png') }}"
                                alt="Current share image" class="max-h-full max-w-full object-contain">
                        </div>
                        <div class="flex-1">
                            <label for="og_image" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Social Share Image</label>
                            <input id="og_image" type="file" name="og_image" accept="image/png,image/jpeg,image/webp"
                                class="block w-full text-sm text-slate-600 dark:text-slate-300 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-600 hover:file:bg-indigo-100">
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">PNG, JPG or WEBP, ideally 1200×630, up to 2&nbsp;MB. Used when pages are shared on social media.</p>
                            @error('og_image')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </x-admin-card>

        {{-- ── Social & App Links ────────────────────────────────────────────── --}}
        @elseif ($tab === 'social')
            <x-admin-card title="Social Media Links">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <x-admin.form.input name="social_facebook" label="Facebook URL" :value="old('social_facebook', $settings->get('social_facebook'))" placeholder="https://facebook.com/yourpage" />
                    <x-admin.form.input name="social_twitter" label="Twitter / X URL" :value="old('social_twitter', $settings->get('social_twitter'))" placeholder="https://x.com/yourhandle" />
                    <x-admin.form.input name="social_instagram" label="Instagram URL" :value="old('social_instagram', $settings->get('social_instagram'))" placeholder="https://instagram.com/yourpage" />
                    <x-admin.form.input name="social_youtube" label="YouTube URL" :value="old('social_youtube', $settings->get('social_youtube'))" placeholder="https://youtube.com/@yourchannel" />
                </div>
            </x-admin-card>

            <x-admin-card title="Mobile App Links" class="mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <x-admin.form.input name="app_store_url" label="Apple App Store URL" :value="old('app_store_url', $settings->get('app_store_url'))" placeholder="https://apps.apple.com/..." />
                    <x-admin.form.input name="play_store_url" label="Google Play Store URL" :value="old('play_store_url', $settings->get('play_store_url'))" placeholder="https://play.google.com/store/apps/..." />
                </div>
            </x-admin-card>

        {{-- ── Templates ─────────────────────────────────────────────────────── --}}
        @elseif ($tab === 'templates')
            <x-admin-card title="Email & SMS Templates">
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Edit notification templates sent to users. Use <code class="bg-slate-100 dark:bg-night-800 px-1 py-0.5 rounded text-xs">@{{ variable }}</code> placeholders.</p>
                <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-night-700">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-night-800 text-sm">
                        <thead class="bg-slate-50 dark:bg-night-800/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Template</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Channel</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-night-800 bg-white dark:bg-night-900">
                            @forelse ($emailTemplates as $tpl)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-800 dark:text-slate-200">{{ $tpl->name }}</td>
                                    <td class="px-4 py-3">
                                        <x-admin-badge :color="$tpl->channel === 'email' ? 'blue' : 'purple'">{{ strtoupper($tpl->channel) }}</x-admin-badge>
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-admin-badge :color="$tpl->is_active ? 'green' : 'gray'">{{ $tpl->is_active ? 'Active' : 'Inactive' }}</x-admin-badge>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('admin.email-templates.edit', $tpl) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-medium">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">No templates seeded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin-card>

        {{-- ── Mail / SMTP ───────────────────────────────────────────────────── --}}
        @elseif ($tab === 'mail')
            <x-admin-card title="Mail Driver">
                <x-admin.form.select name="mail_mailer" label="Active Driver"
                    hint="Sends all transactional email — verification codes, password resets and notifications. 'Log' writes emails to the application log instead of delivering them — use it for local development only; no real email is sent.">
                    <option value="log"  @selected(old('mail_mailer', $settings->get('mail_mailer', 'log')) === 'log')>Log (development only)</option>
                    <option value="smtp" @selected(old('mail_mailer', $settings->get('mail_mailer')) === 'smtp')>SMTP</option>
                </x-admin.form.select>
            </x-admin-card>

            {{-- autocomplete hints stop the browser's password manager from filling these
                 credential fields with the admin's own login email/password. --}}
            <x-admin-card title="SMTP Credentials" class="mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <x-admin.form.input name="mail_host" label="Host" :value="old('mail_host', $settings->get('mail_host'))" placeholder="smtp.yourprovider.com" autocomplete="off" data-1p-ignore data-lpignore="true" />
                    <x-admin.form.input name="mail_port" label="Port" type="number" :value="old('mail_port', $settings->get('mail_port', 587))" placeholder="587" autocomplete="off" />
                    <x-admin.form.select name="mail_encryption" label="Encryption"
                        hint="TLS for port 587 (recommended), SSL for port 465.">
                        <option value="tls"  @selected(old('mail_encryption', $settings->get('mail_encryption', 'tls')) === 'tls')>TLS (STARTTLS)</option>
                        <option value="ssl"  @selected(old('mail_encryption', $settings->get('mail_encryption')) === 'ssl')>SSL</option>
                        <option value="none" @selected(old('mail_encryption', $settings->get('mail_encryption')) === 'none')>None</option>
                    </x-admin.form.select>
                    <x-admin.form.input name="mail_username" label="Username" :value="old('mail_username', $settings->get('mail_username'))" placeholder="user@yourdomain.com" autocomplete="off" data-1p-ignore data-lpignore="true" />
                    <x-admin.form.input name="mail_password" label="Password" type="password"
                        :hint="!empty($secretConfigured['mail_password']) ? 'A password is saved. Leave blank to keep it.' : 'Your SMTP password or app password.'"
                        placeholder="••••••••" autocomplete="new-password" data-1p-ignore data-lpignore="true" />
                </div>
            </x-admin-card>

            <x-admin-card title="From Address" class="mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <x-admin.form.input name="mail_from_address" label="From Email" type="email" :value="old('mail_from_address', $settings->get('mail_from_address'))" placeholder="no-reply@yourdomain.com" autocomplete="off" />
                    <x-admin.form.input name="mail_from_name" label="From Name" :value="old('mail_from_name', $settings->get('mail_from_name'))" :placeholder="$settings->get('site_name', config('app.name'))" autocomplete="off" />
                </div>
            </x-admin-card>

        {{-- ── SMS / OTP ─────────────────────────────────────────────────────── --}}
        @elseif ($tab === 'sms')
            <x-admin-card title="SMS Driver">
                <x-admin.form.select name="otp_driver" label="Active Driver"
                    hint="Sends both OTP verification codes and SMS notifications. 'Log' writes messages to the application log instead of delivering them — use it for local development only; no real SMS is sent.">
                    <option value="log"    @selected(old('otp_driver', $settings->get('otp_driver', 'log')) === 'log')>Log (development only)</option>
                    <option value="twilio" @selected(old('otp_driver', $settings->get('otp_driver')) === 'twilio')>Twilio</option>
                    <option value="vonage" @selected(old('otp_driver', $settings->get('otp_driver')) === 'vonage')>Vonage (Nexmo)</option>
                    <option value="zan"    @selected(old('otp_driver', $settings->get('otp_driver')) === 'zan')>Zan Communications (Bangladesh)</option>
                </x-admin.form.select>
            </x-admin-card>

            {{-- autocomplete hints stop the browser's password manager from filling these
                 credential fields with the admin's own login email/password. --}}
            <x-admin-card title="Twilio Credentials" class="mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <x-admin.form.input name="twilio_sid"   label="Account SID"   :value="old('twilio_sid',   $settings->get('twilio_sid'))"   placeholder="ACxxxxxxxxxxxxxxxx" autocomplete="off" data-1p-ignore data-lpignore="true" />
                    <x-admin.form.input name="twilio_token" label="Auth Token"     :value="old('twilio_token', $settings->get('twilio_token'))"  type="password" placeholder="••••••••" autocomplete="new-password" data-1p-ignore data-lpignore="true" />
                    <x-admin.form.input name="twilio_from"  label="From Number"    :value="old('twilio_from',  $settings->get('twilio_from'))"   placeholder="+1234567890" autocomplete="off" data-1p-ignore data-lpignore="true" />
                </div>
            </x-admin-card>

            <x-admin-card title="Vonage Credentials" class="mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <x-admin.form.input name="vonage_api_key"    label="API Key"    :value="old('vonage_api_key',    $settings->get('vonage_api_key'))"    placeholder="abcd1234" autocomplete="off" data-1p-ignore data-lpignore="true" />
                    <x-admin.form.input name="vonage_api_secret" label="API Secret" :value="old('vonage_api_secret', $settings->get('vonage_api_secret'))"  type="password" placeholder="••••••••" autocomplete="new-password" data-1p-ignore data-lpignore="true" />
                    <x-admin.form.input name="vonage_from"       label="From Name / Number" :value="old('vonage_from', $settings->get('vonage_from'))"      placeholder="Ready" autocomplete="off" data-1p-ignore data-lpignore="true" />
                </div>
            </x-admin-card>

            <x-admin-card title="Zan Communications Credentials" class="mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <x-admin.form.input name="zan_api_key"   label="API Key"   :value="old('zan_api_key',   $settings->get('zan_api_key'))"   type="password" placeholder="••••••••" autocomplete="new-password" data-1p-ignore data-lpignore="true" />
                    <x-admin.form.input name="zan_sender_id" label="Sender ID" :value="old('zan_sender_id', $settings->get('zan_sender_id'))" placeholder="Approved sender ID" autocomplete="off" data-1p-ignore data-lpignore="true" />
                </div>
            </x-admin-card>

            <x-admin-card title="Push Notifications (Firebase)" class="mt-6">
                <div class="rounded-lg bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-800 p-3 mb-4 text-xs text-indigo-700 dark:text-indigo-300 leading-relaxed">
                    Push uses the <strong>Firebase Cloud Messaging HTTP v1 API</strong>. In the Firebase console open
                    <em>Project settings → Service accounts → Generate new private key</em>, then paste the entire
                    downloaded JSON file below. Leave this blank to disable push notifications.
                    <br><br>
                    The old FCM "server key" is no longer accepted by Google and is not used.
                </div>
                <x-admin.form.textarea name="firebase_service_account" label="Service Account JSON" rows="6"
                    autocomplete="off" spellcheck="false" data-1p-ignore data-lpignore="true"
                    class="font-mono"
                    placeholder='{"type":"service_account","project_id":"...","private_key":"-----BEGIN PRIVATE KEY-----\n...","client_email":"..."}'>{{ old('firebase_service_account', $settings->get('firebase_service_account')) }}</x-admin.form.textarea>
            </x-admin-card>

        {{-- ── Broadcasting (Pusher) ─────────────────────────────────────────── --}}
        @elseif ($tab === 'broadcasting')
            <x-admin-card title="Broadcast Driver">
                <x-admin.form.select name="broadcast_driver" label="Active Driver"
                    hint="Powers real-time features like live chat message delivery. 'Log' writes broadcast events to the application log instead of pushing them to connected browsers — use it for local development only.">
                    <option value="log"    @selected(old('broadcast_driver', $settings->get('broadcast_driver', 'log')) === 'log')>Log (development only)</option>
                    <option value="pusher" @selected(old('broadcast_driver', $settings->get('broadcast_driver')) === 'pusher')>Pusher</option>
                </x-admin.form.select>
            </x-admin-card>

            {{-- autocomplete hints stop the browser's password manager from filling these
                 credential fields with the admin's own login email/password. --}}
            <x-admin-card title="Pusher Credentials" class="mt-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <x-admin.form.input name="pusher_app_id" label="App ID" :value="old('pusher_app_id', $settings->get('pusher_app_id'))" placeholder="1234567" autocomplete="off" data-1p-ignore data-lpignore="true" />
                    <x-admin.form.input name="pusher_key" label="Key" :value="old('pusher_key', $settings->get('pusher_key'))" placeholder="a1b2c3d4e5f6g7h8i9j0" autocomplete="off" data-1p-ignore data-lpignore="true" />
                    <x-admin.form.input name="pusher_secret" label="Secret" type="password"
                        :hint="!empty($secretConfigured['pusher_secret']) ? 'A secret is saved. Leave blank to keep it.' : 'Your Pusher app secret.'"
                        placeholder="••••••••" autocomplete="new-password" data-1p-ignore data-lpignore="true" />
                    <x-admin.form.input name="pusher_cluster" label="Cluster" :value="old('pusher_cluster', $settings->get('pusher_cluster', 'mt1'))" placeholder="mt1" autocomplete="off" />
                </div>
            </x-admin-card>

        @endif

        @if ($tab !== 'templates')
            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-5 py-2 bg-brand hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-brand/30 transition">
                    Save Settings
                </button>
            </div>
        @endif
    </form>
    @endif
</x-admin-layout>
