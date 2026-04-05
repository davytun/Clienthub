<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $business = auth()->user()->business;

        // Use explicit business relations — never rely solely on the global BusinessScope
        // for aggregate stats shown to the owner.
        $stats = [
            'clients'         => $business->clients()->count(),
            'active_projects' => $business->projects()->where('status', 'active')->count(),
            'unpaid_invoices' => $business->invoices()->whereIn('status', ['sent'])->count(),
            'total_invoiced'  => $business->invoices()->whereIn('status', ['sent', 'paid'])->sum('total'),
        ];

        return view('dashboard', compact('stats'));
    }
}
