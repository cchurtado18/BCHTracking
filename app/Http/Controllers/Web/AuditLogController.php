<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        if (auth()->user() && auth()->user()->isAgencyUser()) {
            abort(403, 'No tiene acceso al módulo de auditoría.');
        }
        $query = AuditLog::with('user')->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->auditable_type);
        }
        if ($request->filled('user_id') && (int) $request->user_id > 0) {
            $query->where('user_id', (int) $request->user_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = '%' . trim($request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('summary', 'like', $search)
                    ->orWhere('old_values', 'like', $search)
                    ->orWhere('new_values', 'like', $search);
            });
        }

        $logs = $query->paginate(25)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        // Estadísticas con los mismos filtros
        $statsQuery = AuditLog::query();
        if ($request->filled('action')) {
            $statsQuery->where('action', $request->action);
        }
        if ($request->filled('auditable_type')) {
            $statsQuery->where('auditable_type', $request->auditable_type);
        }
        if ($request->filled('user_id') && (int) $request->user_id > 0) {
            $statsQuery->where('user_id', (int) $request->user_id);
        }
        if ($request->filled('date_from')) {
            $statsQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $statsQuery->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = '%' . trim($request->search) . '%';
            $statsQuery->where(function ($q) use ($search) {
                $q->where('summary', 'like', $search)
                    ->orWhere('old_values', 'like', $search)
                    ->orWhere('new_values', 'like', $search);
            });
        }
        $statsTotal = $statsQuery->count();
        $statsCreated = (clone $statsQuery)->where('action', 'created')->count();
        $statsUpdated = (clone $statsQuery)->where('action', 'updated')->count();
        $statsDeleted = (clone $statsQuery)->where('action', 'deleted')->count();

        return view('audit.index', compact('logs', 'users', 'statsTotal', 'statsCreated', 'statsUpdated', 'statsDeleted'));
    }

    public function show(string $id): View
    {
        if (auth()->user() && auth()->user()->isAgencyUser()) {
            abort(403, 'No tiene acceso al módulo de auditoría.');
        }
        $log = AuditLog::with('user')->findOrFail($id);
        return view('audit.show', compact('log'));
    }
}
