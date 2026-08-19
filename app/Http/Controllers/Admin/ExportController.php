<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Export;
use App\Services\ExportManager;
use App\Support\Exports\ExportProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(private readonly ExportManager $manager) {}

    /**
     * Metadata the export dialog needs: available columns, defaults, and per-scope counts
     * for the current filter set.
     */
    public function options(Request $request, string $resource): JsonResponse
    {
        $profile = $this->authorizedProfile($request, $resource);
        $counts = $this->manager->scopeCounts($profile, $request);

        return response()->json([
            'title' => $profile->title(),
            'columns' => $profile->columns(),
            'default_columns' => $profile->defaultColumns(),
            'formats' => ExportManager::FORMATS,
            'counts' => $counts,
        ]);
    }

    /**
     * Kick off an export. Small scopes complete synchronously and come back "completed"
     * with a download URL; large ones return "queued" with a poll URL.
     */
    public function store(Request $request, string $resource): JsonResponse
    {
        $profile = $this->authorizedProfile($request, $resource);

        $validated = $request->validate([
            'format' => 'required|in:'.implode(',', ExportManager::FORMATS),
            'scope' => 'required|in:'.implode(',', ExportManager::SCOPES),
            'columns' => 'array',
            'columns.*' => 'string',
            'ids' => 'array',
            'ids.*' => 'integer',
        ]);

        $export = $this->manager->start(
            user: $request->user(),
            profile: $profile,
            format: $validated['format'],
            scope: $validated['scope'],
            columns: $this->manager->sanitizeColumns($profile, $validated['columns'] ?? []),
            request: $request,
            ids: array_map('intval', $validated['ids'] ?? []),
        );

        return response()->json($this->status($export));
    }

    /**
     * Poll a queued export's progress.
     */
    public function progress(Request $request, Export $export): JsonResponse
    {
        $this->authorizeOwner($request, $export);

        return response()->json($this->status($export));
    }

    /**
     * Stream a completed export file and count the download.
     */
    public function download(Request $request, Export $export): StreamedResponse
    {
        $this->authorizeOwner($request, $export);

        abort_unless($export->isCompleted() && $export->file_path, 404);

        $disk = Storage::disk(config('exports.disk', 'local'));
        abort_unless($disk->exists($export->file_path), 410, 'This export has expired.');

        $export->increment('download_count');

        return $disk->download($export->file_path, $export->file_name);
    }

    /**
     * Render a printable table for the current scope (opened in a new tab, auto-prints).
     */
    public function print(Request $request, string $resource): View
    {
        $profile = $this->authorizedProfile($request, $resource);

        $columns = $this->manager->sanitizeColumns($profile, (array) $request->input('columns', []));
        $scope = in_array($request->input('scope'), ExportManager::SCOPES, true) ? $request->input('scope') : 'filtered';

        $table = $this->manager->table(
            profile: $profile,
            scope: $scope,
            request: $request,
            columns: $columns,
            ids: array_map('intval', (array) $request->input('ids', [])),
        );

        return view('admin.exports.print', [
            'title' => $profile->title(),
            'headings' => $table['headings'],
            'rows' => $table['rows'],
            'generatedAt' => now(),
        ]);
    }

    /**
     * Download history for the current admin.
     */
    public function index(Request $request): View
    {
        $exports = Export::forUser($request->user()->id)
            ->latest()
            ->paginate(20);

        return view('admin.exports.index', compact('exports'));
    }

    public function destroy(Request $request, Export $export): RedirectResponse
    {
        $this->authorizeOwner($request, $export);

        if ($export->file_path) {
            Storage::disk(config('exports.disk', 'local'))->delete($export->file_path);
        }

        $export->delete();

        return back()->with('success', 'Export deleted.');
    }

    private function authorizedProfile(Request $request, string $resource): ExportProfile
    {
        abort_unless($this->manager->registry()->has($resource), 404);

        $profile = $this->manager->registry()->resolve($resource);

        abort_unless($request->user()?->can($profile->permission()), 403);

        return $profile;
    }

    private function authorizeOwner(Request $request, Export $export): void
    {
        abort_unless($export->user_id === $request->user()?->id, 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function status(Export $export): array
    {
        return [
            'id' => $export->id,
            'status' => $export->status,
            'progress' => $export->progressPercent(),
            'total_rows' => $export->total_rows,
            'processed_rows' => $export->processed_rows,
            'error' => $export->error,
            'poll_url' => route('admin.exports.progress', $export),
            'download_url' => $export->isCompleted() ? route('admin.exports.download', $export) : null,
        ];
    }
}
