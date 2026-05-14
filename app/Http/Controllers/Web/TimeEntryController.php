<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use App\Services\TimeAttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimeEntryController extends Controller
{
    public function __construct(
        private TimeAttendanceService $timeAttendance
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $openEntry = TimeEntry::query()
            ->with(['breaks' => fn ($q) => $q->orderByDesc('id')])
            ->where('user_id', $user->id)
            ->whereNull('clock_out_at')
            ->orderByDesc('id')
            ->first();

        $activeBreak = $openEntry?->breaks->firstWhere('ended_at', null);

        $history = TimeEntry::query()
            ->where('user_id', $user->id)
            ->orderByDesc('clock_in_at')
            ->paginate(20)
            ->withQueryString();

        $displayTz = config('app.display_timezone') ?: 'America/New_York';

        return view('time-entries.index', compact('openEntry', 'activeBreak', 'history', 'displayTz'));
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $this->timeAttendance->clockIn(
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return redirect()->route('time-entries.index')
            ->with('success', 'Jornada iniciada correctamente.');
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $this->timeAttendance->clockOut(
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return redirect()->route('time-entries.index')
            ->with('success', 'Salida registrada correctamente.');
    }

    public function breakStart(Request $request): RedirectResponse
    {
        $this->timeAttendance->startBreak($request->user());

        return redirect()->route('time-entries.index')
            ->with('success', 'Break iniciado.');
    }

    public function breakEnd(Request $request): RedirectResponse
    {
        $this->timeAttendance->endBreak($request->user());

        return redirect()->route('time-entries.index')
            ->with('success', 'Break finalizado.');
    }
}
