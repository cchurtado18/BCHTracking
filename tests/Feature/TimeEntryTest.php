<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\TimeEntry;
use App\Models\TimeEntryBreak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_agency_user_cannot_access_time_entries_index(): void
    {
        $agency = Agency::create([
            'name' => 'Sub Test',
            'code' => '9998',
            'phone' => '555',
            'is_active' => true,
            'is_main' => false,
        ]);
        $user = User::factory()->create(['agency_id' => $agency->id]);

        $this->actingAs($user)->get(route('time-entries.index'))->assertForbidden();
    }

    public function test_central_user_can_clock_in_and_clock_out(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $this->actingAs($user)
            ->post(route('time-entries.clock-in'))
            ->assertRedirect(route('time-entries.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('time_entries', [
            'user_id' => $user->id,
            'clock_out_at' => null,
        ]);

        $this->actingAs($user)
            ->post(route('time-entries.clock-out'))
            ->assertRedirect(route('time-entries.index'))
            ->assertSessionHas('success');

        $entry = TimeEntry::where('user_id', $user->id)->first();
        $this->assertNotNull($entry->clock_out_at);
    }

    public function test_central_user_cannot_double_clock_in_without_clock_out(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $this->actingAs($user)->post(route('time-entries.clock-in'))->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->from(route('time-entries.index'))
            ->post(route('time-entries.clock-in'))
            ->assertRedirect(route('time-entries.index'))
            ->assertSessionHasErrors('clock');
    }

    public function test_central_user_cannot_clock_out_without_open_entry(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $this->actingAs($user)
            ->from(route('time-entries.index'))
            ->post(route('time-entries.clock-out'))
            ->assertRedirect(route('time-entries.index'))
            ->assertSessionHasErrors('clock');
    }

    public function test_admin_can_view_team_time_entries(): void
    {
        $admin = User::factory()->create(['agency_id' => null, 'is_admin' => true]);
        $worker = User::factory()->create(['agency_id' => null, 'is_admin' => false]);
        TimeEntry::create([
            'user_id' => $worker->id,
            'clock_in_at' => now()->subHours(2),
            'clock_out_at' => now()->subHour(),
        ]);

        $this->actingAs($admin)
            ->get(route('time-entries.admin.index'))
            ->assertOk()
            ->assertSee($worker->name);
    }

    public function test_central_user_can_start_and_end_break(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $this->actingAs($user)->post(route('time-entries.clock-in'));

        $entry = TimeEntry::where('user_id', $user->id)->first();
        $this->assertNotNull($entry);

        $this->actingAs($user)
            ->post(route('time-entries.break-start'))
            ->assertRedirect(route('time-entries.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('time_entry_breaks', [
            'time_entry_id' => $entry->id,
            'ended_at' => null,
        ]);

        $this->actingAs($user)
            ->post(route('time-entries.break-end'))
            ->assertRedirect(route('time-entries.index'))
            ->assertSessionHas('success');

        $break = TimeEntryBreak::where('time_entry_id', $entry->id)->first();
        $this->assertNotNull($break->ended_at);
    }

    public function test_clock_out_while_on_break_closes_break_and_shift(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $this->actingAs($user)->post(route('time-entries.clock-in'));
        $entry = TimeEntry::where('user_id', $user->id)->first();
        $this->actingAs($user)->post(route('time-entries.break-start'));

        $this->actingAs($user)->post(route('time-entries.clock-out'))->assertSessionHasNoErrors();

        $entry->refresh();
        $this->assertNotNull($entry->clock_out_at);
        $this->assertNotNull(TimeEntryBreak::where('time_entry_id', $entry->id)->whereNotNull('ended_at')->first());
    }

    public function test_cannot_start_second_break_without_ending_first(): void
    {
        $user = User::factory()->create(['agency_id' => null]);
        $this->actingAs($user)->post(route('time-entries.clock-in'));
        $this->actingAs($user)->post(route('time-entries.break-start'));

        $this->actingAs($user)
            ->from(route('time-entries.index'))
            ->post(route('time-entries.break-start'))
            ->assertSessionHasErrors('clock');
    }
}
