<?php

use App\Models\Competition;
use App\Models\PersonalBest;
use App\Models\Result;
use App\Models\Sport;
use App\Models\User;

function makeCompetition(User $organizer, Sport $sport, array $overrides = []): Competition
{
    return Competition::create([
        'organizer_id' => $organizer->id,
        'sport_id' => $sport->id,
        'title' => 'City Sprint',
        'description' => 'A sprint.',
        'location' => 'Central Track',
        'start_time' => now()->addDays(3),
        'end_time' => now()->addDays(3)->addHour(),
        'registration_deadline' => now()->addDay(),
        'status' => 'published',
        ...$overrides,
    ]);
}

it('lists competitions as cards with a status pill and an empty state', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('competitions.index'))
        ->assertOk()
        ->assertSee('No upcoming competitions yet');

    makeCompetition($user, Sport::create(['name' => 'Running', 'slug' => 'running']));

    $this->actingAs($user)->get(route('competitions.index'))
        ->assertOk()
        ->assertSee('City Sprint')
        ->assertSee('Upcoming')
        ->assertDontSee('No upcoming competitions yet');
});

it('derives a display status from the schedule', function () {
    $user = User::factory()->create();
    $sport = Sport::create(['name' => 'Running', 'slug' => 'running']);

    expect(makeCompetition($user, $sport)->displayStatus())->toBe('upcoming')
        ->and(makeCompetition($user, $sport, ['start_time' => now()->subHour(), 'end_time' => now()->addHour()])->displayStatus())->toBe('in_progress')
        ->and(makeCompetition($user, $sport, ['start_time' => now()->subDays(2), 'end_time' => now()->subDay()])->displayStatus())->toBe('finished')
        ->and(makeCompetition($user, $sport, ['status' => 'cancelled'])->displayStatus())->toBe('cancelled');
});

it('formats result values per sport type', function () {
    $user = User::factory()->create();
    $track = makeCompetition($user, Sport::create(['name' => 'Sprint', 'slug' => 'sprint', 'result_type' => 'time']));
    $team = makeCompetition($user, Sport::create(['name' => 'Darts', 'slug' => 'darts', 'result_type' => 'score']));

    $fast = Result::create(['competition_id' => $track->id, 'registrant_type' => 'user', 'registrant_id' => $user->id, 'value' => 12.43]);
    $long = Result::create(['competition_id' => $track->id, 'registrant_type' => 'user', 'registrant_id' => User::factory()->create()->id, 'value' => 125.5]);
    $points = Result::create(['competition_id' => $team->id, 'registrant_type' => 'user', 'registrant_id' => $user->id, 'value' => 87]);

    expect($fast->fresh()->formattedValue())->toBe('12.43s')
        ->and($long->fresh()->formattedValue())->toBe('2:05.50')
        ->and($points->fresh()->formattedValue())->toBe('87 pts');
});

it('shows a leaderboard with the winner highlighted on the results tab', function () {
    $user = User::factory()->create(['name' => 'Alice Runner']);
    $competition = makeCompetition($user, Sport::create(['name' => 'Sprint', 'slug' => 'sprint', 'result_type' => 'time']));
    Result::create(['competition_id' => $competition->id, 'registrant_type' => 'user', 'registrant_id' => $user->id, 'value' => 11.9]);

    $this->actingAs($user)->get(route('competitions.show', $competition))
        ->assertOk()
        ->assertSee('Alice Runner')
        ->assertSee('11.90s')
        ->assertSee('bg-amber-50', false)
        ->assertSee('🏆');
});

it('records personal best history and shows the trend', function () {
    $user = User::factory()->create();
    $pb = PersonalBest::create(['user_id' => $user->id, 'exercise' => 'Squat', 'weight' => 100]);
    $pb->update(['weight' => 110]);
    $pb->update(['exercise' => 'Back squat']); // no weight change, no new entry

    expect($pb->entries()->count())->toBe(2);

    $this->actingAs($user)->get(route('pbs.index'))
        ->assertOk()
        ->assertSee('▲ 10');

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Back squat')
        ->assertSee('▲ 10');
});

it('shows connect and empty states on the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Connect Google Calendar')
        ->assertSee('No personal bests yet');
});

it('logs working weights with sets and lists them on the pb page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('workout-logs.store'), ['exercise' => 'Bench press', 'set_weights' => [80, 80, 85, 85]])
        ->assertRedirect(route('pbs.index').'#workout');

    $this->assertDatabaseHas('workout_logs', ['user_id' => $user->id, 'exercise' => 'Bench press']);

    $this->actingAs($user)->get(route('pbs.index'))
        ->assertOk()
        ->assertSee('Working weights')
        ->assertSee('Bench press')
        ->assertSee('2 × 80 · 2 × 85', false)
        ->assertSee('4 sets');

    $this->actingAs($user)->post(route('workout-logs.store'), ['exercise' => 'Squat', 'set_weights' => []])
        ->assertSessionHasErrors('set_weights');
});

it('prevents deleting another users workout log', function () {
    $owner = User::factory()->create();
    $log = $owner->workoutLogs()->create(['exercise' => 'Row', 'set_weights' => [60, 60, 55], 'performed_on' => now()]);

    $this->actingAs(User::factory()->create())->delete(route('workout-logs.destroy', $log))->assertForbidden();
    $this->assertDatabaseHas('workout_logs', ['id' => $log->id]);
});

it('saves a program day and logs a whole session from it', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('workout-days.store'), ['name' => 'Push', 'exercises' => "Bench press\nOverhead press\n\nBench press"])
        ->assertRedirect(route('pbs.index').'#workout');

    $day = $user->workoutDays()->first();
    expect($day->name)->toBe('Push')->and($day->exercises)->toBe(['Bench press', 'Overhead press']);

    $this->actingAs($user)->post(route('workout-logs.session'), [
        'day_name' => 'Push',
        'exercises' => [
            ['name' => 'Bench press', 'set_weights' => [80, 80, 85]],
            ['name' => 'Overhead press', 'set_weights' => ['', null]], // skipped
        ],
    ])->assertSessionHas('success');

    expect($user->workoutLogs()->count())->toBe(1);
    $this->assertDatabaseHas('workout_logs', ['user_id' => $user->id, 'exercise' => 'Bench press', 'day_name' => 'Push']);

    $this->actingAs($user)->get(route('pbs.index'))
        ->assertOk()
        ->assertSee('Push')
        ->assertSee('2 × 80 · 85', false)
        ->assertSee('Prefilled from your last session');
});

it('rejects a session with no weights and protects other users program days', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('workout-logs.session'), ['exercises' => [['name' => 'Squat', 'set_weights' => ['']]]])
        ->assertSessionHas('error');
    expect($user->workoutLogs()->count())->toBe(0);

    $day = User::factory()->create()->workoutDays()->create(['name' => 'Legs', 'exercises' => ['Squat']]);
    $this->actingAs($user)->put(route('workout-days.update', $day), ['name' => 'X', 'exercises' => 'Y'])->assertForbidden();
    $this->actingAs($user)->delete(route('workout-days.destroy', $day))->assertForbidden();
});
