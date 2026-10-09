<?php

use App\Models\Competition;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function deletionCompetition(array $overrides = []): Competition
{
    return Competition::create(array_merge([
        'organizer_id' => User::factory()->create()->id,
        'sport_id' => Sport::firstOrCreate(['slug' => 'running'], ['name' => 'Running'])->id,
        'title' => 'Spring 5K',
        'description' => 'A spring race.',
        'location' => 'City Park',
        'start_time' => now()->subDays(10),
        'end_time' => now()->subDays(10)->addHours(2),
        'registration_deadline' => now()->subDays(15),
        'registration_mode' => 'individual',
        'status' => 'published',
    ], $overrides));
}

function upcomingDeletionCompetition(array $overrides = []): Competition
{
    return deletionCompetition(array_merge([
        'title' => 'Autumn 10K',
        'start_time' => now()->addDays(10),
        'end_time' => now()->addDays(10)->addHours(2),
        'registration_deadline' => now()->addDays(5),
    ], $overrides));
}

function registerForDeletionTest(Competition $competition, string $type, int $id, string $status = 'confirmed'): Registration
{
    return Registration::create([
        'competition_id' => $competition->id,
        'registrant_type' => $type,
        'registrant_id' => $id,
        'status' => $status,
        'registered_at' => now()->subDays(20),
    ]);
}

function deleteAccount(User $user)
{
    return test()->actingAs($user)->from('/profile')->delete('/profile', ['email_confirmation' => $user->email]);
}

function deletionTeam(User $captain, array $memberUsers = []): Team
{
    $team = Team::create([
        'name' => 'Riga Lions',
        'sport_id' => Sport::firstOrCreate(['slug' => 'basketball'], ['name' => 'Basketball'])->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);
    $team->members()->create(['user_id' => $captain->id, 'role' => 'captain', 'joined_at' => now()]);

    foreach ($memberUsers as $user) {
        $team->members()->create(['user_id' => $user->id, 'role' => 'member', 'joined_at' => now()]);
    }

    return $team;
}

// --- Users with competition history are anonymized ---

it('anonymizes instead of deleting a user who took part in a competition', function () {
    $user = User::factory()->create(['name' => 'Anna Berzina', 'email' => 'anna@example.com']);
    $competition = deletionCompetition(['winner_type' => 'user', 'winner_id' => $user->id]);
    registerForDeletionTest($competition, 'user', $user->id);
    Result::create(['competition_id' => $competition->id, 'registrant_type' => 'user', 'registrant_id' => $user->id, 'value' => 20, 'position' => 1]);

    deleteAccount($user)->assertSessionHasNoErrors()->assertRedirect('/');

    $this->assertGuest();
    expect(User::find($user->id))->toBeNull();

    $anonymized = User::withTrashed()->find($user->id);
    expect($anonymized->anonymized_at)->not->toBeNull();
    expect($anonymized->name)->toBe('Deleted user');
    expect($anonymized->email)->not->toContain('anna');
    expect($anonymized->google_access_token)->toBeNull();

    $competition->refresh();
    expect($competition->winner->name)->toBe('Deleted user');
    expect($competition->results()->first()->registrant->name)->toBe('Deleted user');
    $this->assertDatabaseHas('registrations', ['registrant_type' => 'user', 'registrant_id' => $user->id]);
});

it('keeps the competitions of an organizer who deletes their account', function () {
    $organizer = User::factory()->create();
    $participant = User::factory()->create();
    $competition = deletionCompetition(['organizer_id' => $organizer->id]);
    registerForDeletionTest($competition, 'user', $participant->id);

    deleteAccount($organizer)->assertRedirect('/');

    expect(Competition::find($competition->id))->not->toBeNull();
    expect($competition->fresh()->organizer->name)->toBe('Deleted user');
    $this->assertDatabaseHas('registrations', ['competition_id' => $competition->id, 'registrant_id' => $participant->id]);
});

it('archives the teams of a captain with competition history', function () {
    $captain = User::factory()->create();
    $team = deletionTeam($captain);
    registerForDeletionTest(deletionCompetition(['registration_mode' => 'team']), 'team', $team->id);

    deleteAccount($captain)->assertRedirect('/');

    $archived = Team::withTrashed()->find($team->id);
    expect($archived->trashed())->toBeTrue();
    expect($archived->captain->name)->toBe('Deleted user');
});

it('gives a fresh account, not the anonymized one, when the person signs in with Google again', function () {
    $user = User::factory()->create(['email' => 'anna@example.com', 'google_id' => 'google-123']);
    registerForDeletionTest(deletionCompetition(), 'user', $user->id);
    deleteAccount($user);

    $googleUser = (new SocialiteUser)->map([
        'id' => 'google-123',
        'name' => 'Anna Berzina',
        'email' => 'anna@example.com',
        'avatar' => null,
    ]);
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->andReturn($googleUser);
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

    $newUser = User::where('email', 'anna@example.com')->sole();
    expect($newUser->id)->not->toBe($user->id);
    $this->assertAuthenticatedAs($newUser);
    expect(User::withTrashed()->find($user->id)->name)->toBe('Deleted user');
});

it('does not let an anonymized user be invited to a team', function () {
    $user = User::factory()->create(['username' => 'anna']);
    registerForDeletionTest(deletionCompetition(), 'user', $user->id);
    deleteAccount($user);
    $captain = User::factory()->create();
    $team = deletionTeam($captain);

    $this->actingAs($captain)
        ->from(route('teams.show', $team))
        ->post(route('teams.invite', $team), ['username' => "deleted_{$user->id}"])
        ->assertSessionHasErrors('username');
});

// --- Users without history are deleted ---

it('permanently deletes a user without competition history and removes their memberships', function () {
    $captain = User::factory()->create();
    $member = User::factory()->create();
    $team = deletionTeam($captain, [$member]);

    deleteAccount($member)->assertRedirect('/');

    expect(User::withTrashed()->find($member->id))->toBeNull();
    $this->assertDatabaseMissing('team_members', ['team_id' => $team->id, 'user_id' => $member->id]);
});

// --- Upcoming commitments block deletion ---

it('refuses to delete an account registered for an upcoming competition', function () {
    $user = User::factory()->create();
    registerForDeletionTest(upcomingDeletionCompetition(), 'user', $user->id, 'pending');

    deleteAccount($user)
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrorsIn('userDeletion', ['account' => 'You are registered for Autumn 10K. Withdraw from upcoming competitions before deleting your account.']);

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->name)->not->toBe('Deleted user');
});

