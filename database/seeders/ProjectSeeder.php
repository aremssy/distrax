<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $zoneIds = Zone::pluck('id')->all();
        $categoryIds = ProjectCategory::where('is_active', true)->orderBy('sort_order')->pluck('id')->values();

        if (! Project::exists() && empty($zoneIds)) {
            $this->command?->warn('ProjectSeeder skipped: no zones found. Run ZoneSeeder first.');

            return;
        }

        if (! Project::exists()) {
            Project::factory()->count(6)->sequence(fn ($sequence) => [
                'zone_id' => $zoneIds[array_rand($zoneIds)],
                'sort_order' => $sequence->index,
            ])->create();
        }

        if ($categoryIds->isEmpty()) {
            $this->command?->warn('ProjectSeeder could not assign categories: no active project categories found.');

            return;
        }

        $index = 0;

        // chunkById (not chunk): the callback writes project_category_id, the very
        // column the whereNull filter uses, so offset paging would skip projects.
        Project::whereNull('project_category_id')
            ->chunkById(200, function ($projects) use ($categoryIds, &$index): void {
                foreach ($projects as $project) {
                    $project->update([
                        'project_category_id' => $categoryIds[$index % $categoryIds->count()],
                    ]);

                    $index++;
                }
            });
    }
}
