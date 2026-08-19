<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModerateReviewRequest;
use App\Models\Review;
use App\Services\AuditLogger;
use App\Services\VerifiedReviewGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReviewModerationController extends Controller
{
    public function index(Request $request): View
    {
        $reviews = Review::with(['reviewer:id,name,email', 'reviewable', 'moderator:id,name,email'])
            ->when($request->filled('visible'), fn ($query) => $query->where('is_visible', $request->boolean('visible')))
            ->when($request->filled('verified'), fn ($query) => $query->where('is_verified', $request->boolean('verified')))
            ->when($request->string('search')->value(), fn ($query, string $search) => $query->where(
                fn ($q) => $q->where('body', 'like', "%{$search}%")
                    ->orWhereHas('reviewer', fn ($r) => $r->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
            ))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function moderate(ModerateReviewRequest $request, Review $review, AuditLogger $audit, VerifiedReviewGuard $guard): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($audit, $data, $guard, $review): void {
            match ($data['action']) {
                'verify' => $guard->markVerified($review),
                'hide' => $review->update(['is_visible' => false]),
                'show' => $review->update(['is_visible' => true]),
                'delete' => $review->delete(),
            };

            if (! $review->trashed()) {
                $review->update([
                    'moderated_by' => auth()->id(),
                    'moderation_note' => $data['note'] ?? null,
                    'moderated_at' => now(),
                ]);
            }

            $audit->record('review.'.$data['action'], $review, ['note' => $data['note'] ?? null]);
        });

        return redirect()->route('admin.reviews.index')->with('success', 'Review moderated.');
    }
}
