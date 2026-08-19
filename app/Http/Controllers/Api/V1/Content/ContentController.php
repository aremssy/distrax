<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Contact\StoreContactMessageRequest;
use App\Http\Resources\Api\V1\CmsPageResource;
use App\Http\Resources\Api\V1\FaqResource;
use App\Models\CmsPage;
use App\Models\ContactMessage;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Public content endpoints for mobile/other clients: CMS pages
 * (privacy policy, terms of service, about, …), FAQs, and the
 * contact-us submission the "Help & Contact" screen posts to.
 */
class ContentController extends ApiController
{
    /**
     * GET /api/v1/pages/{slug}
     *
     * A single published CMS page by slug. Slugs mirror the website, e.g.
     * `privacy-policy`, `terms-of-service`, `about-us`.
     */
    public function page(string $slug): JsonResponse
    {
        // Cache the *resolved array*, not the Eloquent model — the database cache
        // store has serializable_classes=false and returns models as incomplete
        // objects (which then blow up in the resource). Same gotcha as BlogCategory.
        $data = Cache::remember("api.page.{$slug}", 300, function () use ($slug): ?array {
            $page = CmsPage::published()->where('slug', $slug)->first();

            return $page ? (new CmsPageResource($page))->resolve() : null;
        });

        if ($data === null) {
            return $this->error('Page not found.', 404);
        }

        return $this->success($data);
    }

    /**
     * GET /api/v1/faqs
     *
     * Active FAQs, ordered, grouped into `{category, items[]}` sections.
     */
    public function faqs(): JsonResponse
    {
        $sections = Cache::remember('api.faqs', 300, function (): array {
            return Faq::active()->ordered()->get()
                ->groupBy(fn (Faq $faq): string => $faq->category ?: 'General')
                ->map(fn ($items, string $category): array => [
                    'category' => $category,
                    'items' => FaqResource::collection($items)->resolve(),
                ])
                ->values()
                ->all();
        });

        return $this->success($sections);
    }

    /**
     * POST /api/v1/contact
     *
     * Store a contact-us message (throttled at the route level).
     */
    public function contact(StoreContactMessageRequest $request): JsonResponse
    {
        ContactMessage::create([...$request->validated(), 'status' => 'new']);

        return $this->created(null, "Thanks for reaching out. We'll get back to you shortly.");
    }
}
