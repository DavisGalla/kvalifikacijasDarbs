<?php

use App\Models\Competition;
use App\Models\Registration;
use App\Models\Sport;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

/**
 * A public team with `$memberCount` members (captain included).
 */
function teamWithMembers(int $memberCount): Team
{
    $captain = User::factory()->create();
    $team = Team::create([
        'name' => 'Riga Lions',
        'sport_id' => Sport::create(['name' => 'Basketball', 'slug' => 'basketball'])->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);

    $team->members()->create(['user_id' => $captain->id, 'role' => 'captain', 'joined_at' => now()]);

    foreach (User::factory()->count($memberCount - 1)->create() as $user) {
        $team->members()->create(['user_id' => $user->id, 'role' => 'member', 'joined_at' => now()]);
    }

    return $team;
}

function teamCompetition(Team $team, array $overrides = []): Competition
{
    return Competition::create(array_merge([
        'organizer_id' => User::factory()->create()->id,
        'sport_id' => $team->sport_id,
        'title' => 'City Cup',
        'description' => 'A team tournament.',
        'location' => 'Arena',
        'start_time' => now()->addDays(10),
        'end_time' => now()->addDays(10)->addHours(4),
        'registration_deadline' => now()->addDays(5),
        'registration_mode' => 'team',
        'min_team_members' => 2,
        'max_team_members' => 5,
        'status' => 'published',
    ], $overrides));
}

function registerTeam(Team $team, Competition $competition, string $status = 'pending'): Registration
{
    return Registration::create([
        'competition_id' => $competition->id,
        'registrant_type' => 'team',
        'registrant_id' => $team->id,
        'status' => $status,
        'registered_at' => now(),
    ]);
}

// --- Joining a team registered for a competition ---

it('prevents joining a public team that would exceed a registered competition maximum', function () {
    $team = teamWithMembers(5);
    registerTeam($team, teamCompetition($team, ['max_team_members' => 5]));
    $joiner = User::factory()->create();

    $response = $this->actingAs($joiner)->post(route('teams.join', $team));

    $response->assertSessionHas('error', 'This team is registered for City Cup, which allows at most 5 members.');
    expect($team->members()->count())->toBe(5);
    $this->assertDatabaseMissing('team_members', ['team_id' => $team->id, 'user_id' => $joiner->id]);
});

it('allows joining a registered team that still has room under the competition maximum', function () {
    $team = teamWithMembers(4);
    registerTeam($team, teamCompetition($team, ['max_team_members' => 5]));

    $response = $this->actingAs(User::factory()->create())->post(route('teams.join', $team));

    $response->assertSessionHas('success');
    expect($team->members()->count())->toBe(5);
});

it('ignores the maximum of competitions that have already started', function () {
    $team = teamWithMembers(5);
    registerTeam($team, teamCompetition($team, [
        'start_time' => now()->subHour(),
        'end_time' => now()->addHours(3),
        'registration_deadline' => now()->subDay(),
    ]));

    $response = $this->actingAs(User::factory()->create())->post(route('teams.join', $team));

    $response->assertSessionHas('success');
    expect($team->members()->count())->toBe(6);
});

it('ignores the maximum of cancelled registrations', function () {
    $team = teamWithMembers(5);
    registerTeam($team, teamCompetition($team), 'cancelled');

    $response = $this->actingAs(User::factory()->create())->post(route('teams.join', $team));

    $response->assertSessionHas('success');
    expect($team->members()->count())->toBe(6);
});

// --- Accepting an invitation to a team registered for a competition ---

it('prevents accepting an invitation that would exceed a registered competition maximum', function () {
    $team = teamWithMembers(5);
    registerTeam($team, teamCompetition($team, ['max_team_members' => 5]));
    $invitee = User::factory()->create();
    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'invited_user_id' => $invitee->id,
        'invited_by' => $team->captain_id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($invitee)->post(route('teams.invitations.accept', $invitation));

    $response->assertSessionHas('error', 'This team is registered for City Cup, which allows at most 5 members.');
    expect($team->members()->count())->toBe(5);
    expect($invitation->fresh()->status)->toBe('pending');
});

// --- Leaving a team registered for a competition ---

it('prevents leaving when the team would drop below a registered competition minimum', function () {
    $team = teamWithMembers(3);
    registerTeam($team, teamCompetition($team, ['min_team_members' => 3]));
    $member = $team->members()->where('role', 'member')->first()->user;

    $response = $this->actingAs($member)->delete(route('teams.leave', $team));

    $response->assertSessionHas('error', 'This team is registered for City Cup, which requires at least 3 members.');
    expect($team->members()->count())->toBe(3);
});

// --- Registering a team for a competition ---

it('rejects registering a team with more members than the competition allows', function () {
    $team = teamWithMembers(6);
    $competition = teamCompetition($team, ['max_team_members' => 5]);

    $response = $this->actingAs($team->captain)->post(route('competitions.register', $competition), [
        'team_id' => $team->id,
    ]);

    $response->assertSessionHas('error', 'Your team has too many members. This competition allows at most 5.');
    $this->assertDatabaseCount('registrations', 0);
});

it('rejects registering a team with fewer members than the competition requires', function () {
    $team = teamWithMembers(1);
    $competition = teamCompetition($team, ['min_team_members' => 2]);

    $response = $this->actingAs($team->captain)->post(route('competitions.register', $competition), [
        'team_id' => $team->id,
    ]);

    $response->assertSessionHas('error', 'Your team needs at least 2 members to register.');
    $this->assertDatabaseCount('registrations', 0);
});

it('registers a team whose size fits the competition limits', function () {
    $team = teamWithMembers(5);
    $competition = teamCompetition($team, ['max_team_members' => 5]);

    $response = $this->actingAs($team->captain)->post(route('competitions.register', $competition), [
        'team_id' => $team->id,
    ]);

    $response->assertSessionHas('success');
    $this->assertDatabaseHas('registrations', [
        'competition_id' => $competition->id,
        'registrant_type' => 'team',
        'registrant_id' => $team->id,
        'status' => 'pending',
    ]);
});
