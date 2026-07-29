<?php

namespace App\Repositories;

use App\Models\OrganizationIndex;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use App\Models\FiscalYear;
use App\Models\TaxSetting;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DashboardRepository extends BaseRepository
{

    public function getDashboard()
    {
        $organization = "abc";

        if (!$organization) {
            return null;
        }


    }
    // public function getAllOrganizations($status = null){
    //     $query = Organization::with('rootUser');

    //     if (!is_null($status)) {
    //         $query->where('status', $status);
    //     }

    //     $organizations = $query->get();
    //     $this->attachSubscriptions($organizations);
    //     return $this->rootUserPasswordAndPermission($organizations);
    // }

    // private function attachSubscriptions($organizations): void
    // {
    //     $orgIds = $organizations->pluck('id');
    //     $subscriptions = \App\Models\OrganizationSubscription::whereIn('organization_id', $orgIds)
    //         ->where('status', 'active')
    //         ->get()
    //         ->keyBy('organization_id');

    //     $organizations->each(function ($org) use ($subscriptions) {
    //         $sub = $subscriptions->get($org->id);
    //         $org->subscription = $sub ? [
    //             'plan_name'     => $sub->plan_name,
    //             'billing_cycle' => $sub->billing_cycle,
    //             'end_date'      => $sub->end_date?->toDateString(),
    //         ] : null;
    //     });
    // }

    // private function rootUserPasswordAndPermission($organizations){
    //     foreach($organizations as $organization){
    //         $organization->isRootUserSetPasswordAlready = false;
    //         if($organization->rootUser?->password){
    //             $organization->isRootUserSetPasswordAlready = true;
    //         }
    //         $organization->permissionCount = $organization->rootUser?->getAllPermissions()?->count();
    //     }

    //     return $organizations;
    // }

    // public function createOrganizationByAdmin($organizationData){
    //     $organization = Organization::create($organizationData);
    //     $organization->organizationSetting()->create([
    //         'emp_no_start' => '000001',
    //         'emp_no_next' => '000001',
    //         'separator' => '/',
    //         'use_dept_code' => 0,
    //         'use_type_code' => 0,
    //         'use_loc_code' => 0,
    //         'prefix_order' => @json_encode(['use_type', 'use_deptartment', 'use_location']),
    //         'leave_fy_start_month' => 1,
    //         'leave_fy_end_month' => 12,
    //     ]);

    //     return $organization;
    // }
    
    // public function updateOrganizationByAdmin($organization, $organizationData){
    //     $wasActive = (int) $organization->status === 1;
    //     $updated = $organization->update($organizationData);
    //     if ($updated) {
    //         $isNowInactive = isset($organizationData['status']) && (int) $organizationData['status'] === 0;
    //         if ($wasActive && $isNowInactive) {
    //             // Revoke all tokens for users of this organization
    //             \Illuminate\Support\Facades\DB::table('personal_access_tokens')
    //                 ->where('tokenable_type', '=', \App\Models\User::class)
    //                 ->whereIn('tokenable_id', function($q) use ($organization) {
    //                     $q->select('id')->from('users')->where('organization_id', $organization->id);
    //                 })
    //                 ->delete();
    //         }
    //     }
    //     return $updated;
    // }

    // public function getOrganizationById($id){
    //     return Organization::find($id);
    // }

    // public function getDashboard()
    // {
    //     $organization = auth()->user()->organization;

    //     if (!$organization) {
    //         return null;
    //     }

    //     return $organization->loadCount([
    //         'departments as active_departments_count' => function ($query) {
    //             $query->where('status', true);
    //         },
    //         'departments as total_departments_count',

    //         'designations as active_designations_count' => function ($query) {
    //             $query->where('status', true);
    //         },
    //         'designations as total_designations_count',

    //         'officeLocations as active_officeLocations_count' => function ($query) {
    //             $query->where('status', true);
    //         },
    //         'officeLocations as total_officeLocations_count',

    //         'jobLevels as active_jobLevels_count' => function ($query) {
    //             $query->where('status', true);
    //         },
    //         'jobLevels as total_jobLevels_count',

    //         'qualificationLevels as active_qualificationLevels_count' => function ($query) {
    //             $query->where('status', true);
    //         },
    //         'qualificationLevels as total_qualificationLevels_count',

    //         'employeeTypes as active_employeeTypes_count' => function ($query) {
    //             $query->where('status', true);
    //         },
    //         'employeeTypes as total_employeeTypes_count',

    //         'payrollStructure as active_payrollStructure_count' => function ($query) {
    //             $query->where('status', true);
    //         },
    //         'payrollStructure as total_payrollStructure_count',

    //         'taxStructures as active_taxStructures_count' => function ($query) {
    //             $query->where('status', true);
    //         },
    //         'taxStructures as total_taxStructures_count',
    //     ]);
    // }

    // public function getLoggedInEmployee()
    // {
    //     $user = auth()->user();
    //     return \App\Models\Employee::with(['designation:id,name', 'department:id,name', 'organization.organizationSetting'])
    //         ->where('user_id', $user->id)
    //         ->where('organization_id', $user->organization_id)
    //         ->first();
    // }
    // public function getEmployee()
    // {
    //     $ids = auth()?->user()?->organization?->employees()?->selectRaw('MIN(id) as id')?->groupBy('department_id')?->limit(5)?->pluck('id');
    //     return \App\Models\Employee::with(['designation:id,name', 'department:id,name'])?->whereIn('id', $ids)?->get(['id', 'name', 'designation_id', 'department_id', 'profile_image']);
    // }

    // public function getWorkAnniversary()
    // {
    //     $today = now();
    //     $sevenDaysLater = $today->copy()->addDays(7);

    //     return auth()->user()?->organization?->employees()
    //         ->with('designation:id,name') 
    //         ->where('status', 1)
    //         ->select('id', 'name', 'designation_id', 'date_of_joining', 'profile_image')
    //         ->get()
    //         ->map(function ($emp) use ($today) {
    //             $joining = Carbon::parse($emp->date_of_joining);

    //             $anniversary = Carbon::createFromDate(
    //                 $today->year,
    //                 $joining->month,
    //                 $joining->day
    //             );

    //             // if this year's anniversary already passed, move to next year
    //             if ($anniversary->lessThan($today)) {
    //                 $anniversary->addYear();
    //             }

    //             return [
    //                 'id' => $emp->id,
    //                 'name' => $emp->name,
    //                 'profile_image' => $emp->profile_image,
    //                 'designation_name' => $emp->designation?->name,
    //                 'next_anniversary' => $anniversary,
    //                 'years_completed' => $today->diffInYears($joining),
    //             ];
    //         })
    //         ->filter(function ($emp) use ($today, $sevenDaysLater) {
    //             return $emp['next_anniversary']->between($today, $sevenDaysLater);
    //         })
    //         ->sortBy('next_anniversary')
    //         ->take(5)
    //         ->map(function ($emp) {
    //             // format for frontend
    //             $emp['sort_date'] = $emp['next_anniversary']->format('m-d');
    //             $emp['next_anniversary'] = $emp['next_anniversary']->format('F j');
    //             return $emp;
    //         })
    //         ->values();
    // }

    // public function getBirthdays()
    // {
    //     $today = now();
    //     $start = $today->format('m-d');
    //     $end = $today->copy()->addDays(30)->format('m-d');

    //     $employees = auth()->user()->organization
    //         ->employees()
    //         ->with('designation')
    //         ->with('department')
    //         ->selectRaw("employees.*, DATE_FORMAT(date_of_birth, '%M %d') as birthday_day,
    //             DATE_FORMAT(date_of_birth, '%m-%d') as birthday_md")
    //         ->whereBetween(
    //             DB::raw("DATE_FORMAT(date_of_birth, '%m-%d')"),
    //             [$start, $end]
    //         )
    //         ->orderByRaw("DATE_FORMAT(date_of_birth, '%m-%d')")
    //         ->get();

    //     return $employees->filter(function ($employee) {
    //         return !is_null($employee->birthday_day);
    //     })->map(function ($employee) {
    //         return [
    //             'name' => $employee->name,
    //             'designation' => $employee->designation->name ?? 'N/A',
    //             'department' => $employee->department->name ?? 'N/A',
    //             'birthday' => $employee->birthday_day,
    //             'birthday_md' => $employee->birthday_md,
    //             'profile_image' => $employee->profile_image,
    //         ];
    //     })->values();
    // }

    // public function getEmployeeCountBy($model)
    // {
    //     $query = auth()->user()->organization->{$model}();

    //     $relationshipToCount = 'employees';

    //     if ($model === 'qualificationLevels') {
    //         $relationshipToCount = 'employeeQualifications';

    //         $query->withCount([
    //             $relationshipToCount => function ($q) {
    //                 $q->select(DB::raw('COUNT(DISTINCT employee_id)'));
    //             }
    //         ]);
    //     } elseif ($model === 'jobLevels') {
    //         $relationshipToCount = 'employeeContracts';
    //          $query->withCount([
    //             $relationshipToCount => function ($q) {
    //                 $q->select(DB::raw('COUNT(DISTINCT employee_id)'));
    //             }
    //         ]);
    //     } else {
    //         $query->withCount($relationshipToCount);
    //     }

    //     $countAttribute = Str::snake($relationshipToCount) . '_count';

    //     return $query
    //             ?->pluck($countAttribute, 'name')
    //             ?->toArray();

    // }
    // public function updateOrganization($organizationData)
    // {
    //     $user = auth()->user();
    //     $user->organization->update($organizationData);
    //     return $user->organization->fresh();
    // }


    // public function getUserOrganization()
    // {
    //     return auth()->user()->organization;
    // }

    // public function createSalarySetting($data)
    // {
    //     try {
    //         DB::beginTransaction();

    //         $user = auth()->user();
    //         $organization = $user?->organization;

    //         if (!$organization) {
    //             throw new \Exception('User organization not found.');
    //         }

    //         $dataArrayFiscalYear = [
    //             'title' => Carbon::createFromFormat('d/m/Y', $data['start_date'])->format('Y') . '-' . Carbon::createFromFormat('d/m/Y', $data['end_date'])->format('Y'),
    //             'organization_id' => $organization->id,
    //             'starts_at' => Carbon::createFromFormat('d/m/Y', $data['start_date']),
    //             'ends_at' => Carbon::createFromFormat('d/m/Y', $data['end_date']),
    //             'eobi_amount' => (float) $data['eobi'],
    //             'pf_percentage' => (float) $data['provident_fund_percentage'],
    //         ];

    //         if ($organization->fiscalYear !== null) {
    //             $organization->fiscalYear->update($dataArrayFiscalYear);
    //             $fiscalYearId = $organization->fiscalYear->id;
    //         } else {
    //             $fiscalYear = FiscalYear::create($dataArrayFiscalYear);
    //             $fiscalYearId = $fiscalYear->id;
    //         }

    //         $dataArrayTax = [];
    //         foreach ($data['salary_from'] as $index => $salaryFrom) {
    //             $dataArrayTax[] = [
    //                 'fiscal_year_id' => $fiscalYearId,
    //                 'income_from' => (float) str_replace(',', '', $salaryFrom),
    //                 'income_to' => isset($data['salary_to'][$index])
    //                     ? (float) str_replace(',', '', $data['salary_to'][$index])
    //                     : null,
    //                 'fixed_tax' => isset($data['tax_fixed_amount'][$index])
    //                     ? (float) str_replace(',', '', $data['tax_fixed_amount'][$index])
    //                     : 0,
    //                 'percentage' => (float) ($data['tax_percentage'][$index] ?? 0),
    //                 'created_at' => now(),
    //                 'updated_at' => now(),
    //             ];
    //         }

    //         if ($organization->fiscalYear?->taxSetting->isNotEmpty()) {
    //             TaxSetting::where('fiscal_year_id', $fiscalYearId)->delete();
    //         }

    //         TaxSetting::insert($dataArrayTax);
    //         DB::commit();
    //         return [$dataArrayFiscalYear, $dataArrayTax];

    //     } catch (\Exception $e) {
    //         DB::rollback();

    //         return response()->json([
    //             'message' => 'Error creating salary setting',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function globalSettingsData()
    // {
    //     return auth()->user()->organization->organizationSetting;
    // }

    // public function updateGlobalSettings($globalSettingData)
    // {
    //     return auth()->user()->organization->organizationSetting->update($globalSettingData);
    // }
    // public function getIndexes(){
    //     return OrganizationIndex::pluck('index_name');
    // }
    // public function getName($organizationId){
    //     return str_replace(' ', '-', strtolower(Organization::where('id', $organizationId)->value('name')));
    // }
    // public function indexExist($organizationId){
    //     $name = $this->getName($organizationId);
    //     return OrganizationIndex::where('index_name',$name . '-' . $organizationId)->exists();

    // }
    // public function storeIndexName($organizationId, $name, $hostUrl = null){
    //     OrganizationIndex::create(['organization_id'=>$organizationId,'index_name'=>$name,'host_url'=>$hostUrl]);
    // }

    // public function updatePayrollOnboardingDate($payrollMonth)
    // {
    //     $organizationSettings = $this->globalSettingsData();
    //     return $organizationSettings->update(['payroll_onboarding_date' => $payrollMonth . '-01']);
    // }

    public function getSuperAdminDashboard(): array
    {
        logger('Inside DashboardRepository getSuperAdminDashboard');
        $now = Carbon::now();

        // Fix: Pass the variable name as a string, or return an array directly
        return compact('now'); 
    }
}