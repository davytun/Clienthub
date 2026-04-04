<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $business = auth()->user()->business;

        $stats = [
            'clients'          => User::where('business_id', $business->id)->where('role', 'client')->count(),
            'active_projects'  => Project::where('status', 'active')->count(),
            'unpaid_invoices'  => Invoice::whereIn('status', ['sent'])->count(),
            'total_invoiced'   => Invoice::whereIn('status', ['sent', 'paid'])->sum('total'),
        ];

        return view('dashboard', compact('stats'));
    }
}
