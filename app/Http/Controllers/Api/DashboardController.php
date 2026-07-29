<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService; 

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function getDashboard()
    {
        try {
            return $this->dashboardService->getDashboard();
        } catch (\Exception $e) {
            $this->storeException($e);
            return $this->errorResponse($this->getMessageData('error', 'en')['general_error'], 500);
        }
    }

    public function getSuperAdminDashboard()
    {
        logger("getSuperAdminDashboard -  dashboard controller");
        try {
            return $this->dashboardService->getSuperAdminDashboard();
        } catch (\Exception $e) {
            $this->storeException($e);
            return $this->errorResponse($this->getMessageData('error', 'en')['general_error'], 500);
        }
    }
}