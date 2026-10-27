<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    public function index(Request $request)
    {
        $user = $request->user();

        return view('dashboard', [
            'stats' => $this->dashboard->statsFor($user),
            'recent' => $this->dashboard->recentTickets($user),
        ]);
    }
}
