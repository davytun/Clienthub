<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        $clients = User::where('business_id', auth()->user()->business_id)
            ->where('role', 'client')
            ->orderBy('name')
            ->get();

        return view('clients.index', compact('clients'));
    }
}
