<?php

namespace App\Services;

use App\Models\TimeEntry;
use App\Models\TimeEntryBreak;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimeAttendanceService
{
    public function clockIn(User $user, ?string $ip, ?string $userAgent): TimeEntry
    {
        if ($user->isAgencyUser()) {
            throw ValidationException::withMessages([
                'clock' => 'El fichaje no está disponible para usuarios de subagencia.',
            ]);
        }

        return DB::transaction(function () use ($user, $ip, $userAgent) {
            $open = TimeEntry::query()
                ->where('user_id', $user->id)
                ->whereNull('clock_out_at')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if ($open) {
                throw ValidationException::withMessages([
                    'clock' => 'Ya tiene una entrada activa. Debe registrar salida antes de una nueva entrada.',
                ]);
            }

            return TimeEntry::create([
                'user_id' => $user->id,
                'clock_in_at' => now(),
                'clock_in_ip' => $ip,
                'clock_in_user_agent' => $userAgent ? mb_substr($userAgent, 0, 512) : null,
            ]);
        });
    }

    public function clockOut(User $user, ?string $ip, ?string $userAgent): TimeEntry
    {
        if ($user->isAgencyUser()) {
            throw ValidationException::withMessages([
                'clock' => 'El fichaje no está disponible para usuarios de subagencia.',
            ]);
        }

        return DB::transaction(function () use ($user, $ip, $userAgent) {
            $open = TimeEntry::query()
                ->where('user_id', $user->id)
                ->whereNull('clock_out_at')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if (! $open) {
                throw ValidationException::withMessages([
                    'clock' => 'No hay una entrada activa. Registre entrada primero.',
                ]);
            }

            $this->closeActiveBreakLocked($open->id);

            $open->update([
                'clock_out_at' => now(),
                'clock_out_ip' => $ip,
                'clock_out_user_agent' => $userAgent ? mb_substr($userAgent, 0, 512) : null,
            ]);

            return $open->fresh();
        });
    }

    public function startBreak(User $user): TimeEntryBreak
    {
        if ($user->isAgencyUser()) {
            throw ValidationException::withMessages([
                'clock' => 'El fichaje no está disponible para usuarios de subagencia.',
            ]);
        }

        return DB::transaction(function () use ($user) {
            $open = TimeEntry::query()
                ->where('user_id', $user->id)
                ->whereNull('clock_out_at')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if (! $open) {
                throw ValidationException::withMessages([
                    'clock' => 'Debe registrar entrada antes de iniciar un break.',
                ]);
            }

            $active = TimeEntryBreak::query()
                ->where('time_entry_id', $open->id)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if ($active) {
                throw ValidationException::withMessages([
                    'clock' => 'Ya tiene un break en curso. Finalícelo antes de iniciar otro.',
                ]);
            }

            return TimeEntryBreak::create([
                'time_entry_id' => $open->id,
                'started_at' => now(),
            ]);
        });
    }

    public function endBreak(User $user): TimeEntryBreak
    {
        if ($user->isAgencyUser()) {
            throw ValidationException::withMessages([
                'clock' => 'El fichaje no está disponible para usuarios de subagencia.',
            ]);
        }

        return DB::transaction(function () use ($user) {
            $open = TimeEntry::query()
                ->where('user_id', $user->id)
                ->whereNull('clock_out_at')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if (! $open) {
                throw ValidationException::withMessages([
                    'clock' => 'No hay entrada activa.',
                ]);
            }

            $active = TimeEntryBreak::query()
                ->where('time_entry_id', $open->id)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if (! $active) {
                throw ValidationException::withMessages([
                    'clock' => 'No hay un break en curso.',
                ]);
            }

            $active->update(['ended_at' => now()]);

            return $active->fresh();
        });
    }

    private function closeActiveBreakLocked(int $timeEntryId): void
    {
        TimeEntryBreak::query()
            ->where('time_entry_id', $timeEntryId)
            ->whereNull('ended_at')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->get()
            ->each(fn (TimeEntryBreak $b) => $b->update(['ended_at' => now()]));
    }
}
