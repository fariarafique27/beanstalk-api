<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Attendance\AttendanceService;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index(Request $request)
    {
        logger('ATTENDANCE INDEX CONTROLLER HIT!', $request->all());

        try {
            return $this->attendanceService->getTodayAttendances($request);
        } catch (\Exception $e) {
            $this->storeException($e);
            return $this->errorResponse($this->getMessageData('error', 'en')['general_error'], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            return $this->attendanceService->getEmployeeHistory($request, $id);
        } catch (\Exception $e) {
            $this->storeException($e);
            return $this->errorResponse($this->getMessageData('error', 'en')['general_error'], 500);
        }
    }

}
