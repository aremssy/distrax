<?php

namespace App\Http\Controllers\Api\V1\Communication;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Communication\StoreReportRequest;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ReportController extends ApiController
{
    /**
     * POST /api/v1/reports
     *
     * Submit a report for a user, listing, or review.
     * One report per reporter per entity (subsequent submissions update the reason/details).
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        $user = $request->user();
        $morphClass = StoreReportRequest::REPORTABLE_TYPES[$request->input('reportable_type')];

        // Prevent reporting yourself
        if ($morphClass === User::class && (int) $request->input('reportable_id') === $user->id) {
            return $this->error('You cannot report yourself.', 422);
        }

        $report = Report::updateOrCreate(
            [
                'reporter_id' => $user->id,
                'reportable_type' => (new $morphClass)->getMorphClass(),
                'reportable_id' => (int) $request->input('reportable_id'),
            ],
            [
                'reason' => $request->input('reason'),
                'details' => $request->input('details'),
                'status' => 'pending',
            ]
        );

        $wasCreated = $report->wasRecentlyCreated;

        return $this->success(
            ['report_id' => $report->id],
            $wasCreated ? 'Report submitted. Our team will review it shortly.' : 'Your existing report has been updated.'
        );
    }
}
