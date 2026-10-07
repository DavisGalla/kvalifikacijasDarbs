<?php

use App\Models\Competition;
use App\Models\Sport;
use App\Models\User;

function validCompetitionPayload(Sport $sport, array $overrides = []): array
{
    return array_merge([
        'sport_id' => $sport->id,
        'title' => 'Spring 5K',
        'description' => 'A spring race around the park.',
        'location' => 'City Park',
        'start_time' => now()->addDays(10)->toDateTimeString(),
        'end_time' => now()->addDays(10)->addHours(2)->toDateTimeString(),
        'registration_deadline' => now()->addDays(5)->toDateTimeString(),
        'registration_mode' => 'individual',
        'status' => 'published',
    ], $overrides);
}

// --- Creating a competition ---

it('allows an authenticated user to create a competition as its organizer', function () {
    $user = User::factory()->create();
    $sport = Sport::create(['name' => 'Running', 'slug' => 'running']);

    $response = $this->actingAs($user)->post(route('competitions.store'), validCompetitionPayload($sport));

    $response->assertRedirect(route('competitions.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('competitions', [
        'organizer_id' => $user->id,
        'sport_id' => $sport->id,
        'title' => 'Spring 5K',
        'status' => 'published',
    ]);
});

it('clears team size limits when the registration mode is individual', function () {
    $user = User::factory()->create();
    $sport = Sport::create(['name' => 'Running', 'slug' => 'running']);

    $this->actingAs($user)->post(route('competitions.store'), validCompetitionPayload($sport, [
        'min_team_members' => 3,
        'max_team_members' => 6,
    ]));

    $competition = Competition::first();
    expect($competition->min_team_members)->toBeNull();
    expect($competition->max_team_members)->toBeNull();
});

it('keeps team size limits when the registration mode is team', function () {
    $user = User::factory()->create();
    $sport = Sport::create(['name' => 'Football', 'slug' => 'football']);

    $this->actingAs($user)->post(route('competitions.store'), validCompetitionPayload($sport, [
        'registration_mode' => 'team',
        'min_team_members' => 5,
        'max_team_members' => 11,
    ]));

    $competition = Competition::first();
    expect($competition->min_team_members)->toBe(5);
    expect($competition->max_team_members)->toBe(11);
});

it('requires team size limits when the registration mode is team', function () {
    $user = User::factory()->create();
    $sport = Sport::create(['name' => 'Football', 'slug' => 'football']);

    $response = $this->from(route('competitions.create'))
        ->actingAs($user)
        ->post(route('competitions.store'), validCompetitionPayload($sport, [
            'registration_mode' => 'team',
        ]));

    $response->assertSessionHasErrors(['min_team_members', 'max_team_members']);
    $this->assertDatabaseCount('competitions', 0);
});

it('rejects a competition whose end time is before its start time', function () {
    $user = User::factory()->create();
    $sport = Sport::create(['name' => 'Running', 'slug' => 'running']);

    $response = $this->from(route('competitions.create'))
        ->actingAs($user)
        ->post(route('competitions.store'), validCompetitionPayload($sport, [
            'start_time' => now()->addDays(10)->toDateTimeString(),
            'end_time' => now()->addDays(9)->toDateTimeString(),
        ]));

    $response->assertSessionHasErrors('end_time');
    $this->assertDatabaseCount('competitions', 0);
});

it('rejects a competition with a registration deadline after it starts', function () {
    $user = User::factory()->create();
    $sport = Sport::create(['name' => 'Running', 'slug' => 'running']);

    $response = $this->from(route('competitions.create'))
        ->actingAs($user)
        ->post(route('competitions.store'), validCompetitionPayload($sport, [
            'registration_deadline' => now()->addDays(11)->toDateTimeString(),
        ]));

    $response->assertSessionHasErrors('registration_deadline');
    $this->assertDatabaseCount('competitions', 0);
});

it('rejects a competition starting in the past', function () {
    $user = User::factory()->create();
    $sport = Sport::create(['name' => 'Running', 'slug' => 'running']);

    $response = $this->from(route('competitions.create'))
        ->actingAs($user)
        ->post(route('competitions.store'), validCompetitionPayload($sport, [
            'start_time' => now()->subDay()->toDateTimeString(),
        ]));

    $response->assertSessionHasErrors('start_time');
    $this->assertDatabaseCount('competitions', 0);
});

it('rejects a competition with a non existent sport', function () {
    $user = User::factory()->create();
    $sport = Sport::create(['name' => 'Running', 'slug' => 'running']);

    $response = $this->from(route('competitions.create'))
        ->actingAs($user)
        ->post(route('competitions.store'), validCompetitionPayload($sport, [
            'sport_id' => 999999,
        ]));

    $response->assertSessionHasErrors('sport_id');
    $this->assertDatabaseCount('competitions', 0);
});

// --- Viewing competitions ---

it('only lists published competitions that have not ended', function () {
    $user = User::factory()->create();
    $organizer = User::factory()->create();
    $sport = Sport::create(['name' => 'Running', 'slug' => 'running']);

    $upcoming = Competition::create([
        'organizer_id' => $organizer->id,
        'sport_id' => $sport->id,
        'title' => 'Upcoming Race',
        'description' => 'desc',
        'location' => 'loc',
        'start_time' => now()->addDays(5),
        'end_time' => now()->addDays(5)->addHours(2),
        'registration_deadline' => now()->addDays(2),
        'status' => 'published',
    ]);
    $draft = Competition::create([
        'organizer_id' => $organizer->id,
        'sport_id' => $sport->id,
        'title' => 'Draft Race',
        'description' => 'desc',
        'location' => 'loc',
        'start_time' => now()->addDays(5),
        'end_time' => now()->addDays(5)->addHours(2),
        'registration_deadline' => now()->addDays(2),
        'status' => 'draft',
    ]);
    $finished = Competition::create([
        'organizer_id' => $organizer->id,
        'sport_id' => $sport->id,
        'title' => 'Finished Race',
        'description' => 'desc',
        'location' => 'loc',
        'start_time' => now()->subDays(5),
        'end_time' => now()->subDays(5)->addHours(2),
        'registration_deadline' => now()->subDays(8),
        'status' => 'published',
    ]);

    $response = $this->actingAs($user)->get(route('competitions.index'));

    $response->assertOk()
        ->assertSee('Upcoming Race')
        ->assertDontSee('Draft Race')
        ->assertDontSee('Finished Race');
});

it('returns a 404 for a draft competition shown to a regular visitor', function () {
    $user = User::factory()->create();
    $organizer = User::factory()->create();
    $sport = Sport::create(['name' => 'Running', 'slug' => 'running']);
    $draft = Competition::create([
        'organizer_id' => $organizer->id,
        'sport_id' => $sport->id,
        'title' => 'Draft Race',
        'description' => 'desc',
        'location' => 'loc',
        'start_time' => now()->addDays(5),
        'end_time' => now()->addDays(5)->addHours(2),
        'registration_deadline' => now()->addDays(2),
        'status' => 'draft',
    ]);

    $this->actingAs($user)->get(route('competitions.show', $draft))->assertNotFound();
});

// --- Access control ---

it('redirects guests away from competition management routes', function () {
    $this->post(route('competitions.store'), [])->assertRedirect(route('login'));
    $this->get(route('competitions.create'))->assertRedirect(route('login'));
});
