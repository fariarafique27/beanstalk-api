<?php

namespace App\Http\Responses;

use App\Http\Responses\BaseResponse;
use Illuminate\Support\Collection;


class DashBoardResponse extends BaseResponse
{

    public function prepareSuperAdminDashboardResponse(array $data)
    {
        $response_message = $this->getMessageData('success', 'en')['general_success'];
        return $this->successResponse($response_message, $data);
    }

        public function prepareDashboardResponse($data, $employeeBirthdays, $workAnniversary, $myEmployee = null)
    {
        if (!empty($data)) {
            $response_message = $this->getMessageData('success', 'en')['general_success'];
            $response['dashboard'] = [
                'departments'          => [$data->active_departments_count, $data->total_departments_count],
                'designations'         => [$data->active_designations_count, $data->total_designations_count],
                'officeLocations'      => [$data->active_officeLocations_count, $data->total_officeLocations_count],
                'jobLevels'            => [$data->active_jobLevels_count, $data->total_jobLevels_count],
                'qualificationLevels'  => [$data->active_qualificationLevels_count, $data->total_qualificationLevels_count],
                'employeeTypes'        => [$data->active_employeeTypes_count, $data->total_employeeTypes_count],
                'payrollStructure'     => [$data->active_payrollStructure_count, $data->total_payrollStructure_count],
                'taxStructures'        => [$data->active_taxStructures_count, $data->total_taxStructures_count],
                'birthdays'            => $employeeBirthdays,
                'workAnniversary'      => $workAnniversary,
                'my_profile'           => $myEmployee ? [
                    'name'            => $myEmployee->name,
                    'employee_no'     => $myEmployee->employee_no,
                    'designation'     => $myEmployee->designation?->name,
                    'department'      => $myEmployee->department?->name,
                    'date_of_joining' => $myEmployee->date_of_joining,
                    'email'           => $myEmployee->official_email,
                    'phone'           => $myEmployee->phone,
                    'profile_image'   => $myEmployee->profile_image,
                ] : null,
            ];

            return $this->successResponse($response_message, $response);
        }
        $response_message = $this->getMessageData('error', 'en')['general_error'];
        return $this->errorResponse($response_message, 200);
    }
}