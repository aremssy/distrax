<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['property_listing_id', 'url', 'path', 'disk', 'source', 'thumbnail', 'sort_order'])]
class PropertyVideo extends Model
{
    public function listing(): BelongsTo
    {
        return $this->belongsTo(PropertyListing::class, 'property_listing_id');
    }

    /**
     * Playable URL. Uploaded files resolve from their disk — relative for the
     * public disk so the link works regardless of APP_URL — external videos
     * keep their stored link.
     */
    public function resolvedUrl(): string
    {
        if (! $this->path || ! $this->disk) {
            return (string) $this->url;
        }

        return $this->disk === 'public'
            ? '/storage/'.$this->path
            : Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Detect the video source from a URL: youtube, vimeo, upload (direct
     * MP4/WebM/MOV link), or null when unrecognised.
     */
    public static function detectSource(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        return match (true) {
            str_contains($host, 'youtube.com'), str_contains($host, 'youtu.be') => 'youtube',
            str_contains($host, 'vimeo.com') => 'vimeo',
            str_ends_with($path, '.mp4'), str_ends_with($path, '.webm'), str_ends_with($path, '.mov') => 'upload',
            default => null,
        };
    }

    /**
     * Embeddable player URL for YouTube/Vimeo links, or null when the video
     * cannot be embedded (uploads, unrecognised links).
     */
    public function embedUrl(): ?string
    {
        $url = (string) $this->url;

        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{6,20})~i', $url, $matches)) {
            return 'https://www.youtube.com/embed/'.$matches[1];
        }

        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $url, $matches)) {
            return 'https://player.vimeo.com/video/'.$matches[1];
        }

        return null;
    }
}
