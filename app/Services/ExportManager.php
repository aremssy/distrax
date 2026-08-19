<?php

namespace App\Services;

use App\Exports\GenericSheetExport;
use App\Jobs\ProcessExport;
use App\Models\Export;
use App\Models\User;
use App\Support\Exports\ExportProfile;
use App\Support\Exports\ExportRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Orchestrates the whole export lifecycle: resolving the profile, narrowing the query to
 * the requested scope, deciding whether to run now or on the queue, and writing the file
 * for the chosen format while reporting progress.
 */
class ExportManager
{
    /** @var list<string> */
    public const FORMATS = ['csv', 'xlsx', 'pdf'];

    /** @var list<string> */
    public const SCOPES = ['selected', 'page', 'filtered', 'all'];

    public function __construct(private readonly ExportRegistry $registry) {}

    public function registry(): ExportRegistry
    {
        return $this->registry;
    }

    /**
     * Row counts used by the export dialog to label the scope options.
     *
     * @return array{filtered: int, all: int}
     */
    public function scopeCounts(ExportProfile $profile, Request $request): array
    {
        return [
            'filtered' => (clone $profile->query($request))->toBase()->getCountForPagination(),
            'all' => (clone $profile->query(new Request))->toBase()->getCountForPagination(),
        ];
    }

    /**
     * Create the export record and either process it synchronously (small/precise scopes)
     * or hand it to the queue (large datasets), returning the persisted model.
     *
     * @param  list<string>  $columns
     * @param  list<int>  $ids
     */
    public function start(
        User $user,
        ExportProfile $profile,
        string $format,
        string $scope,
        array $columns,
        Request $request,
        array $ids = [],
    ): Export {
        $total = $this->countFor($profile, $scope, $request, $ids);

        $export = Export::create([
            'user_id' => $user->id,
            'resource' => $profile->key(),
            'format' => $format,
            'scope' => $scope,
            'columns' => array_values($columns),
            'filters' => $request->query(),
            'ids' => $ids ?: null,
            'status' => Export::STATUS_QUEUED,
            'total_rows' => $total,
            'expires_at' => now()->addHours((int) config('exports.retention_hours', 24)),
        ]);

        if ($this->shouldQueue($scope, $total)) {
            ProcessExport::dispatch($export);
        } else {
            $this->process($export);
        }

        return $export->refresh();
    }

    /**
     * Build an in-memory headings + rows table for the given scope, used by the print view.
     * Capped so a "print everything" can't run away.
     *
     * @param  list<string>  $columns
     * @param  list<int>  $ids
     * @return array{headings: list<string>, rows: list<array<int, string|int|float|null>>}
     */
    public function table(ExportProfile $profile, string $scope, Request $request, array $columns, array $ids = [], int $limit = 5000): array
    {
        $labels = $profile->columns();
        $columns = array_values(array_filter($columns, fn (string $key): bool => isset($labels[$key])));

        if ($columns === []) {
            $columns = $profile->defaultColumns();
        }

        $query = $this->scopedQuery($profile, $scope, $request, $ids)->limit($limit);

        $rows = [];
        foreach ($query->get() as $row) {
            $mapped = $profile->map($row);
            $rows[] = array_map(fn (string $key) => $mapped[$key] ?? null, $columns);
        }

        return [
            'headings' => array_map(fn (string $key): string => $labels[$key] ?? $key, $columns),
            'rows' => $rows,
        ];
    }

    /**
     * Validate/normalise a submitted column list against a profile, falling back to the
     * profile defaults when nothing valid was chosen.
     *
     * @param  list<string>  $columns
     * @return list<string>
     */
    public function sanitizeColumns(ExportProfile $profile, array $columns): array
    {
        $available = $profile->columns();
        $columns = array_values(array_filter($columns, fn ($key): bool => is_string($key) && isset($available[$key])));

        return $columns !== [] ? $columns : $profile->defaultColumns();
    }

