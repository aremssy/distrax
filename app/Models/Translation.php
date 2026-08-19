<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['language_id', 'group', 'key', 'value'])]
class Translation extends Model
{
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
