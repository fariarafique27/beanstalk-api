<?php
namespace App\Services;

use App\Jobs\SyncZktecoDeviceJob;
use App\Repositories\DeviceRepository;
use App\Http\Responses\DeviceResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceService extends BaseService
{
    public function __construct(protected DeviceRepository $deviceRepository) {}

   public function getDevice()
    {
        $orgId = Auth::user()->organization_id;
        $device = $this->deviceRepository->findByOrganization($orgId);

        return (new DeviceResponse())->show($device);
    }

    public function saveDevice(Request $request)
    {
        $validated = $request->validate([
            'ip'       => ['required', 'ip'],
            'port'     => ['required', 'integer', 'min:1', 'max:65535'],
            'name'     => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ]);

        $orgId = Auth::user()->organization_id;

        // A blank password field means "don't change it" — never let an
        // empty submit wipe out a password that's already saved.
        if (!array_key_exists('password', $validated) || $validated['password'] === '') {
            unset($validated['password']);
        }

        $device = $this->deviceRepository->updateOrCreate($orgId, $validated);

        return (new DeviceResponse())->show($device);
    }

    // public function syncNow()
    // {
    //     $orgId = Auth::user()->organization_id;
    //     $device = $this->deviceRepository->findActiveByOrganization($orgId);

    //     if (!$device) {
    //         return $this->errorResponse('No active device configured.', 422);
    //     }

    //     \App\Jobs\SyncZktecoDeviceJob::dispatch($device);

    //     return $this->successResponse(null, 'Sync started. This may take a moment.');
    // }

    public function syncNow()
    {
        $orgId = Auth::user()->organization_id;
        $device = $this->deviceRepository->findActiveByOrganization($orgId);

        if (!$device) {
            return $this->errorResponse('No active device configured.', 422);
        }

        // dispatchSync() runs the job immediately in this request instead
        // of queuing it -- so a bad IP/unreachable device throws here and
        // now, where we can actually catch it and tell the frontend,
        // rather than failing silently in a background worker later.
        try {
            \App\Jobs\SyncZktecoDeviceJob::dispatchSync($device);
        } catch (\Throwable $e) {
            report($e);
            return $this->errorResponse('Sync failed: ' . $e->getMessage(), 500);
        }

        return $this->successResponse(null, 'Sync completed successfully.');
    }

}