it('refuses to delete the account of an organizer with an unfinished competition', function () {
    $organizer = User::factory()->create();
    upcomingDeletionCompetition(['organizer_id' => $organizer->id]);

    deleteAccount($organizer)
        ->assertSessionHasErrorsIn('userDeletion', ['account' => 'You organize Autumn 10K, which has not finished yet. Cancel it or wait until it ends before deleting your account.']);

    expect($organizer->fresh())->not->toBeNull();
});

it('refuses to delete the account of a captain whose team is registered for an upcoming competition', function () {
    $captain = User::factory()->create();
    $team = deletionTeam($captain);
    registerForDeletionTest(upcomingDeletionCompetition(['registration_mode' => 'team']), 'team', $team->id, 'pending');

    deleteAccount($captain)->assertSessionHasErrorsIn('userDeletion', 'account');

    expect(Team::find($team->id))->not->toBeNull();
    expect($captain->fresh())->not->toBeNull();
});

it('refuses to delete the account of a member the team needs for an upcoming competition', function () {
    $captain = User::factory()->create();
    $member = User::factory()->create();
    $team = deletionTeam($captain, [$member]);
    registerForDeletionTest(upcomingDeletionCompetition([
        'registration_mode' => 'team',
        'min_team_members' => 2,
        'max_team_members' => 5,
    ]), 'team', $team->id, 'pending');

    deleteAccount($member)
        ->assertSessionHasErrorsIn('userDeletion', ['account' => 'This team is registered for Autumn 10K, which requires at least 2 members.']);

    $this->assertDatabaseHas('team_members', ['team_id' => $team->id, 'user_id' => $member->id]);
});
