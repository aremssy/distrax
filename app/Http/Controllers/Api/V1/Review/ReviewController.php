<?php

namespace App\Http\Controllers\Api\V1\Review;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Review\StoreReviewRequest;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\PropertyListing;
use App\Models\Report;
use App\Models\Review;
use App\Models\Technician;
use App\Models\User;
use App\Services\NotificationDispatcher;
use App\Services\VerifiedReviewGuard;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReviewController extends ApiController
{
    /**
     * GET /api/v1/reviews?reviewable_type=listing&reviewable_id=1
     *
     * Paginated visible reviews for a reviewable entity.
     * Includes avg_rating, total_count, and rating_distribution summary.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'reviewable_type' => ['required', Rule::in(array_keys(StoreReviewRequest::REVIEWABLE_TYPES))],
            'reviewable_id' => ['required', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $modelClass = StoreReviewRequest::REVIEWABLE_TYPES[$request->input('reviewable_type')];
        $morphClass = (new $modelClass)->getMorphClass();
        $reviewableId = (int) $request->input('reviewable_id');
        $perPage = min((int) $request->input('per_page', 15), 50);

        $baseQuery = Review::where('reviewable_type', $morphClass)
            ->where('reviewable_id', $reviewableId)
            ->where('is_visible', true);

        // Summary computed before pagination so counts cover all pages
        $avgRating = round((float) (clone $baseQuery)->avg('rating'), 1);
        $totalCount = (clone $baseQuery)->count();
        $distribution = (clone $baseQuery)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // Fill missing ratings with 0 for a complete 1-5 map
        $ratingDistribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $ratingDistribution[(string) $i] = (int) ($distribution[$i] ?? 0);
        }

        $paginator = (clone $baseQuery)
            ->with('reviewer')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => ReviewResource::collection($paginator)->resolve(),
            'summary' => [
                'avg_rating' => $avgRating,
                'total_count' => $totalCount,
                'rating_distribution' => $ratingDistribution,
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'errors' => null,
        ]);
    }

    /**
     * POST /api/v1/reviews
     *
     * Submit a review for a listing, technician, or user.
     * GUARD: the reviewer must have a completed booking/job with that entity.
     * The unique constraint (reviewer_id, reviewable_type, reviewable_id) prevents duplicates.
     */
    public function store(
        StoreReviewRequest $request,
        VerifiedReviewGuard $guard,
        NotificationDispatcher $dispatcher,
    ): JsonResponse {
        $user = $request->user();

        if (Gate::denies('create', Review::class)) {
            return $this->error('You are not allowed to post reviews.', 403);
        }

        $morphClass = StoreReviewRequest::REVIEWABLE_TYPES[$request->input('reviewable_type')];
        $reviewable = (new $morphClass)->findOrFail($request->integer('reviewable_id'));

        // Prevent self-review
        if ($reviewable instanceof User && $reviewable->id === $user->id) {
            return $this->error('You cannot review yourself.', 422);
        }

        if ($reviewable instanceof PropertyListing && $reviewable->owner_id === $user->id) {
            return $this->error('You cannot review your own listing.', 422);
        }

        if ($reviewable instanceof Technician && $reviewable->user_id === $user->id) {
            return $this->error('You cannot review your own technician profile.', 422);
        }

        // Verified-only guard
        if (! $guard->canReview($user, $reviewable)) {
            return $this->error(
                'You can only leave a review after a completed rental, booking, or job with this entity.',
                403
            );
        }

        try {
            $review = Review::create([
                'reviewer_id' => $user->id,
                'reviewable_type' => $reviewable->getMorphClass(),
                'reviewable_id' => $reviewable->id,
                'rating' => $request->integer('rating'),
                'body' => $request->input('body'),
                'is_verified' => true,
                'is_visible' => true,
            ]);
        } catch (UniqueConstraintViolationException) {
            return $this->error('You have already reviewed this entity.', 409);
        }

        $review->load('reviewer');

        // Notify the entity's owner about the new review
        $this->notifyReviewTarget($reviewable, $user, $review, $dispatcher);

        return $this->created(new ReviewResource($review), 'Review submitted successfully.');
    }

    /**
     * POST /api/v1/reviews/{review}/report
     *
     * Flag a review as abusive. Delegates to the existing Report system
     * (reportable_type=review is already supported by StoreReportRequest).
     * This is a convenience alias so the client doesn't need the generic report endpoint.
     */
    public function report(Request $request, Review $review): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', Rule::in([
                'spam', 'inappropriate_content', 'fake_listing',
                'scam', 'wrong_information', 'harassment', 'other',
            ])],
            'details' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        if (Gate::denies('report', $review)) {
            return $this->error('You cannot report your own review.', 422);
        }

        $report = Report::updateOrCreate(
            [
                'reporter_id' => $user->id,
                'reportable_type' => $review->getMorphClass(),
                'reportable_id' => $review->id,
            ],
            [
                'reason' => $request->input('reason'),
                'details' => $request->input('details'),
                'status' => 'pending',
            ]
        );

        return $this->success(
            ['report_id' => $report->id],
            $report->wasRecentlyCreated
                ? 'Review reported. Our team will investigate.'
                : 'Your existing report has been updated.'
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function notifyReviewTarget(
        mixed $reviewable,
        User $reviewer,
        Review $review,
        NotificationDispatcher $dispatcher,
    ): void {
        $recipient = match (true) {
            $reviewable instanceof PropertyListing => User::find($reviewable->owner_id),
            $reviewable instanceof Technician => User::find($reviewable->user_id),
            $reviewable instanceof User => $reviewable,
            default => null,
        };

        if (! $recipient || $recipient->id === $reviewer->id) {
            return;
        }

        $dispatcher->send(
            user: $recipient,
            type: 'review_posted',
            title: 'New review from '.$reviewer->name,
            body: $review->body ? '"'.mb_strimwidth($review->body, 0, 100, '…').'"' : 'Rating: '.$review->rating.'/5',
            data: ['review_id' => $review->id, 'rating' => $review->rating],
            notifiable: $review,
        );
    }
}
