<?php

namespace App\Support\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Describes how one resource (users, listings, payments, …) is exported. Implementations
 * live in app/Support/Exports/Profiles and are registered in config/exports.php, so the
 * same reusable export UI, controller, and queue job serve every listing page.
 */
interface ExportProfile
{
    /**
     * Stable slug used in routes and the exports table (e.g. "users").
     */
    public function key(): string;

    /**
     * Permission a user must hold to run this export.
     */
    public function permission(): string;

    /**
     * Human title, shown on the PDF/print header and in download history.
     */
    public function title(): string;

    /**
     * Base file name (no extension), e.g. "users-2026-07-19-120000".
     */
    public function filename(): string;

    /**
     * All exportable columns as an ordered map of key => label.
     *
     * @return array<string, string>
     */
    public function columns(): array;

    /**
     * Column keys selected by default in the export dialog.
     *
     * @return list<string>
     */
    public function defaultColumns(): array;

    /**
     * The base query for the resource, with the given request's filters applied. An empty
     * request (scope = "all") therefore yields every record.
     *
     * @return Builder<covariant Model>
     */
    public function query(Request $request): Builder;

    /**
     * Rows returned by query() are loaded in chunks of this size.
     */
    public function chunkSize(): int;

    /**
     * Page size used to reproduce a single visible page (scope = "page").
     */
    public function perPage(): int;

    /**
     * Map one model to a full column key => scalar value row. The manager then keeps only
     * the columns the user selected.
     *
     * @return array<string, string|int|float|null>
     */
    public function map(Model $row): array;
}
