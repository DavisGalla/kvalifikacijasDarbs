<?php

use App\Models\Sport;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

// --- Creating a team ---

it('creates a team and makes the creator its captain member', function () {
    $user = User::factory()->create();
    $sport = Sport::create(['name' => 'Basketball', 'slug' => 'basketball']);

    $response = $this->actingAs($user)->post(route('teams.store'), [
        'name' => 'Riga Lions',
        'sport_id' => $sport->id,
        'is_public' => true,
    ]);

    $team = Team::first();
    $response->assertRedirect(route('teams.show', $team));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('teams', [
        'name' => 'Riga Lions',
        'captain_id' => $user->id,
        'sport_id' => $sport->id,
    ]);
    $this->assertDatabaseHas('team_members', [
        'team_id' => $team->id,
        'user_id' => $user->id,
        'role' => 'captain',
    ]);
});

it('rejects a team without a name or a valid sport', function () {
    $user = User::factory()->create();

    $response = $this->from(route('teams.create'))
        ->actingAs($user)
        ->post(route('teams.store'), [
            'name' => '',
            'sport_id' => 999999,
        ]);

    $response->assertSessionHasErrors(['name', 'sport_id']);
    $this->assertDatabaseCount('teams', 0);
});

// --- Viewing a team ---

it('allows anyone to view a public team', function () {
    $captain = User::factory()->create();
    $viewer = User::factory()->create();
    $sport = Sport::create(['name' => 'Basketball', 'slug' => 'basketball']);
    $team = Team::create([
        'name' => 'Riga Lions',
        'sport_id' => $sport->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);

    $this->actingAs($viewer)->get(route('teams.show', $team))->assertOk();
});

it('prevents non members from viewing a private team', function () {
    $captain = User::factory()->create();
    $outsider = User::factory()->create();
    $sport = Sport::create(['name' => 'Basketball', 'slug' => 'basketball']);
    $team = Team::create([
        'name' => 'Private Squad',
        'sport_id' => $sport->id,
        'captain_id' => $captain->id,
        'is_public' => false,
    ]);

    $this->actingAs($outsider)->get(route('teams.show', $team))->assertNotFound();
    $this->actingAs($captain)->get(route('teams.show', $team))->assertOk();
});

// --- Joining a team ---

it('allows a user to join a public team', function () {
    $captain = User::factory()->create();
    $joiner = User::factory()->create();
    $sport = Sport::create(['name' => 'Basketball', 'slug' => 'basketball']);
    $team = Team::create([
        'name' => 'Riga Lions',
        'sport_id' => $sport->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);

    $response = $this->actingAs($joiner)->post(route('teams.join', $team));

    $response->assertSessionHas('success');
    $this->assertDatabaseHas('team_members', [
        'team_id' => $team->id,
        'user_id' => $joiner->id,
        'role' => 'member',
    ]);
});

it('prevents joining a private team directly', function () {
    $captain = User::factory()->create();
    $joiner = User::factory()->create();
    $sport = Sport::create(['name' => 'Basketball', 'slug' => 'basketball']);
    $team = Team::create([
        'name' => 'Private Squad',
        'sport_id' => $sport->id,
        'captain_id' => $captain->id,
        'is_public' => false,
    ]);

    $this->actingAs($joiner)->post(route('teams.join', $team))->assertNotFound();
    $this->assertDatabaseMissing('team_members', ['team_id' => $team->id, 'user_id' => $joiner->id]);
});

it('prevents a user from joining a team twice', function () {
    $captain = User::factory()->create();
    $joiner = User::factory()->create();
    $sport = Sport::create(['name' => 'Basketball', 'slug' => 'basketball']);
    $team = Team::create([
        'name' => 'Riga Lions',
        'sport_id' => $sport->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);
    $team->members()->create(['user_id' => $joiner->id, 'role' => 'member', 'joined_at' => now()]);

    $response = $this->actingAs($joiner)->post(route('teams.join', $team));

    $response->assertSessionHas('error');
    expect($team->members()->where('user_id', $joiner->id)->count())->toBe(1);
});

// --- Leaving a team ---

it('allows a member to leave a team', function () {
    $captain = User::factory()->create();
    $member = User::factory()->create();
    $sport = Sport::create(['name' => 'Basketball', 'slug' => 'basketball']);
    $team = Team::create([
        'name' => 'Riga Lions',
        'sport_id' => $sport->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);
    $team->members()->create(['user_id' => $member->id, 'role' => 'member', 'joined_at' => now()]);

    $response = $this->actingAs($member)->delete(route('teams.leave', $team));

    $response->assertRedirect(route('teams.index'));
    $this->assertDatabaseMissing('team_members', ['team_id' => $team->id, 'user_id' => $member->id]);
});

it('does not allow the captain to leave their own team', function () {
    $captain = User::factory()->create();
    $sport = Sport::create(['name' => 'Basketball', 'slug' => 'basketball']);
    $team = Team::create([
        'name' => 'Riga Lions',
        'sport_id' => $sport->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);
    $team->members()->create(['user_id' => $captain->id, 'role' => 'captain', 'joined_at' => now()]);

    $response = $this->actingAs($captain)->delete(route('teams.leave', $team));

    $response->assertSessionHas('error');
    $this->assertDatabaseHas('team_members', ['team_id' => $team->id, 'user_id' => $captain->id, 'role' => 'captain']);
});

// --- Deleting a team ---

it('allows the captain to delete their team', function () {
    $captain = User::factory()->create();
    $sport = Sport::create(['name' => 'Basketball', 'slug' => 'basketball']);
    $team = Team::create([
        'name' => 'Riga Lions',
        'sport_id' => $sport->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);

    $response = $this->actingAs($captain)->delete(route('teams.destroy', $team));

    $response->assertRedirect(route('teams.index'));
    $this->assertDatabaseMissing('teams', ['id' => $team->id]);
});

it('prevents a non captain from deleting a team', function () {
    $captain = User::factory()->create();
    $member = User::factory()->create();
    $sport = Sport::create(['name' => 'Basketball', 'slug' => 'basketball']);
    $team = Team::create([
        'name' => 'Riga Lions',
        'sport_id' => $sport->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);

    $response = $this->actingAs($member)->delete(route('teams.destroy', $team));

    $response->assertForbidden();
    $this->assertDatabaseHas('teams', ['id' => $team->id]);
});

// --- Invitations ---

it('allows the captain to invite a user by username', function () {
    $captain = User::factory()->create();
    $invitee = User::factory()->create(['username' => 'jdoe']);
    $sport = Sport::create(['name' => 'Basketball', 'slug' => 'basketball']);
    $team = Team::create([
        'name' => 'Riga Lions',
        'sport_id' => $sport->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);

    $response = $this->actingAs($captain)->post(route('teams.invite', $team), ['username' => 'jdoe']);

    $response->assertSessionHas('success');
    $this->assertDatabaseHas('team_invitations', [
        'team_id' => $team->id,
        'invited_user_id' => $invitee->id,
        'invited_by' => $captain->id,
        'status' => 'pending',
    ]);
});

it('prevents a non captain from sending invitations', function () {
    $captain = User::factory()->create();
    $member = User::factory()->create();
    $invitee = User::factory()->create(['username' => 'jdoe']);
    $sport = Sport::create(['name' => 'Basketball', 'slug' => 'basketball']);
    $team = Team::create([
        'name' => 'Riga Lions',
        'sport_id' => $sport->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);

    $response = $this->actingAs($member)->post(route('teams.invite', $team), ['username' => 'jdoe']);

    $response->assertForbidden();
    $this->assertDatabaseCount('team_invitations', 0);
});

it('prevents duplicate pending invitations to the same user', function () {
    $captain = User::factory()->create();
    $invitee = User::factory()->create(['username' => 'jdoe']);
    $sport = Sport::create(['name' => 'Basketball', 'slug' => 'basketball']);
    $team = Team::create([
        'name' => 'Riga Lions',
        'sport_id' => $sport->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);
    TeamInvitation::create([
        'team_id' => $team->id,
        'invited_user_id' => $invitee->id,
        'invited_by' => $captain->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($captain)->post(route('teams.invite', $team), ['username' => 'jdoe']);

    $response->assertSessionHas('error');
    $this->assertDatabaseCount('team_invitations', 1);
});

it('allows an invited user to accept an invitation and join the team', function () {
    $captain = User::factory()->create();
    $invitee = User::factory()->create(['username' => 'jdoe']);
    $sport = Sport::create(['name' => 'Basketball', 'slug' => 'basketball']);
    $team = Team::create([
        'name' => 'Riga Lions',
        'sport_id' => $sport->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);
    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'invited_user_id' => $invitee->id,
        'invited_by' => $captain->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($invitee)->post(route('teams.invitations.accept', $invitation));

    $response->assertSessionHas('success');
    $this->assertDatabaseHas('team_invitations', ['id' => $invitation->id, 'status' => 'accepted']);
    $this->assertDatabaseHas('team_members', ['team_id' => $team->id, 'user_id' => $invitee->id]);
});

it('allows an invited user to decline an invitation', function () {
    $captain = User::factory()->create();
    $invitee = User::factory()->create(['username' => 'jdoe']);
    $sport = Sport::create(['name' => 'Basketball', 'slug' => 'basketball']);
    $team = Team::create([
        'name' => 'Riga Lions',
        'sport_id' => $sport->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);
    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'invited_user_id' => $invitee->id,
        'invited_by' => $captain->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($invitee)->delete(route('teams.invitations.decline', $invitation));

    $response->assertSessionHas('success');
    $this->assertDatabaseHas('team_invitations', ['id' => $invitation->id, 'status' => 'declined']);
    $this->assertDatabaseMissing('team_members', ['team_id' => $team->id, 'user_id' => $invitee->id]);
});

it('prevents a user from responding to someone elses invitation', function () {
    $captain = User::factory()->create();
    $invitee = User::factory()->create(['username' => 'jdoe']);
    $stranger = User::factory()->create();
    $sport = Sport::create(['name' => 'Basketball', 'slug' => 'basketball']);
    $team = Team::create([
        'name' => 'Riga Lions',
        'sport_id' => $sport->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);
    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'invited_user_id' => $invitee->id,
        'invited_by' => $captain->id,
        'status' => 'pending',
    ]);

    $this->actingAs($stranger)->post(route('teams.invitations.accept', $invitation))->assertForbidden();
    $this->assertDatabaseHas('team_invitations', ['id' => $invitation->id, 'status' => 'pending']);
});

// --- Access control ---

it('redirects guests away from team management routes', function () {
    $this->post(route('teams.store'), [])->assertRedirect(route('login'));
    $this->get(route('teams.index'))->assertRedirect(route('login'));
});
