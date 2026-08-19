<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignTechnicianZoneRequest;
use App\Http\Requests\Admin\StoreTechnicianRequest;
use App\Http\Requests\Admin\UpdateTechnicianRequest;
use App\Models\Technician;
use App\Models\TechnicianCategory;
use App\Models\User;
use App\Models\Zone;
use App\Services\TechnicianSuggestionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class TechnicianController extends Controller
{
    public function index(Request $request): View
    {
        $technicians = Technician::with(['user:id,name,email,phone', 'category:id,name', 'zone:id,name'])
            ->withAvg('reviews', 'rating')
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->integer('category_id'), fn ($query, int $categoryId) => $query->where('technician_category_id', $categoryId))
            ->latest()
            ->paginate(25);

        $categories = TechnicianCategory::orderBy('name')->get(['id', 'name']);

        return view('admin.technicians.index', compact('technicians', 'categories'));
    }

    public function create(): View
    {
        return view('admin.technicians.create', [
            'categories' => $this->categories(),
            'zones' => $this->zones(),
        ]);
    }

    /**
     * Create a profile for an existing user account, matched by email.
     *
     * Admin-created technicians are not auto-approved: whatever status the
     * admin picks is what they get, defaulting to the same 'pending' the
     * public application flow uses.
     */
    public function store(StoreTechnicianRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::query()->where('email', $data['user_email'])->firstOrFail();

        $technician = Technician::create([
            ...Arr::except($data, ['user_email']),
            'user_id' => $user->id,
            'skills' => array_values(array_unique($data['skills'] ?? [])),
            'is_available' => $request->boolean('is_available'),
            'is_verified' => $request->boolean('is_verified'),
        ]);

        return redirect()->route('admin.technicians.show', $technician)->with('success', 'Technician created.');
    }

    public function edit(Technician $technician): View
    {
        return view('admin.technicians.edit', [
            'technician' => $technician->load(['user:id,name,email']),
            'categories' => $this->categories(),
            'zones' => $this->zones(),
        ]);
    }

    public function show(Technician $technician): View
    {
        // Only the five most recent reviews are rendered; the total comes from the count.
        return view('admin.technicians.show', [
            'technician' => $technician
                ->load([
                    'user',
                    'category',
                    'zone',
                    'reviews' => fn ($q) => $q->with('reviewer:id,name,email,avatar')->latest()->limit(5),
                ])
                ->loadCount('reviews'),
        ]);
    }

    public function update(UpdateTechnicianRequest $request, Technician $technician): RedirectResponse
    {
        $data = $request->validated();

        $data['skills'] = array_values(array_unique($data['skills'] ?? []));
        $data['is_available'] = $request->boolean('is_available');
        $data['is_verified'] = $request->boolean('is_verified');

        $technician->update($data);

        return redirect()->route('admin.technicians.show', $technician)->with('success', 'Technician updated.');
    }

    public function approve(Technician $technician): RedirectResponse
    {
        $technician->update([
            'status' => 'active',
            'is_verified' => true,
            'is_available' => true,
        ]);

        return redirect()->back()->with('success', 'Technician approved.');
    }

    public function suspend(Technician $technician): RedirectResponse
    {
        $technician->update([
            'status' => 'suspended',
            'is_available' => false,
        ]);

        return redirect()->back()->with('success', 'Technician suspended.');
    }

    public function assignZone(AssignTechnicianZoneRequest $request, Technician $technician): RedirectResponse
    {
        $technician->update($request->validated());

        return redirect()->back()->with('success', 'Zone assigned.');
    }

    public function suggestions(Request $request, Zone $zone, TechnicianSuggestionService $suggestions): JsonResponse
    {
        return response()->json([
            'technicians' => $suggestions->forZone($zone, $request->integer('category_id') ?: null),
        ]);
    }

    /**
     * @return Collection<int, TechnicianCategory>
     */
    private function categories(): Collection
    {
        return TechnicianCategory::orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return Collection<int, Zone>
     */
    private function zones(): Collection
    {
        return Zone::query()->active()->whereIn('type', ['city', 'area'])->orderBy('name')->get(['id', 'name', 'type']);
    }
}
