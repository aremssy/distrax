<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\StoreTechnicianApplicationRequest;
use App\Models\Technician;
use App\Models\TechnicianCategory;
use App\Models\Zone;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TechnicianApplicationController extends Controller
{
    /**
     * Show the "become a technician" application form.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if ($existing = $this->profileFor($request)) {
            return redirect()->route('technician.profile.edit')
                ->with('info', $existing->status === 'pending'
                    ? 'Your application is already in review.'
                    : 'You already have a technician profile.');
        }

        return view('website.technician.apply', [
            'categories' => $this->categories(),
            'zones' => $this->zones(),
        ]);
    }

    /**
     * Store the application. Profiles always start as pending so an admin
     * reviews them before the technician appears in the marketplace.
     */
    public function store(StoreTechnicianApplicationRequest $request): RedirectResponse
    {
        if ($this->profileFor($request)) {
            return redirect()->route('technician.profile.edit')
                ->with('info', 'You already have a technician profile.');
        }

        Technician::create([
            ...$request->safe()->only([
                'technician_category_id', 'zone_id', 'bio', 'skills', 'experience_years', 'hourly_rate',
            ]),
            'user_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        return redirect()->route('technician.profile.edit')
            ->with('success', 'Application submitted. We will review it and let you know once it is approved.');
    }

    private function profileFor(Request $request): ?Technician
    {
        return Technician::where('user_id', $request->user()->id)->first();
    }

    /**
     * @return Collection<int, TechnicianCategory>
     */
    private function categories(): Collection
    {
        return TechnicianCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return Collection<int, Zone>
     */
    private function zones(): Collection
    {
        return Zone::query()->active()->whereIn('type', ['city', 'area'])->orderBy('name')->get(['id', 'name', 'type']);
    }
}
