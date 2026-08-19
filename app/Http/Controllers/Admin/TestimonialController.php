<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ReordersRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveTestimonialRequest;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    use ReordersRecords;

    public function index(Request $request): View
    {
        $testimonials = Testimonial::query()
            ->when($request->boolean('active'), fn ($query) => $query->where('is_active', true))
            ->orderBy('sort_order')
            ->latest()
            ->paginate(25);

        return view('admin.testimonials.index', compact('testimonials'));
    }

    public function store(SaveTestimonialRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['avatar'] = $request->file('avatar')?->store('testimonials', 'public');
        $data['sort_order'] = $this->nextSortOrder(Testimonial::class);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active', true);

        Testimonial::create($data);

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial created.');
    }

    public function show(Testimonial $testimonial): View
    {
        return view('admin.testimonials.show', compact('testimonial'));
    }

    public function update(SaveTestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $data = $request->validated();

        $data['sort_order'] = $data['sort_order'] ?? $testimonial->sort_order;
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active', $testimonial->is_active);

        if ($request->hasFile('avatar')) {
            $oldImage = $testimonial->avatar;
            $data['avatar'] = $request->file('avatar')->store('testimonials', 'public');
        } else {
            unset($data['avatar']);
        }

        $testimonial->update($data);

        if (isset($oldImage) && $oldImage && ! str_starts_with($oldImage, 'http')) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('admin.testimonials.show', $testimonial)->with('success', 'Testimonial updated.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial deleted.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:testimonials,id'],
            'offset' => ['sometimes', 'integer', 'min:0'],
        ]);

        $this->persistOrder(Testimonial::class, $validated['ids'], (int) ($validated['offset'] ?? 0));

        return response()->json(['ok' => true]);
    }
}
