<?php

namespace App\Models;

use App\Support\SettingRepository;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'group', 'type', 'is_public'])]
class Setting extends Model
{
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    /**
     * Settings are read on every request through the `setting()` helper and cached
     * forever, so any write — including one that bypasses SettingRepository — must
     * drop the cached payload.
     */
    protected static function booted(): void
    {
        static::saved(fn () => app(SettingRepository::class)->flush());
        static::deleted(fn () => app(SettingRepository::class)->flush());
    }
}
