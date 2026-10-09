<?php

use App\Filament\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Filament\Resources\TeamMembers\Pages\EditTeamMember;
use App\Filament\Resources\Teams\Pages\EditTeam;
use App\Filament\Resources\Teams\Pages\ListTeams;
use App\Models\Competition;
use App\Models\Registration;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
});

function adminTeam(int $memberCount): Team
{
    $captain = User::factory()->create();
    $team = Team::create([
        'name' => 'Riga Lions',
        'sport_id' => Sport::firstOrCreate(['slug' => 'basketball'], ['name' => 'Basketball'])->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);
    $team->members()->create(['user_id' => $captain->id, 'role' => 'captain', 'joined_at' => now()]);

    foreach (User::factory()->count($memberCount - 1)->create() as $user) {
        $team->members()->create(['user_id' => $user->id, 'role' => 'member', 'joined_at' => now()]);
    }

    return $team;
}

function adminRegistration(Team $team, bool $upcoming, array $limits = []): void
{
    $start = $upcoming ? now()->addDays(10) : now()->subDays(10);

    $competition = Competition::create(array_merge([
        'organizer_id' => User::factory()->create()->id,
        'sport_id' => $team->sport_id,
        'title' => 'City Cup',
        'description' => 'A team tournament.',
        'location' => 'Arena',
        'start_time' => $start,
        'end_time' => $start->copy()->addHours(4),
        'registration_deadline' => $start->copy()->subDays(5),
        'registration_mode' => 'team',
        'min_team_members' => 1,
        'max_team_members' => 5,
        'status' => 'published',
    ], $limits));

    Registration::create([
        'competition_id' => $competition->id,
        'registrant_type' => 'team',
        'registrant_id' => $team->id,
        'status' => 'confirmed',
        'registered_at' => now(),
    ]);
}

it('archives a team with history when an admin deletes it', function () {
    $team = adminTeam(2);
    adminRegistration($team, upcoming: false);

    Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()])
        ->callAction(DeleteAction::class)
        ->assertHasNoActionErrors();

    expect(Team::withTrashed()->find($team->id)->trashed())->toBeTrue();
});

it('refuses an admin deleting a team registered for an upcoming competition', function () {
    $team = adminTeam(2);
    adminRegistration($team, upcoming: true);

    Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()])
        ->callAction(DeleteAction::class)
        ->assertNotified('Team not deleted');

    expect(Team::find($team->id))->not->toBeNull();
});

it('only archives or deletes the eligible teams in an admin bulk delete', function () {
    $blocked = adminTeam(2);
    adminRegistration($blocked, upcoming: true);
    $free = adminTeam(1);

    Livewire::test(ListTeams::class)
        ->callTableBulkAction(DeleteBulkAction::class, [$blocked, $free]);

    expect(Team::find($blocked->id))->not->toBeNull();
    expect(Team::withTrashed()->find($free->id))->toBeNull();
});

it('refuses an admin adding a member beyond a registered competition maximum', function () {
    $team = adminTeam(5);
    adminRegistration($team, upcoming: true, limits: ['max_team_members' => 5]);
    $newcomer = User::factory()->create();

    Livewire::test(CreateTeamMember::class)
        ->fillForm([
            'team_id' => $team->id,
            'user_id' => $newcomer->id,
            'role' => 'member',
            'joined_at' => now()->toDateTimeString(),
        ])
        ->call('create')
        ->assertNotified('Member not added');

    expect($team->members()->count())->toBe(5);
});

it('lets an admin add a member when the team has room', function () {
    $team = adminTeam(2);
    $newcomer = User::factory()->create();

    Livewire::test(CreateTeamMember::class)
        ->fillForm([
            'team_id' => $team->id,
            'user_id' => $newcomer->id,
            'role' => 'member',
            'joined_at' => now()->toDateTimeString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('team_members', ['team_id' => $team->id, 'user_id' => $newcomer->id]);
});

it('refuses an admin removing a member the team needs for an upcoming competition', function () {
    $team = adminTeam(2);
    adminRegistration($team, upcoming: true, limits: ['min_team_members' => 2]);
    $member = $team->members()->where('role', 'member')->first();

    Livewire::test(EditTeamMember::class, ['record' => $member->getRouteKey()])
        ->callAction(DeleteAction::class)
        ->assertNotified('Member not removed');

    expect($team->members()->count())->toBe(2);
});
