<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ActivityLog::class, 'activity_log');
    }

    public function index(Request $request): View
    {
        $query = ActivityLog::with('user')
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->when($request->filled('user'), fn ($q) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', '%' . $request->string('user') . '%')))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->when($request->filled('module'), fn ($q) => $q->where('table_name', 'like', '%' . $request->string('module') . '%'))
            ->when($request->filled('success'), fn ($q) => $q->where('success', $request->boolean('success')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')))
            ->when($request->filled('description'), fn ($q) => $q->where('description', 'like', '%' . $request->string('description') . '%'))
            ->when($request->filled('record_id'), fn ($q) => $q->where('record_id', $request->integer('record_id')))
            ->latest('created_at');

        $logs = $query->paginate(20)->withQueryString();

        return view('logs.activity', compact('logs'));
    }

    public function show(ActivityLog $activityLog): View
    {
        return view('logs.activity-show', compact('activityLog'));
    }
}
