<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

class CountryWisePhoneNumber implements ValidationRule
{
    private ?string $countryCode;

    private string $errorMessage = 'The phone number is not valid for the selected country.';

    /**
     * @param  string|null  $countryCode  ISO 3166-1 alpha-2 country code (e.g., 'US', 'BD', 'PK')
     *                                    If null, phone number must be in international format.
     */
    public function __construct(?string $countryCode = null)
    {
        $this->countryCode = strtoupper($countryCode ?? '');
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $util = PhoneNumberUtil::getInstance();

            // Parse the phone number with the country code
            $phoneNumber = $util->parse((string) $value, $this->countryCode ?: null);

            // Validate the phone number
            if (! $util->isValidNumber($phoneNumber)) {
                $fail($this->errorMessage);

                return;
            }

            // If a country code is provided, verify the number is for that country
            if ($this->countryCode && $phoneNumber->getCountryCode() !== $util->getCountryCodeForRegion($this->countryCode)) {
                $fail($this->errorMessage);

                return;
            }
        } catch (NumberParseException) {
            $fail($this->errorMessage);
        }
    }
}
