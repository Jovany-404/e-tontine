<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function admin(Request $request)
    {
        $data = $this->dashboardService->getAdminDashboard();

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function tontine(int $tontineId)
    {
        $data = $this->dashboardService->getTontineDashboard($tontineId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }
}
