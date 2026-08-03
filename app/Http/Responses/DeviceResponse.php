<?php
namespace App\Http\Responses;

class DeviceResponse extends BaseResponse
{
    public function show($device)
    {
        if (!$device) {
            return $this->successResponse([], 'No device configured yet.');
        }

        return $this->successResponse([
            'ip'             => $device->ip,
            'port'           => $device->port,
            'name'           => $device->name,
            'is_active'      => $device->is_active,
            'last_synced_at' => $device->last_synced_at?->diffForHumans(),
        ]);
    }
}