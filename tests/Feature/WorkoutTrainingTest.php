<?php

use App\Models\User;
use App\Models\WorkoutDay;
use App\Models\WorkoutLog;

// --- Workout days (program days) ---

it('allows an authenticated user to create a program day', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('workout-days.store'), [
        'name' => 'Push Day',
        'exercises' => "Bench press\nOverhead press\nTricep dips",
    ]);

    $response->assertRedirect(route('pbs.index').'#workout');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('workout_days', [
        'user_id' => $user->id,
        'name' => 'Push Day',
    ]);

    $day = WorkoutDay::first();
    expect($day->exercises)->toBe(['Bench press', 'Overhead press', 'Tricep dips']);
});

it('deduplicates and trims exercises when creating a program day', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('workout-days.store'), [
        'name' => 'Leg Day',
        'exercises' => "Squat\n  Squat  \nDeadlift\n\n",
    ]);

    $day = WorkoutDay::first();
    expect($day->exercises)->toBe(['Squat', 'Deadlift']);
});

it('rejects a program day without any exercises', function () {
    $user = User::factory()->create();

    $response = $this->from(route('pbs.index'))
        ->actingAs($user)
        ->post(route('workout-days.store'), [
            'name' => 'Empty Day',
            'exercises' => "\n  \n",
        ]);

    $response->assertSessionHasErrors('exercises');
    $this->assertDatabaseMissing('workout_days', ['name' => 'Empty Day']);
});

it('prevents a user from exceeding the maximum number of program days', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < WorkoutDay::MAX_PER_USER; $i++) {
        WorkoutDay::create([
            'user_id' => $user->id,
            'name' => "Day {$i}",
            'exercises' => ['Exercise A'],
        ]);
    }

    $response = $this->actingAs($user)->post(route('workout-days.store'), [
        'name' => 'One Too Many',
        'exercises' => 'Exercise B',
    ]);

    $response->assertSessionHas('error');
    $this->assertDatabaseMissing('workout_days', ['name' => 'One Too Many']);
    expect(WorkoutDay::where('user_id', $user->id)->count())->toBe(WorkoutDay::MAX_PER_USER);
});

it('allows a user to update their own program day', function () {
    $user = User::factory()->create();
    $day = WorkoutDay::create([
        'user_id' => $user->id,
        'name' => 'Old Name',
        'exercises' => ['Squat'],
    ]);

    $response = $this->actingAs($user)->put(route('workout-days.update', $day), [
        'name' => 'New Name',
        'exercises' => "Squat\nLunge",
    ]);

    $response->assertRedirect(route('pbs.index').'#workout');
    $day->refresh();
    expect($day->name)->toBe('New Name');
    expect($day->exercises)->toBe(['Squat', 'Lunge']);
});

it('prevents a user from updating another users program day', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $day = WorkoutDay::create([
        'user_id' => $owner->id,
        'name' => 'Push Day',
        'exercises' => ['Bench press'],
    ]);

    $response = $this->actingAs($otherUser)->put(route('workout-days.update', $day), [
        'name' => 'Hijacked',
        'exercises' => 'Bench press',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('workout_days', ['id' => $day->id, 'name' => 'Push Day']);
});

it('prevents a user from deleting another users program day', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $day = WorkoutDay::create([
        'user_id' => $owner->id,
        'name' => 'Push Day',
        'exercises' => ['Bench press'],
    ]);

    $response = $this->actingAs($otherUser)->delete(route('workout-days.destroy', $day));

    $response->assertForbidden();
    $this->assertDatabaseHas('workout_days', ['id' => $day->id]);
});

it('allows a user to delete their own program day', function () {
    $user = User::factory()->create();
    $day = WorkoutDay::create([
        'user_id' => $user->id,
        'name' => 'Push Day',
        'exercises' => ['Bench press'],
    ]);

    $response = $this->actingAs($user)->delete(route('workout-days.destroy', $day));

    $response->assertRedirect(route('pbs.index').'#workout');
    $this->assertDatabaseMissing('workout_days', ['id' => $day->id]);
});

// --- Workout logs (single exercise) ---

it('allows an authenticated user to log a single exercise', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('workout-logs.store'), [
        'exercise' => 'Bench press',
        'set_weights' => [80, 80, 85],
        'performed_on' => now()->toDateString(),
    ]);

    $response->assertRedirect(route('pbs.index').'#workout');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('workout_logs', [
        'user_id' => $user->id,
        'exercise' => 'Bench press',
    ]);

    $log = WorkoutLog::first();
    expect($log->set_weights)->toEqual([80.0, 80.0, 85.0]);
});

