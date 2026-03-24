<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::query()
            ->select(['id', 'user_id', 'action', 'payload', 'ip_address', 'created_at'])
            ->with('user:id,name,role')
            ->when($request->action, fn ($query) => $query->where('action', $request->action))
            ->when($request->date_from, fn ($query) => $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay()))
            ->when($request->date_to, fn ($query) => $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay()))
            ->orderByDesc('created_at')
            ->simplePaginate(50)
            ->withQueryString();

        return view('admin.logs.index', compact('logs'));
    }
}
