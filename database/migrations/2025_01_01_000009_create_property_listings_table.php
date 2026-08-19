<?php

use App\Enums\PropertyType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('agency_id')->nullable(); // FK added after agencies table

            // Listing type & title — the allowed values come from the PropertyType enum
            // (single source of truth), so the schema and app never drift.
            $table->enum('type', PropertyType::values())->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('language_tag', 10)->default('en');

            // Pricing — each listing is priced in its own currency (ISO 4217 code).
            $table->unsignedBigInteger('price');
            $table->char('currency_code', 3)->nullable();
            $table->unsignedBigInteger('service_charge')->default(0);
            $table->unsignedTinyInteger('advance_months')->default(0);

            // Property details
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->unsignedSmallInteger('floor')->nullable();
            $table->unsignedSmallInteger('total_floors')->nullable();
            // Integer, not smallint: a smallint caps at 65,535 sqft, which is smaller than
            // many land plots and commercial units.
            $table->unsignedInteger('area_sqft')->nullable();
            $table->boolean('parking')->default(false);
            $table->boolean('furnished')->default(false);
            $table->enum('allowed_for', ['bachelor', 'family', 'both'])->default('both');

            // Utility flags (JSON array of amenities)
            $table->json('utility_flags')->nullable();

            // Address
            $table->string('address')->nullable();
            $table->char('country_code', 2)->nullable()->index(); // ISO 3166-1 alpha-2, for global filtering
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            // Status & visibility
            $table->enum('status', ['pending', 'active', 'rented', 'sold', 'archived', 'rejected'])->default('pending')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_verified')->default(false)->index();
            $table->timestamp('last_freshness_check_at')->nullable();
            $table->timestamp('needs_confirmation_at')->nullable()->index();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamp('published_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['zone_id', 'type', 'status']);
            $table->index('price');
            // Search: WHERE status = 'active' ORDER BY published_at DESC / price.
            $table->index(['status', 'published_at'], 'property_listings_status_published_at_index');
            $table->index(['status', 'price'], 'property_listings_status_price_index');
            // Homepage featured rail.
            $table->index(['status', 'is_featured', 'published_at'], 'property_listings_status_featured_published_index');
            // Owner dashboard + agency profile listing counts.
            $table->index(['owner_id', 'status'], 'property_listings_owner_status_index');
            $table->index(['agency_id', 'status'], 'property_listings_agency_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_listings');
    }
};
