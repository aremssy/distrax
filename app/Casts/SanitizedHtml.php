<?php

namespace App\Casts;

use App\Support\HtmlSanitizer;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Runs rich-text attributes through the HTML allowlist on the way into the database, so
 * every write path (admin forms, owner dashboard, API) is sanitized in one place rather
 * than relying on each controller to remember.
 *
 * @implements CastsAttributes<string, string>
 */
class SanitizedHtml implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return app(HtmlSanitizer::class)->clean((string) $value);
    }
}
