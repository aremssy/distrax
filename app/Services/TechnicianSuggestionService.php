<?php

namespace App\Services;

use App\Models\Technician;
use App\Models\Zone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TechnicianSuggestionService
{
    public function forZone(Zone $zone, ?int $categoryId = null, int $perPage = 15): LengthAwarePaginator
    {
        return Technician::query()
            ->with(['user:id,name,email,phone', 'category:id,name,commission_rate', 'zone:id,name,parent_id'])
            ->availableForZone($zone)
            ->when($categoryId, fn ($query) => $query->where('technician_category_id', $categoryId))
            ->orderByRaw('zone_id = ? desc', [$zone->id])
            ->orderByDesc('rating')
            ->orderByDesc('experience_years')
            ->paginate($perPage);
    }
}
