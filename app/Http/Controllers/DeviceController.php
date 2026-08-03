<?php
namespace App\Http\Controllers;

use App\Services\DeviceService;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function __construct(protected DeviceService $deviceService) {}

    public function show()
    {
        try {
            return $this->deviceService->getDevice();
        } catch (\Exception $e) {
            $this->storeException($e);
            return $this->errorResponse($this->getMessageData('error', 'en')['general_error'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            return $this->deviceService->saveDevice($request);
        } catch (\Exception $e) {
            $this->storeException($e);
            return $this->errorResponse($this->getMessageData('error', 'en')['general_error'], 500);
        }
    }

    public function sync()
    {
        try {
            return $this->deviceService->syncNow();
        } catch (\Exception $e) {
            $this->storeException($e);
            return $this->errorResponse($this->getMessageData('error', 'en')['general_error'], 500);
        }
    }
}