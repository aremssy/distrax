<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        Language::firstOrCreate(
            ['code' => 'en'],
            [
                'name' => 'English',
                'native_name' => 'English',
                'direction' => 'ltr',
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 0,
            ]
        );

        Language::firstOrCreate(
            ['code' => 'ar'],
            [
                'name' => 'Arabic',
                'native_name' => 'العربية',
                'direction' => 'rtl',
                'is_active' => false,
                'is_default' => false,
                'sort_order' => 1,
            ]
        );

        Language::firstOrCreate(
            ['code' => 'bn'],
            [
                'name' => 'Bengali',
                'native_name' => 'বাংলা',
                'direction' => 'ltr',
                'is_active' => false,
                'is_default' => false,
                'sort_order' => 2,
            ]
        );
    }
}
