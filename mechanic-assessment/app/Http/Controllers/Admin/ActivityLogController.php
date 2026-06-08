<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = ActivityLog::with('user')
            ->latest()
            ->when($request->filled('action'), function ($q) use ($request): void {
                $q->where('action', $request->string('action')->toString());
            })
            ->paginate(30)
            ->withQueryString();

        $actions = ActivityLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('admin.activity-logs.index', compact('logs', 'actions'));
    }
}
