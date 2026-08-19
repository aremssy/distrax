<?php

namespace App\Support\Exports;

use InvalidArgumentException;

/**
 * Resolves an export profile from its slug. The slug => class map lives in
 * config/exports.php so adding a listing to the export module is one config line.
 */
class ExportRegistry
{
    /**
     * @param  array<string, class-string<ExportProfile>>  $profiles
     */
    public function __construct(private array $profiles) {}

    public function has(string $key): bool
    {
        return isset($this->profiles[$key]);
    }

    public function resolve(string $key): ExportProfile
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException("Unknown export resource [{$key}].");
        }

        return app($this->profiles[$key]);
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->profiles);
    }
}
