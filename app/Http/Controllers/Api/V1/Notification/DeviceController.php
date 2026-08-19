<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Device\StoreDeviceRequest;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends ApiController
{
    /**
     * POST /api/v1/devices
     *
     * Register or update a device token for push notifications.
     * If the same token is already registered to another account it is
     * re-associated with the current user (device was handed over / re-logged in).
     */
    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $user = $request->user();
        $token = $request->input('token');

        // Re-assign token if it belonged to a different user
        Device::where('token', $token)
            ->where('user_id', '!=', $user->id)
            ->delete();

        $device = Device::updateOrCreate(
            ['user_id' => $user->id, 'token' => $token],
            [
                'platform' => $request->input('platform'),
                'name' => $request->input('name'),
            ]
        );

        return $this->created([
            'id' => $device->id,
            'platform' => $device->platform,
            'name' => $device->name,
        ], 'Device registered for push notifications.');
    }

    /**
     * DELETE /api/v1/devices/{device}
     *
     * Unregister a device token (e.g. on logout).
     */
    public function destroy(Request $request, Device $device): JsonResponse
    {
        if ($device->user_id !== $request->user()->id) {
            return $this->error('Device not found.', 404);
        }

        $device->delete();

        return $this->success(null, 'Device unregistered.');
    }
}
