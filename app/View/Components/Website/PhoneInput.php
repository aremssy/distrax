<?php

namespace App\View\Components\Website;

use App\Models\Country;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;

class PhoneInput extends Component
{
    /** @var Collection<int, array{name: string, code: string, phone_code: string, flag: string}> */
    public Collection $countries;

    public string $dialCode;

    public string $localNumber;

    public function __construct(
        public string $name,
        public ?string $value = null,
        public string $label = 'Phone',
        public ?string $id = null,
        public bool $required = false,
        public bool $optional = false,
        public string $placeholder = 'Enter phone number',
    ) {
        $this->id ??= $name;
        $this->countries = $this->activeCountries();
        [$this->dialCode, $this->localNumber] = $this->splitNumber((string) $value);
    }

    /**
     * Active countries as plain arrays, cached across requests since the list is effectively
     * static. Primitives are cached (not Eloquent models) so the payload serialises safely
     * regardless of cache driver.
     *
     * @return Collection<int, array{name: string, code: string, phone_code: string, flag: string}>
     */
    private function activeCountries(): Collection
    {
        $countries = Cache::rememberForever('countries.active', fn (): array => Country::query()
            ->where('is_active', true)
            ->whereNotNull('phone_code')
            ->orderBy('name')
            ->get(['name', 'code', 'phone_code'])
            ->map(fn (Country $country): array => [
                'name' => (string) $country->name,
                'code' => (string) $country->code,
                'phone_code' => (string) $country->phone_code,
                'flag' => $country->flag,
            ])
            ->all());

        return collect($countries);
    }

    /**
     * Split a stored E.164-ish number into [dialCode, localNumber] by longest matching dial code.
     *
     * @return array{0: string, 1: string}
     */
    private function splitNumber(string $value): array
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';
        $defaultIso = strtoupper((string) config('app.default_country', 'BD'));
        $default = data_get($this->countries->firstWhere('code', $defaultIso), 'phone_code')
            ?? data_get($this->countries->first(), 'phone_code')
            ?? '';

        if ($digits === '') {
            return [$default, ''];
        }

        $match = $this->countries
            ->sortByDesc(fn (array $country): int => strlen($country['phone_code']))
            ->first(fn (array $country): bool => $country['phone_code'] !== ''
                && str_starts_with($digits, $country['phone_code']));

        if ($match) {
            return [$match['phone_code'], substr($digits, strlen($match['phone_code']))];
        }

        return [$default, $digits];
    }

    public function render(): View|Closure|string
    {
        return view('components.website.phone-input');
    }
}
