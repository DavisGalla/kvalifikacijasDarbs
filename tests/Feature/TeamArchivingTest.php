<?php

use App\Models\Competition;
use App\Models\Matchup;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Sport;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

function archivableTeam(string $name = 'Riga Lions'): Team
{
    $captain = User::factory()->create();
    $team = Team::create([
        'name' => $name,
        'sport_id' => Sport::firstOrCreate(['slug' => 'basketball'], ['name' => 'Basketball'])->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);
    $team->members()->create(['user_id' => $captain->id, 'role' => 'captain', 'joined_at' => now()]);

    return $team;
}

function archivingCompetition(Team $team, array $overrides = []): Competition
{
    return Competition::create(array_merge([
        'organizer_id' => User::factory()->create()->id,
        'sport_id' => $team->sport_id,
        'title' => 'City Cup',
        'description' => 'A team tournament.',
        'location' => 'Arena',
        'start_time' => now()->subDays(10),
        'end_time' => now()->subDays(10)->addHours(4),
        'registration_deadline' => now()->subDays(15),
        'registration_mode' => 'team',
        'min_team_members' => 1,
        'max_team_members' => 5,
        'status' => 'published',
    ], $overrides));
}

function archivingRegistration(Team $team, Competition $competition, string $status = 'confirmed'): Registration
{
    return Registration::create([
        'competition_id' => $competition->id,
        'registrant_type' => 'team',
        'registrant_id' => $team->id,
        'status' => $status,
        'registered_at' => now()->subDays(20),
    ]);
}

// --- Deleting a team without history ---

it('permanently deletes a team that never took part in a competition', function () {
    $team = archivableTeam();

    $response = $this->actingAs($team->captain)->delete(route('teams.destroy', $team));

    $response->assertRedirect(route('teams.index'));
    $response->assertSessionHas('success', 'Team deleted successfully.');
    expect(Team::withTrashed()->find($team->id))->toBeNull();
    $this->assertDatabaseMissing('team_members', ['team_id' => $team->id]);
});

// --- Archiving a team with history ---

it('archives instead of deleting a team that took part in a past competition', function () {
    $team = archivableTeam();
    archivingRegistration($team, archivingCompetition($team));

    $response = $this->actingAs($team->captain)->delete(route('teams.destroy', $team));

    $response->assertRedirect(route('teams.index'));
    $response->assertSessionHas('success', 'Team archived. Its competition history has been kept.');
    expect(Team::find($team->id))->toBeNull();
    expect(Team::withTrashed()->find($team->id)->archived_at)->not->toBeNull();
    $this->assertDatabaseHas('registrations', ['registrant_type' => 'team', 'registrant_id' => $team->id]);
});

it('archives a team whose only history is a matchup', function () {
    $team = archivableTeam();
    $opponent = archivableTeam('Daugava');
    Matchup::create([
        'competition_id' => archivingCompetition($team)->id,
        'home_team_id' => $opponent->id,
        'away_team_id' => $team->id,
        'home_score' => 2,
        'away_score' => 1,
    ]);

    $this->actingAs($team->captain)->delete(route('teams.destroy', $team));

    expect(Team::withTrashed()->find($team->id)->trashed())->toBeTrue();
    $this->assertDatabaseHas('matchups', ['away_team_id' => $team->id]);
});

it('keeps the winner, results and matchups of an archived team visible on the competition', function () {
    $team = archivableTeam();
    $opponent = archivableTeam('Daugava');
    $competition = archivingCompetition($team, ['winner_type' => 'team', 'winner_id' => $team->id]);
    archivingRegistration($team, $competition);
    Result::create([
        'competition_id' => $competition->id,
        'registrant_type' => 'team',
        'registrant_id' => $team->id,
        'value' => 3,
        'position' => 1,
    ]);
    Matchup::create([
        'competition_id' => $competition->id,
        'home_team_id' => $team->id,
        'away_team_id' => $opponent->id,
        'home_score' => 3,
        'away_score' => 0,
    ]);

    $this->actingAs($team->captain)->delete(route('teams.destroy', $team));

    $competition->refresh();
    expect($competition->winner)->toBeInstanceOf(Team::class);
    expect($competition->winner->name)->toBe('Riga Lions');
    expect($competition->results()->first()->registrant->name)->toBe('Riga Lions');
    expect($competition->matchups()->first()->homeTeam->name)->toBe('Riga Lions');

    $this->actingAs(User::factory()->create())
        ->get(route('competitions.show', $competition))
        ->assertOk()
        ->assertSee('Winner: Riga Lions')
        ->assertDontSee('Unknown');
});

it('keeps an archived team registration in the captain competition history', function () {
    $team = archivableTeam();
    archivingRegistration($team, archivingCompetition($team));

    $this->actingAs($team->captain)->delete(route('teams.destroy', $team));

    $this->actingAs($team->captain)
        ->get(route('competitions.history'))
        ->assertOk()
        ->assertSee('City Cup');
});

it('removes pending invitations when a team is archived', function () {
    $team = archivableTeam();
    archivingRegistration($team, archivingCompetition($team));
    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'invited_user_id' => User::factory()->create()->id,
        'invited_by' => $team->captain_id,
        'status' => 'pending',
    ]);

    $this->actingAs($team->captain)->delete(route('teams.destroy', $team));

    $this->assertDatabaseMissing('team_invitations', ['id' => $invitation->id]);
});

