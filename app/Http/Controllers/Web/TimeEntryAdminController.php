<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TimeEntryAdminController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'user_id' => ['nullable', Rule::exists('users', 'id')->whereNull('agency_id')],
        ]);

        $dateFrom = $request->filled('date_from') ? $request->date('date_from') : null;
        $dateTo = $request->filled('date_to') ? $request->date('date_to') : null;
        $userId = $request->filled('user_id') ? (int) $request->user_id : null;

        if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $query = TimeEntry::query()
            ->with('user')
            ->whereHas('user', fn ($q) => $q->whereNull('agency_id'));

        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($dateFrom) {
            $query->whereDate('clock_in_at', '>=', $dateFrom->toDateString());
        }
        if ($dateTo) {
            $query->whereDate('clock_in_at', '<=', $dateTo->toDateString());
        }

        $entries = $query->orderByDesc('clock_in_at')->paginate(30)->withQueryString();

        $centralUsers = User::query()
            ->whereNull('agency_id')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $displayTz = config('app.display_timezone') ?: 'America/New_York';

        return view('time-entries.admin-index', compact(
            'entries',
            'centralUsers',
            'displayTz',
            'dateFrom',
            'dateTo',
            'userId'
        ));
    }
}
