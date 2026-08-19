<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['owner_id', 'name', 'body', 'is_default'])]
class AgreementTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(Agreement::class);
    }

    /**
     * @param  array<string, string|int|float|null>  $variables
     */
    public function render(array $variables): string
    {
        $replacements = [];

        foreach ($variables as $key => $value) {
            $replacements['{{'.$key.'}}'] = (string) $value;
        }

        return strtr($this->body, $replacements);
    }
}
