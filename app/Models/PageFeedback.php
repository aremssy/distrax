<?php

namespace App\Models;

use Database\Factories\PageFeedbackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['page', 'is_helpful', 'user_id', 'visitor_hash'])]
class PageFeedback extends Model
{
    /** @use HasFactory<PageFeedbackFactory> */
    use HasFactory;

    protected $table = 'page_feedback';

    protected function casts(): array
    {
        return [
            'is_helpful' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
