<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Http\Requests\Api\V1\Profile\UploadVerificationRequest;
use App\Http\Requests\Website\UpdatePreferencesRequest;
use App\Jobs\ExportUserDataJob;
use App\Models\Block;
use App\Models\NotificationPreference;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountController extends Controller
{
    public function profile(Request $request): View
    {
        return view('website.dashboard.profile', ['user' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_institutional'] = (bool) $request->boolean('is_institutional');
        $request->user()->update($data);

        return back()->with('success', 'Profile updated.');
    }

    public function verification(UploadVerificationRequest $request): RedirectResponse
    {
        $path = $request->file('document')->store('verification/'.$request->user()->id, 'local');
        $request->user()->update(['verification_document_path' => $path, 'verification_status' => 'pending', 'verification_note' => null]);

        return back()->with('success', 'Verification document submitted securely.');
    }

    public function settings(Request $request): View
    {
        $preferences = $request->user()->notificationPreferences->groupBy('event')->map(fn ($rows) => $rows->pluck('enabled', 'channel'));
        $blocks = $request->user()->blocks()->with('blocked:id,name,avatar')->get();

        return view('website.dashboard.setting', compact('preferences', 'blocks'));
    }

    public function password(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => Hash::make($request->validated('password'))]);

        return back()->with('success', 'Password updated.');
    }

    public function preferences(UpdatePreferencesRequest $request): RedirectResponse
    {
        $events = ['new_message', 'booking_update', 'saved_search', 'promotions'];
        $channels = ['in_app', 'push', 'email', 'sms'];
        $selected = $request->validated('preferences', []);
        foreach ($events as $event) {
            foreach ($channels as $channel) {
                NotificationPreference::updateOrCreate(['user_id' => $request->user()->id, 'event' => $event, 'channel' => $channel], ['enabled' => isset($selected[$event][$channel])]);
            }
        }

        return back()->with('success', 'Notification preferences updated.');
    }

    public function block(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['block' => 'You cannot block yourself.']);
        }

        Block::firstOrCreate(['blocker_id' => $request->user()->id, 'blocked_id' => $user->id]);

        return back()->with('success', 'User blocked. They can no longer message you.');
    }

    public function unblock(Request $request, Block $block): RedirectResponse
    {
        abort_unless($block->blocker_id === $request->user()->id, 403);
        $block->delete();

        return back()->with('success', 'User unblocked.');
    }

    public function export(Request $request): Response|StreamedResponse
    {
        $format = in_array($request->input('format'), ['json', 'csv', 'pdf'], true)
            ? $request->input('format')
            : 'json';

        $data = ExportUserDataJob::dataFor($request->user());
        $filename = 'ready-export-'.now()->format('Y-m-d').'.'.$format;

        return match ($format) {
            'csv' => $this->exportCsv($data, $filename),
            'pdf' => Pdf::loadView('website.dashboard.data-export-pdf', [
                'data' => $data,
                'rows' => $this->flatten($data),
                'user' => $request->user(),
            ])->download($filename),
            default => response()->streamDownload(function () use ($data): void {
                echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }, $filename, ['Content-Type' => 'application/json']),
        };
    }

    /**
     * Stream the personal-data bundle as a flat CSV of section / field / value rows.
     *
     * @param  array<string, mixed>  $data
     */
    private function exportCsv(array $data, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Section', 'Field', 'Value']);

            foreach ($this->flatten($data) as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Flatten the nested personal-data array into [section, field, value] rows so it can be
     * rendered as a CSV or a tabular PDF.
     *
     * @param  array<string, mixed>  $data
     * @return list<array{string, string, string}>
     */
    private function flatten(array $data): array
    {
        $rows = [];

        foreach ($data as $section => $value) {
            $sectionLabel = str($section)->replace('_', ' ')->title()->value();

            foreach (Arr::dot([$section => $value]) as $key => $leaf) {
                $field = str($key)->after('.')->replace(['.', '_'], [' › ', ' '])->value();

                $rows[] = [
                    $sectionLabel,
                    $field === '' ? $sectionLabel : $field,
                    is_scalar($leaf) ? (string) $leaf : json_encode($leaf),
                ];
            }
        }

        return $rows;
    }

    public function deletion(Request $request): RedirectResponse
    {
        $request->user()->update(['deletion_requested_at' => now()]);

        return back()->with('success', 'Account deletion request submitted.');
    }
}