it('defaults the performed_on date to today when omitted', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('workout-logs.store'), [
        'exercise' => 'Squat',
        'set_weights' => [100],
    ]);

    $log = WorkoutLog::first();
    expect($log->performed_on->toDateString())->toBe(now()->toDateString());
});

it('rejects a workout log with no sets', function () {
    $user = User::factory()->create();

    $response = $this->from(route('pbs.index'))
        ->actingAs($user)
        ->post(route('workout-logs.store'), [
            'exercise' => 'Squat',
            'set_weights' => [],
        ]);

    $response->assertSessionHasErrors('set_weights');
});

it('rejects a workout log dated in the future', function () {
    $user = User::factory()->create();

    $response = $this->from(route('pbs.index'))
        ->actingAs($user)
        ->post(route('workout-logs.store'), [
            'exercise' => 'Squat',
            'set_weights' => [100],
            'performed_on' => now()->addDay()->toDateString(),
        ]);

    $response->assertSessionHasErrors('performed_on');
});

it('prevents a user from deleting another users workout log', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $log = WorkoutLog::create([
        'user_id' => $owner->id,
        'exercise' => 'Deadlift',
        'set_weights' => [120],
        'performed_on' => now()->toDateString(),
    ]);

    $response = $this->actingAs($otherUser)->delete(route('workout-logs.destroy', $log));

    $response->assertForbidden();
    $this->assertDatabaseHas('workout_logs', ['id' => $log->id]);
});

it('allows a user to delete their own workout log', function () {
    $user = User::factory()->create();
    $log = WorkoutLog::create([
        'user_id' => $user->id,
        'exercise' => 'Deadlift',
        'set_weights' => [120],
        'performed_on' => now()->toDateString(),
    ]);

    $response = $this->actingAs($user)->delete(route('workout-logs.destroy', $log));

    $response->assertRedirect(route('pbs.index').'#workout');
    $this->assertDatabaseMissing('workout_logs', ['id' => $log->id]);
});

// --- Workout logs (whole session) ---

it('logs one entry per exercise with at least one filled-in weight', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('workout-logs.session'), [
        'day_name' => 'Push Day',
        'performed_on' => now()->toDateString(),
        'exercises' => [
            ['name' => 'Bench press', 'set_weights' => [80, 85]],
            ['name' => 'Overhead press', 'set_weights' => [null, '']],
            ['name' => 'Tricep dips', 'set_weights' => [20]],
        ],
    ]);

    $response->assertRedirect(route('pbs.index').'#workout');
    $response->assertSessionHas('success', 'Workout logged (2 exercises).');

    $this->assertDatabaseHas('workout_logs', ['exercise' => 'Bench press', 'day_name' => 'Push Day']);
    $this->assertDatabaseHas('workout_logs', ['exercise' => 'Tricep dips', 'day_name' => 'Push Day']);
    $this->assertDatabaseMissing('workout_logs', ['exercise' => 'Overhead press']);
    expect(WorkoutLog::where('user_id', $user->id)->count())->toBe(2);
});

it('rejects a session log where every exercise is left blank', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('workout-logs.session'), [
        'exercises' => [
            ['name' => 'Bench press', 'set_weights' => [null]],
            ['name' => 'Overhead press', 'set_weights' => []],
        ],
    ]);

    $response->assertSessionHas('error', 'Enter at least one weight to log this workout.');
    $this->assertDatabaseCount('workout_logs', 0);
});

// --- Model behaviour ---

it('collapses consecutive equal sets into grouped weight summaries', function () {
    $user = User::factory()->create();
    $log = WorkoutLog::create([
        'user_id' => $user->id,
        'exercise' => 'Bench press',
        'set_weights' => [80, 80, 85],
        'performed_on' => now()->toDateString(),
    ]);

    expect($log->groupedSets())->toBe([[2, 80.0], [1, 85.0]]);
    expect($log->weightSummary())->toBe('2 × 80 · 85');
    expect($log->setCount())->toBe(3);
});

// --- Access control ---

it('redirects guests away from workout routes', function () {
    $this->post(route('workout-days.store'), [])->assertRedirect(route('login'));
    $this->post(route('workout-logs.store'), [])->assertRedirect(route('login'));
    $this->post(route('workout-logs.session'), [])->assertRedirect(route('login'));
});
