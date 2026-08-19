<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActionReportRequest;
use App\Models\Block;
use App\Models\PropertyListing;
use App\Models\Report;
use App\Models\Review;
use App\Models\Technician;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ModerationReportController extends Controller
{
    /**
     * Friendly filter keys mapped to the classes stored in `reportable_type`.
     *
     * @var array<string, class-string>
     */
    private const REPORTABLE_TYPES = [
        'listing' => PropertyListing::class,
        'review' => Review::class,
        'user' => User::class,
        'technician' => Technician::class,
    ];

    public function index(Request $request): View
    {
        $reports = Report::with(['reporter:id,name,email', 'reviewer:id,name,email', 'reportable'])
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when(
                self::REPORTABLE_TYPES[$request->string('type')->value()] ?? null,
                fn ($query, string $class) => $query->where('reportable_type', $class)
            )
            ->when($request->string('search')->value(), fn ($query, string $search) => $query->where(
                fn ($q) => $q->where('reason', 'like', "%{$search}%")
                    ->orWhereHas('reporter', fn ($r) => $r->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
            ))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $types = array_keys(self::REPORTABLE_TYPES);

        return view('admin.reports.index', compact('reports', 'types'));
    }

    public function action(ActionReportRequest $request, Report $report, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($audit, $data, $report): void {
            match ($data['action']) {
                'block' => $this->blockReportSubject($report),
                'remove' => $this->removeReportable($report),
                default => null,
            };

            $report->update([
                'status' => $data['action'] === 'dismiss' ? 'dismissed' : 'actioned',
                'admin_note' => $data['admin_note'] ?? $data['action'],
                'reviewed_by' => auth()->id(),
            ]);

            $audit->record('report.'.$data['action'], $report, ['admin_note' => $data['admin_note'] ?? null]);
        });

        return redirect()->route('admin.reports.index')->with('success', 'Report actioned.');
    }

    private function blockReportSubject(Report $report): void
    {
        $user = $report->reportable instanceof User
            ? $report->reportable
            : ($report->reportable instanceof Review ? $report->reportable->reviewer : null);

        if (! $user instanceof User) {
            return;
        }

        $user->update(['is_blocked' => true]);
        Block::firstOrCreate([
            'blocker_id' => auth()->id(),
            'blocked_id' => $user->id,
        ]);
    }

    private function removeReportable(Report $report): void
    {
        if ($report->reportable instanceof Review) {
            $report->reportable->update(['is_visible' => false, 'moderated_by' => auth()->id(), 'moderated_at' => now()]);
        }

        if ($report->reportable instanceof PropertyListing) {
            $report->reportable->update(['status' => 'archived']);
        }
    }
}
