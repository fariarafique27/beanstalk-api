<?php
namespace App\Http\Responses;

class AttendanceResponse extends BaseResponse
{
    public function today($attendances)
    {
        $totalPresent = 0;
        $totalAbsent = 0;

        foreach ($attendances as $item) {
            if (strtolower($item['status']) === 'present') {
                $totalPresent++;
            } else {
                $totalAbsent++;
            }
        }

        return $this->successResponse('Success', [
            'total_present' => $totalPresent,
            'total_absent' => $totalAbsent,
            'data' => $attendances,
        ]);
    }

    public function history($paginatedLogs)
    {
        return $this->successResponse('Success', $paginatedLogs);
    }
}