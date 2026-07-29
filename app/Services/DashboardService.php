<?php
namespace App\Services;

use App\Repositories\DashboardRepository;
use App\Http\Responses\DashBoardResponse; 

class DashboardService
{
    protected $dashboardRepository;
    protected $dashboardResponse;

    public function __construct(DashboardRepository $dashboardRepository , DashboardResponse $dashboardResponse)
    {
        $this->dashboardRepository = $dashboardRepository;
        $this->dashboardResponse = $dashboardResponse;
    }

    public function getDashboard()
    {
        $data = $this->dashboardRepository->getDashboard();
        return $this->dashboardResponse->prepareDashboardResponse($data , $data , $data, $data);
    }
        public function getSuperAdminDashboard()
    {
      logger('Inside DashboardService getSuperAdminDashboard');
        $data = $this->dashboardRepository->getSuperAdminDashboard();
        return $this->dashboardResponse->prepareSuperAdminDashboardResponse($data);
    }

}