<?php

namespace Database\Factories;

use App\Models\Inspection;
use App\Models\PropertyListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InspectionFactory extends Factory
{
    protected $model = Inspection::class;

    public function definition(): array
    {
        return [
            'property_listing_id' => PropertyListing::factory(),
            'visit_schedule_id' => null,
            'inspector_id' => User::factory(),
            'type' => 'physical',
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
            'checklist' => null,
            'gps_lat' => null,
            'gps_lng' => null,
            'geodata' => null,
            'summary' => null,
            'report_url' => null,
            'issues' => null,
            'buyer_acknowledged_at' => null,
            'completed_at' => null,
        ];
    }
}
