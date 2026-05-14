<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeEntry extends Model
{
    protected $fillable = [
        'user_id',
        'clock_in_at',
        'clock_out_at',
        'clock_in_ip',
        'clock_out_ip',
        'clock_in_user_agent',
        'clock_out_user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clock_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(TimeEntryBreak::class);
    }

    public function activeBreak(): ?TimeEntryBreak
    {
        return $this->breaks()->whereNull('ended_at')->orderByDesc('id')->first();
    }
}
