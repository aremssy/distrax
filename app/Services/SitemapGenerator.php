<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\CmsPage;
use App\Models\PropertyListing;
use App\Models\Zone;
use Illuminate\Support\Facades\Storage;

class SitemapGenerator
{
    public function generate(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        $xml .= $this->staticUrl(url('/'), '1.0', 'daily');

        foreach (CmsPage::published()->get(['slug', 'updated_at']) as $page) {
            $xml .= $this->staticUrl(url("/page/{$page->slug}"), '0.8', 'weekly', $page->updated_at);
        }

        // Blog posts and listings are unbounded — stream them one row at a time
        // rather than hydrating the whole table. Both loops are read-only and
        // touch no relationships, so cursor() is safe (no eager loading needed).
        foreach (Blog::published()->select(['slug', 'updated_at'])->cursor() as $post) {
            $xml .= $this->staticUrl(url("/blog/{$post->slug}"), '0.7', 'weekly', $post->updated_at);
        }

        $listings = PropertyListing::where('status', 'active')
            ->select(['id', 'slug', 'updated_at'])
            ->cursor();

        foreach ($listings as $listing) {
            $xml .= $this->staticUrl(route('properties.show', $listing), '0.6', 'daily', $listing->updated_at);
        }

        $zones = Zone::where('is_active', true)
            ->where('type', 'area')
            ->get(['slug', 'updated_at']);

        foreach ($zones as $zone) {
            $xml .= $this->staticUrl(url("/rent/{$zone->slug}"), '0.5', 'weekly', $zone->updated_at);
        }

        $xml .= $this->staticUrl(url('/about'), '0.4', 'monthly');
        $xml .= $this->staticUrl(url('/contact'), '0.4', 'monthly');
        $xml .= $this->staticUrl(url('/faq'), '0.4', 'monthly');
        $xml .= $this->staticUrl(url('/terms'), '0.3', 'monthly');
        $xml .= $this->staticUrl(url('/privacy'), '0.3', 'monthly');

        $xml .= '</urlset>';

        Storage::disk('public')->put('sitemap.xml', $xml);

        return $xml;
    }

    private function staticUrl(string $loc, string $priority = '0.5', string $changefreq = 'weekly', ?\DateTimeInterface $lastmod = null): string
    {
        $url = "  <url>\n";
        $url .= '    <loc>'.e($loc)."</loc>\n";

        if ($lastmod) {
            $url .= '    <lastmod>'.$lastmod->format('Y-m-d')."</lastmod>\n";
        }

        $url .= "    <changefreq>{$changefreq}</changefreq>\n";
        $url .= "    <priority>{$priority}</priority>\n";
        $url .= "  </url>\n";

        return $url;
    }
}
