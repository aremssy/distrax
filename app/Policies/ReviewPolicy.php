<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function create(User $user): bool
    {
        return ! $user->is_blocked;
    }

    public function report(User $user, Review $review): bool
    {
        return $review->reviewer_id !== $user->id;
    }
}
