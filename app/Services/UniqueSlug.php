<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UniqueSlug
{
    /**
     * Generate a slug unique within the model's table, appending -2, -3… on
     * collision. Checks without global scopes so soft-deleted rows (which still
     * occupy the DB unique index) count as collisions. Falls back to $fallback
     * when the source slugifies to nothing (e.g. a fully non-Latin title).
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function make(string $modelClass, string $source, string $fallback, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: $fallback;
        $slug = $base;
        $suffix = 2;

        while (
            $modelClass::query()
                ->withoutGlobalScopes()
                ->when($ignoreId !== null, fn ($query) => $query->where((new $modelClass)->getKeyName(), '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
