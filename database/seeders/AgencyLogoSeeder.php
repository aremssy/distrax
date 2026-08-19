<?php

namespace Database\Seeders;

use App\Models\Agency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AgencyLogoSeeder extends Seeder
{
    /** @var list<string> */
    private const LOGOS = [
        'assets/images/website/agencies/logo-dreamhomes.svg',
        'assets/images/website/agencies/logo-estateworks.svg',
        'assets/images/website/agencies/logo-realtyclass.svg',
        'assets/images/website/agencies/logo-urbancode.svg',
    ];

    public function run(): void
    {
        $agencies = Agency::query()
            ->where('status', 'active')
            ->whereNull('logo')
            ->orderByDesc('is_verified')
            ->orderByDesc('rating')
            ->orderBy('id')
            ->get(['id']);

        foreach ($agencies as $index => $agency) {
            $sourcePath = self::LOGOS[$index % count(self::LOGOS)];
            $logoPath = 'agencies/seeded/'.File::basename($sourcePath);

            Storage::disk('public')->put(
                $logoPath,
                File::get(public_path($sourcePath)),
            );

            $agency->update(['logo' => $logoPath]);
        }
    }
}
