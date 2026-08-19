<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'code',
        'numeric_code',
        'phone_code',
        'capital',
        'currency',
        'currency_name',
        'currency_symbol',
        'native',
        'region',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * The Unicode flag emoji derived from the ISO 3166-1 alpha-2 code, e.g. "BD" => "🇧🇩".
     */
    protected function flag(): Attribute
    {
        return Attribute::get(function (): string {
            $code = strtoupper((string) $this->code);

            if (! preg_match('/^[A-Z]{2}$/', $code)) {
                return '';
            }

            return mb_chr(0x1F1E6 + ord($code[0]) - ord('A'))
                .mb_chr(0x1F1E6 + ord($code[1]) - ord('A'));
        });
    }
}
