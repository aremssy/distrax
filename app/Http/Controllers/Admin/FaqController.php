<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ReordersRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveFaqRequest;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    use ReordersRecords;

    public function index(Request $request): View
    {
        $faqs = Faq::when($request->string('category')->value(), fn ($query, string $category) => $query->where('category', $category))
            ->ordered()
            ->paginate(50);

        $categories = Faq::whereNotNull('category')->distinct()->orderBy('category')->pluck('category');

        return view('admin.faqs.index', compact('faqs', 'categories'));
    }

    public function store(SaveFaqRequest $request): RedirectResponse
    {
        Faq::create([...$this->payload($request), 'sort_order' => $this->nextSortOrder(Faq::class)]);

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ created.');
    }

    public function update(SaveFaqRequest $request, Faq $faq): RedirectResponse
    {
        $faq->update($this->payload($request));

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ updated.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ deleted.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:faqs,id'],
            'offset' => ['sometimes', 'integer', 'min:0'],
        ]);

        $this->persistOrder(Faq::class, $validated['ids'], (int) ($validated['offset'] ?? 0));

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(SaveFaqRequest $request): array
    {
        $data = $request->validated();

        unset($data['sort_order']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
