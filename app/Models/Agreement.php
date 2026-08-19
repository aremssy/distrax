<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenancy_id', 'agreement_template_id', 'content', 'status', 'generated_at'])]
class Agreement extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }

    public function tenancy(): BelongsTo
    {
        return $this->belongsTo(Tenancy::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AgreementTemplate::class, 'agreement_template_id');
    }
}
