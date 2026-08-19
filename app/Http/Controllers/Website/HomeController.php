<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Project;
use App\Models\PropertyListing;
use App\Models\Testimonial;
use App\Services\HomePageService;
use App\Support\HomeRepository;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __construct(
        private HomePageService $homePageService,
        private HomeRepository $homeRepository,
    ) {}

    public function __invoke(): View
    {
        $listingTypeCounts = $this->homeRepository->listingTypeCounts();

        $testimonials = Testimonial::active()->orderBy('sort_order')->limit(6)->get();

        $projects = Project::with(['zone:id,name', 'category:id,name,image'])->orderByDesc('is_featured')->orderBy('sort_order')->limit(3)->get();

        $topAgents = $this->homeRepository->topAgents();

        $topAgencies = $this->homeRepository->topAgencies();

        $featuredListings = PropertyListing::query()
            ->with([
                'zone:id,name',
                'owner:id,name,avatar',
                'coverImage:id,property_listing_id,path,disk,is_cover,sort_order',
            ])
            ->withViewerFavorite()
            ->where('status', 'active')
            ->where('is_featured', true)
            ->latest('published_at')
            ->latest('id')
            ->limit(8)
            ->get();

        $latestPosts = Blog::published()
            ->with('category:id,name,slug')
            ->latest('published_at')
            ->limit(4)
            ->get();

        $heroSlides = [
            [
                'image' => home_image('home_hero_1_image', 'assets/images/website/hero-section/hero-1.webp'),
                'title' => __('Find the right property.'),
                'highlight' => __('Right place. Right price.'),
                'subtitle' => __('Discover verified properties for rent, sale or investment. Right choices, trusted by thousands.'),
            ],
            [
                'image' => home_image('home_hero_2_image', 'assets/images/website/hero-section/hero.webp'),
                'title' => __('Verified listings from'),
                'highlight' => __('Trusted agents only.'),
                'subtitle' => __('Every property is checked by our team so you can browse and book with confidence.'),
            ],
            [
                'image' => home_image('home_hero_3_image', 'assets/images/website/flat/flat-1.webp'),
                'title' => __('List your property in'),
                'highlight' => __('Just a few minutes.'),
                'subtitle' => __('Reach thousands of active buyers and tenants by posting your property for free.'),
            ],
        ];

        return view('website.home', [
            ...compact('featuredListings', 'latestPosts', 'listingTypeCounts', 'testimonials', 'projects', 'topAgents', 'topAgencies', 'heroSlides'),
            ...$this->homePageService->heroData(),
        ]);
    }
}