// --- Using an archived team ---

it('hides an archived team from listings, its page and joining', function () {
    $team = archivableTeam();
    archivingRegistration($team, archivingCompetition($team));
    $this->actingAs($team->captain)->delete(route('teams.destroy', $team));
    $visitor = User::factory()->create();

    $this->actingAs($visitor)->get(route('teams.index'))->assertOk()->assertDontSee('Riga Lions');
    $this->actingAs($visitor)->get(route('teams.show', $team))->assertNotFound();
    $this->actingAs($visitor)->post(route('teams.join', $team))->assertNotFound();
});

it('does not let an archived team register for a competition', function () {
    $team = archivableTeam();
    archivingRegistration($team, archivingCompetition($team));
    $this->actingAs($team->captain)->delete(route('teams.destroy', $team));
    $upcoming = archivingCompetition($team, [
        'title' => 'Autumn Cup',
        'start_time' => now()->addDays(10),
        'end_time' => now()->addDays(10)->addHours(4),
        'registration_deadline' => now()->addDays(5),
    ]);

    $response = $this->actingAs($team->captain)->post(route('competitions.register', $upcoming), [
        'team_id' => $team->id,
    ]);

    $response->assertSessionHas('error', 'You can only register a team you captain for this sport.');
    $this->assertDatabaseMissing('registrations', ['competition_id' => $upcoming->id]);
});

// --- Refusing deletion ---

it('refuses to delete a team registered for an upcoming competition', function () {
    $team = archivableTeam();
    archivingRegistration($team, archivingCompetition($team, [
        'start_time' => now()->addDays(10),
        'end_time' => now()->addDays(10)->addHours(4),
        'registration_deadline' => now()->addDays(5),
    ]), 'pending');

    $response = $this->actingAs($team->captain)->delete(route('teams.destroy', $team));

    $response->assertSessionHas('error', 'This team is registered for an upcoming competition. Withdraw it from the competition before deleting the team.');
    expect(Team::find($team->id))->not->toBeNull();
});

it('archives a team whose upcoming registration was cancelled', function () {
    $team = archivableTeam();
    archivingRegistration($team, archivingCompetition($team, [
        'start_time' => now()->addDays(10),
        'end_time' => now()->addDays(10)->addHours(4),
        'registration_deadline' => now()->addDays(5),
    ]), 'cancelled');

    $response = $this->actingAs($team->captain)->delete(route('teams.destroy', $team));

    $response->assertSessionHas('success', 'Team archived. Its competition history has been kept.');
    expect(Team::withTrashed()->find($team->id)->trashed())->toBeTrue();
});
