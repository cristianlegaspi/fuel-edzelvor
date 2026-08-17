<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Superadmin-only, read-only view over the audit_logs table. Also
 * gated by the 'role:superadmin' route middleware (routes/web.php) —
 * defence in depth, same pattern as UserController.
 *
 * There is deliberately no create/edit/delete here: audit_logs is
 * append-only (rows are written by App\Models\Concerns\Auditable and
 * AuthController), so this controller only ever reads.
 */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('description', 'like', "%{$request->search}%");
            })
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $users = User::query()->orderBy('name')->get(['id', 'name']);

        return view('audit-logs.index', [
            'logs' => $logs,
            'users' => $users,
            'actions' => AuditLog::ACTIONS,
        ]);
    }
}
