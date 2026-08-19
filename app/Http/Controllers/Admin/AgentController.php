<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAgentRequest;
use App\Http\Requests\Admin\UpdateAgentRequest;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class AgentController extends Controller
{
    public function store(StoreAgentRequest $request, Agency $agency): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['user_email'])->firstOrFail();

        if ($agency->agents()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['user_email' => 'This user is already an agent of this agency.']);
        }

        $agency->agents()->create([
            'user_id' => $user->id,
            'title' => $validated['title'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Agent added.');
    }

    public function update(UpdateAgentRequest $request, Agency $agency, Agent $agent): RedirectResponse
    {
        abort_unless($agent->agency_id === $agency->id, 404);

        $validated = $request->validated();

        $validated['is_active'] = $request->boolean('is_active');

        $agent->update($validated);

        return back()->with('success', 'Agent updated.');
    }

    public function destroy(Agency $agency, Agent $agent): RedirectResponse
    {
        abort_unless($agent->agency_id === $agency->id, 404);

        $agent->delete();

        return back()->with('success', 'Agent removed.');
    }
}