    /**
     * Generate the file for an export record. Safe to call from the request (sync) or the
     * queue job; any failure is recorded on the record rather than thrown to the caller.
     */
    public function process(Export $export): void
    {
        $profile = $this->registry->resolve($export->resource);

        $export->update([
            'status' => Export::STATUS_PROCESSING,
            'processed_rows' => 0,
        ]);

        try {
            $query = $this->scopedQuery($profile, $export->scope, new Request((array) $export->filters), (array) $export->ids);
            $columns = $export->columns;
            $path = config('exports.directory', 'exports').'/'.$profile->filename().'.'.$export->format;

            $rows = match ($export->format) {
                'xlsx' => $this->writeXlsx($export, $profile, $query, $columns, $path),
                'pdf' => $this->writePdf($export, $profile, $query, $columns, $path),
                default => $this->writeCsv($export, $profile, $query, $columns, $path),
            };

            $export->update([
                'status' => Export::STATUS_COMPLETED,
                'file_path' => $path,
                'file_name' => basename($path),
                'processed_rows' => $rows,
                'total_rows' => $rows,
                'completed_at' => now(),
            ]);
        } catch (Throwable $e) {
            $export->update([
                'status' => Export::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);

            report($e);
        }
    }

    /**
     * Narrow the profile's query to the requested scope.
     *
     * @param  list<int>  $ids
     * @return Builder<covariant \Illuminate\Database\Eloquent\Model>
     */
    private function scopedQuery(ExportProfile $profile, string $scope, Request $request, array $ids): Builder
    {
        return match ($scope) {
            'selected' => $profile->query(new Request)->whereKey($ids ?: [0]),
            'page' => $profile->query($request)->forPage((int) ($request->input('page', 1)), $profile->perPage()),
            'all' => $profile->query(new Request),
            default => $profile->query($request), // filtered
        };
    }

    /**
     * @param  list<int>  $ids
     */
    private function countFor(ExportProfile $profile, string $scope, Request $request, array $ids): int
    {
        return match ($scope) {
            'selected' => count($ids),
            'page' => min($profile->perPage(), (clone $profile->query($request))->toBase()->getCountForPagination()),
            'all' => (clone $profile->query(new Request))->toBase()->getCountForPagination(),
            default => (clone $profile->query($request))->toBase()->getCountForPagination(),
        };
    }

    private function shouldQueue(string $scope, int $total): bool
    {
        if (in_array($scope, ['selected', 'page'], true)) {
            return false;
        }

        return $total > (int) config('exports.queue_threshold', 2000);
    }

    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $columns
     */
    private function writeCsv(Export $export, ExportProfile $profile, Builder $query, array $columns, string $path): int
    {
        $disk = Storage::disk(config('exports.disk', 'local'));
        $disk->makeDirectory(dirname($path));
        $handle = fopen($disk->path($path), 'w');

        $labels = $profile->columns();
        fputcsv($handle, array_map(fn (string $key): string => $labels[$key] ?? $key, $columns));

        $written = 0;

        foreach ($this->stream($query, $export->scope) as $row) {
            $mapped = $profile->map($row);
            fputcsv($handle, array_map(fn (string $key) => $mapped[$key] ?? null, $columns));

            if (++$written % 250 === 0) {
                $export->update(['processed_rows' => $written]);
            }
        }

        fclose($handle);

        return $written;
    }

    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $columns
     */
    private function writeXlsx(Export $export, ExportProfile $profile, Builder $query, array $columns, string $path): int
    {
        Excel::store(new GenericSheetExport($profile, $query, $columns), $path, config('exports.disk', 'local'));

        return (clone $query)->toBase()->getCountForPagination();
    }

    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $columns
     */
    private function writePdf(Export $export, ExportProfile $profile, Builder $query, array $columns, string $path): int
    {
        $labels = $profile->columns();
        $headings = array_map(fn (string $key): string => $labels[$key] ?? $key, $columns);

        $rows = [];
        foreach ($query->limit((int) config('exports.pdf_max_rows', 5000))->get() as $row) {
            $mapped = $profile->map($row);
            $rows[] = array_map(fn (string $key) => $mapped[$key] ?? null, $columns);
        }

        $pdf = Pdf::loadView('admin.exports.pdf', [
            'title' => $profile->title(),
            'headings' => $headings,
            'rows' => $rows,
            'generatedAt' => now(),
        ])->setPaper('a4', count($columns) > 6 ? 'landscape' : 'portrait');

        Storage::disk(config('exports.disk', 'local'))->put($path, $pdf->output());

        return count($rows);
    }

    /**
     * Iterate result rows: small/precise scopes eagerly, large scopes lazily (chunked by
     * primary key) so memory stays flat.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return iterable<Model>
     */
    private function stream(Builder $query, string $scope): iterable
    {
        return in_array($scope, ['selected', 'page'], true)
            ? $query->get()
            : $query->lazy($query->getModel()->getPerPage() ?: 500);
    }
}
