<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCampaignRequest;
use App\Models\Campaign;
use App\Models\EmailTemplate;
use App\Services\CampaignSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        $campaigns = Campaign::with('creator:id,name,email')
            ->when($request->string('status')->value(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('channel')->value(), fn ($query, string $channel) => $query->where('channel', $channel))
            ->latest()
            ->paginate(25);

        return view('admin.campaigns.index', compact('campaigns'));
    }

    public function store(SaveCampaignRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['created_by'] = auth()->id();
        $data['sent_to_user_ids'] = [];
        $data['target_segments'] = $request->input('target_segments', []);

        // A campaign only becomes "sent" through the send pipeline, which records
        // recipients. Creating one directly as sent would leave a stuck record that
        // can neither be sent nor deleted, so clamp it to a draft.
        if (($data['status'] ?? null) === 'sent') {
            $data['status'] = 'draft';
        }

        if ($data['channel'] === 'email' && isset($data['email_template_id'])) {
            $template = EmailTemplate::find($data['email_template_id']);
            $data['subject'] ??= $template?->subject;
            $data['content'] ??= $template?->body;
        }

        Campaign::create($data);

        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign created.');
    }

    public function show(Campaign $campaign): View
    {
        return view('admin.campaigns.show', [
            'campaign' => $campaign->load(['creator:id,name,email', 'emailTemplate']),
        ]);
    }

    public function update(SaveCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        if ($campaign->status !== 'draft') {
            return redirect()->route('admin.campaigns.show', $campaign)->with('error', 'Only draft campaigns can be modified.');
        }

        $data = $request->validated();
        $data['target_segments'] = $request->input('target_segments', []);

        if ($data['channel'] === 'email' && isset($data['email_template_id'])) {
            $template = EmailTemplate::find($data['email_template_id']);
            $data['subject'] ??= $template?->subject;
            $data['content'] ??= $template?->body;
        }

        $campaign->update($data);

        return redirect()->route('admin.campaigns.show', $campaign)->with('success', 'Campaign updated.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        if ($campaign->status === 'sent') {
            return redirect()->route('admin.campaigns.index')->with('error', 'Sent campaigns cannot be deleted.');
        }

        $campaign->delete();

        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign deleted.');
    }

    public function send(Campaign $campaign, CampaignSender $sender): RedirectResponse
    {
        if ($campaign->status !== 'draft' && $campaign->status !== 'scheduled') {
            return redirect()->route('admin.campaigns.show', $campaign)->with('error', 'Campaign cannot be sent from its current status.');
        }

        $sentCount = $sender->send($campaign);

        return redirect()->route('admin.campaigns.show', $campaign)->with('success', 'Campaign sent to '.$sentCount.' users.');
    }

    public function templates(): JsonResponse
    {
        $templates = EmailTemplate::where('is_active', true)->get(['id', 'name', 'key', 'subject']);

        return response()->json(['templates' => $templates]);
    }

    public function segments(): JsonResponse
    {
        return response()->json([
            'segments' => collect(Campaign::SEGMENTS)
                ->map(fn (string $label, string $key): array => ['key' => $key, 'label' => $label])
                ->values(),
        ]);
    }
}
