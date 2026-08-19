<?php

namespace Database\Seeders;

use App\Models\ProjectCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ProjectCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Residential', 'slug' => 'residential', 'description' => 'Apartments, condominiums, and residential communities.', 'source_image' => 'assets/images/website/flat/flat-1.webp'],
            ['name' => 'Commercial', 'slug' => 'commercial', 'description' => 'Office, retail, and other commercial developments.', 'source_image' => 'assets/images/website/flat/flat-2.webp'],
            ['name' => 'Mixed-Use', 'slug' => 'mixed-use', 'description' => 'Developments combining residential and commercial spaces.', 'source_image' => 'assets/images/website/flat/flat-3.webp'],
            ['name' => 'Luxury', 'slug' => 'luxury', 'description' => 'Premium projects with high-end amenities and finishes.', 'source_image' => 'assets/images/website/flat/flat-4.webp'],
            ['name' => 'Land Development', 'slug' => 'land-development', 'description' => 'Planned plots, communities, and land development projects.', 'source_image' => 'assets/images/website/flat/flat-5.webp'],
        ];

        foreach ($categories as $sortOrder => $data) {
            $category = ProjectCategory::firstOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ],
            );

            $sourceImage = public_path($data['source_image']);

            if (! $category->image && File::exists($sourceImage)) {
                $imagePath = 'project-categories/'.$data['slug'].'.webp';

                Storage::disk('public')->put($imagePath, File::get($sourceImage));
                $category->update(['image' => $imagePath]);
            }
        }
    }
}
