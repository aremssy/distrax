<?php

namespace App\Console\Commands;

use App\Services\SitemapGenerator;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate the sitemap.xml file';

    public function handle(SitemapGenerator $sitemap): int
    {
        $this->components->task('Generating sitemap', fn () => $sitemap->generate());

        $this->components->info('Sitemap generated successfully.');

        return self::SUCCESS;
    }
}
