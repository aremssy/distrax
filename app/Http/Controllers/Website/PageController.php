<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\Faq;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = CmsPage::published()->where('slug', $slug)->firstOrFail();

        return view('website.pages.cms_page', compact('page'));
    }

    public function faq(Request $request): View
    {
        $allFaqs = Faq::active()->ordered()->get();

        // toBase(): grouping yields collections-of-models, and Eloquent\Collection::only()
        // would call getKey() on each group (a Collection, not a Model) and blow up.
        $grouped = $allFaqs->toBase()->groupBy(fn (Faq $faq): string => $faq->category ?: 'General');

        $categoryCounts = $grouped->map->count();
        $search = trim((string) $request->string('q'));

        $activeCategory = $request->string('category')->value();
        if (! $categoryCounts->has($activeCategory)) {
            $activeCategory = $categoryCounts->keys()->first();
        }

        $faqs = $search !== ''
            ? $allFaqs->filter(fn (Faq $faq): bool => Str::contains($faq->question.' '.$faq->answer, $search, ignoreCase: true))
                ->groupBy(fn (Faq $faq): string => $faq->category ?: 'General')
            : $grouped->only(array_filter([$activeCategory]));

        return view('website.pages.faq', [
            'faqs' => $faqs,
            'allFaqs' => $allFaqs,
            'categoryCounts' => $categoryCounts,
            'activeCategory' => $activeCategory,
            'search' => $search,
        ]);
    }
}
