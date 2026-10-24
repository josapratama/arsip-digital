<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest('created_at');

        if ($request->filled('q')) {
            $query->where('description', 'ilike', "%{$request->q}%");
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return Inertia::render('ActivityLog/Index', [
            'logs' => $query->paginate(20)->withQueryString()->through(fn($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user' => $log->user?->name ?? 'System',
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->format('d M Y H:i:s'),
            ]),
            'filters' => $request->only(['q', 'action', 'user_id']),
            'users' => User::select('id', 'name')->orderBy('name')->get(),
        ]);
    }
}
