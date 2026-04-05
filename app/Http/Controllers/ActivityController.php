<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isOwner(), 403);

        $logs = ActivityLog::with('actor')
            ->latest('created_at')
            ->paginate(50);

        return view('activity.index', compact('logs'));
    }
}
