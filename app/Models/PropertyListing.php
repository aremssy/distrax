<?php

namespace App\Models;

use App\Casts\SanitizedHtml;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

#[Fillable([
    'zone_id', 'owner_id', 'agency_id', 'type', 'title', 'slug', 'description', 'language_tag',
    'price', 'currency_code', 'service_charge', 'advance_months', 'is_negotiable', 'bedrooms', 'bathrooms', 'floor',
    'total_floors', 'area_sqft', 'parking', 'furnished', 'allowed_for', 'utility_flags',
    'address', 'country_code', 'lat', 'lng', 'status', 'is_featured', 'is_verified',
    'last_freshness_check_at', 'needs_confirmation_at', 'published_at',
])]
class PropertyListing extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (PropertyListing $listing): void {
            if (empty($listing->slug)) {
                $listing->slug = self::uniqueSlug(Str::slug($listing->title) ?: 'listing');
            }

            // A listing is always priced in a concrete currency; fall back to the
            // platform base so legacy/admin create paths never persist a null.
            if (empty($listing->currency_code)) {
                $listing->currency_code = Currency::defaultActive()?->code ?? 'USD';
            }
        });

        static::updating(function (PropertyListing $listing): void {
            if ($listing->isDirty('title') && ! $listing->isDirty('slug')) {
                $listing->slug = self::uniqueSlug(Str::slug($listing->title) ?: 'listing', $listing->id);
            }
        });
    }

    private static function uniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = $base;
        $i = 1;

        while (self::withTrashed()->where('slug', $slug)->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    protected function casts(): array
    {
        return [
            'description' => SanitizedHtml::class,
            'utility_flags' => 'array',
            'parking' => 'boolean',
            'furnished' => 'boolean',
            'is_negotiable' => 'boolean',
            'is_featured' => 'boolean',
            'is_verified' => 'boolean',
            'last_freshness_check_at' => 'datetime',
            'needs_confirmation_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class, 'property_listing_id')->orderBy('sort_order');
    }

    public function coverImage(): HasMany
    {
        return $this->hasMany(PropertyImage::class, 'property_listing_id')->where('is_cover', true);
    }

    /** Photos shown in the gallery — floor plans are excluded and rendered separately. */
    public function galleryImages(): HasMany
    {
        return $this->hasMany(PropertyImage::class, 'property_listing_id')
            ->where('is_floor_plan', false)
            ->orderByDesc('is_cover')
            ->orderBy('sort_order');
    }

    public function floorPlanImages(): HasMany
    {
        return $this->hasMany(PropertyImage::class, 'property_listing_id')
            ->where('is_floor_plan', true)
            ->orderBy('sort_order');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(PropertyVideo::class, 'property_listing_id')->orderBy('sort_order');
    }

    public function customFieldValues(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function hotelBookings(): HasMany
    {
        return $this->hasMany(HotelBooking::class);
    }

    public function availabilityCalendar(): HasMany
    {
        return $this->hasMany(AvailabilityCalendar::class);
    }

    public function visitSchedules(): HasMany
    {
        return $this->hasMany(VisitSchedule::class);
    }

    public function contactReveals(): HasMany
    {
        return $this->hasMany(ContactReveal::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    /** Flag each listing with an `is_favorited` attribute for the viewing user, without N+1 queries. */
    public function scopeWithViewerFavorite(Builder $query, ?User $viewer = null): Builder
    {
        $viewer ??= auth()->user();

        return $query->when($viewer, fn (Builder $query): Builder => $query->withExists([
            'favorites as is_favorited' => fn (Builder $favorites) => $favorites->where('user_id', $viewer->id),
        ]));
    }

    /** Active listings that haven't had a freshness check within the configured window. */
    public function scopeStale(Builder $query): Builder
    {
        $days = (int) setting('listing_freshness_days', 30);

        return $query->where('status', 'active')
            ->where(function (Builder $q) use ($days): void {
                $q->whereNull('last_freshness_check_at')
                    ->orWhere('last_freshness_check_at', '<', now()->subDays($days));
            });
    }

    /** Apply request filters (type, status, zone, featured, verified, stale, search). */
    public function scopeFilter(Builder $query, Request $request): Builder
    {
        if ($type = $request->string('type')->value()) {
            $query->where('type', $type);
        }
        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }
        if ($zoneId = $request->integer('zone_id')) {
            $query->where('zone_id', $zoneId);
        }
        if ($ownerId = $request->integer('owner_id')) {
            $query->where('owner_id', $ownerId);
        }
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }
        if ($request->boolean('verified')) {
            $query->where('is_verified', true);
        }
        if ($request->boolean('stale')) {
            $query->stale();
        }
        if ($search = $request->string('search')->value()) {
            $query->where(fn (Builder $q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%")
            );
        }

        return $query;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isStale(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $days = (int) setting('listing_freshness_days', 30);

        return is_null($this->last_freshness_check_at)
            || $this->last_freshness_check_at->lt(now()->subDays($days));
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'active' => 'green',
            'pending' => 'yellow',
            'archived' => 'gray',
            'rejected' => 'red',
            'rented' => 'blue',
            'sold' => 'indigo',
            default => 'gray',
        };
    }

    public function typeColor(): string
    {
        return match ($this->type) {
            'rent' => 'blue',
            'sale' => 'green',
            'hotel' => 'purple',
            'mess' => 'yellow',
            'land' => 'indigo',
            'commercial' => 'teal',
            'office' => 'cyan',
            'room' => 'pink',
            'vacation' => 'orange',
            'parking' => 'slate',
            default => 'gray',
        };
    }
}
