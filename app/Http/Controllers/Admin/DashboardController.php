<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $dashboardService = new DashboardService();
        $data = $dashboardService->getDashboardData();

        return response()->json($data);
    }
}
