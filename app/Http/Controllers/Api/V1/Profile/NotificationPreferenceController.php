<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Profile\UpdateNotificationPreferencesRequest;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends ApiController
{
    /**
     * GET /api/v1/me/notification-preferences
     *
     * Returns a nested map: { event: { channel: bool } }.
     * Missing rows default to enabled (true).
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $rows = NotificationPreference::where('user_id', $userId)->get();

        $preferences = $this->buildMap($rows->all());

        return $this->success($preferences);
    }

    /**
     * PUT /api/v1/me/notification-preferences
     *
     * Accepts a partial map of { event: { channel: bool } }.
     * Only provided combinations are updated; omitted ones are untouched.
     */
    public function update(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $data = $request->validated();

        $upserts = [];

        foreach (UpdateNotificationPreferencesRequest::EVENTS as $event) {
            if (! isset($data[$event])) {
                continue;
            }

            foreach (UpdateNotificationPreferencesRequest::CHANNELS as $channel) {
                if (! array_key_exists($channel, $data[$event])) {
                    continue;
                }

                $upserts[] = [
                    'user_id' => $userId,
                    'event' => $event,
                    'channel' => $channel,
                    'enabled' => (bool) $data[$event][$channel],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (! empty($upserts)) {
            NotificationPreference::upsert(
                $upserts,
                ['user_id', 'event', 'channel'],
                ['enabled', 'updated_at']
            );
        }

        // Return the full updated state
        $rows = NotificationPreference::where('user_id', $userId)->get();
        $preferences = $this->buildMap($rows->all());

        return $this->success($preferences, 'Notification preferences updated.');
    }

    /**
     * Build the nested { event: { channel: bool } } map.
     * Events/channels not yet saved in the DB default to true.
     *
     * @param  NotificationPreference[]  $rows
     * @return array<string, array<string, bool>>
     */
    private function buildMap(array $rows): array
    {
        // Index existing rows for O(1) lookup
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row->event][$row->channel] = $row->enabled;
        }

        $map = [];

        foreach (UpdateNotificationPreferencesRequest::EVENTS as $event) {
            foreach (UpdateNotificationPreferencesRequest::CHANNELS as $channel) {
                $map[$event][$channel] = $indexed[$event][$channel] ?? true;
            }
        }

        return $map;
    }
}
