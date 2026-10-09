<?php

use App\Filament\Resources\Results\Pages\CreateResult;
use App\Models\Competition;
use App\Models\Matchup;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use App\Services\CompetitionResults;
use Livewire\Livewire;

/**
 * A published competition with the given confirmed registrants (users or teams), finished by default.
 */
function outcomeCompetition(string $mode, array $registrants, array $overrides = []): Competition
{
    $competition = Competition::create(array_merge([
        'organizer_id' => User::factory()->create()->id,
        'sport_id' => Sport::firstOrCreate(['slug' => 'outcome-sport'], ['name' => 'Outcome Sport', 'result_type' => 'score'])->id,
        'title' => 'City Cup',
        'description' => 'A competition.',
        'location' => 'Arena',
        'start_time' => now()->subHours(5),
        'end_time' => now()->subHour(),
        'registration_deadline' => now()->subDay(),
        'registration_mode' => $mode,
        'min_team_members' => $mode === 'team' ? 1 : null,
        'max_team_members' => $mode === 'team' ? 10 : null,
        'status' => 'published',
    ], $overrides));

    foreach ($registrants as $registrant) {
        Registration::create([
            'competition_id' => $competition->id,
            'registrant_type' => $registrant->getMorphClass(),
            'registrant_id' => $registrant->id,
            'status' => 'confirmed',
            'registered_at' => now()->subDays(2),
        ]);
    }

    return $competition;
}

function outcomeTeam(string $name): Team
{
    $captain = User::factory()->create();

    return Team::create([
        'name' => $name,
        'sport_id' => Sport::firstOrCreate(['slug' => 'outcome-sport'], ['name' => 'Outcome Sport', 'result_type' => 'score'])->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);
}

function recordResult(Competition $competition, User $participant, string $value): void
{
    Result::create([
        'competition_id' => $competition->id,
        'registrant_type' => 'user',
        'registrant_id' => $participant->id,
        'value' => $value,
    ]);
}

function postMatchup(Competition $competition, Team $home, Team $away, int $homeScore, int $awayScore, int $round = 1)
{
    return test()->actingAs($competition->organizer)
        ->from(route('competitions.results.index', $competition))
        ->post(route('competitions.matchups.store', $competition), [
            'round' => $round,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
        ]);
}

function putWinner(Competition $competition, array $data)
{
    return test()->actingAs($competition->organizer)
        ->from(route('competitions.results.index', $competition))
        ->put(route('competitions.winner.update', $competition), $data);
}

// --- Lifecycle ---

it('refuses results before the competition has started', function () {
    $participant = User::factory()->create();
    $competition = outcomeCompetition('individual', [$participant], [
        'start_time' => now()->addDay(),
        'end_time' => now()->addDay()->addHours(3),
        'registration_deadline' => now()->addHour(),
    ]);

    $this->actingAs($competition->organizer)
        ->post(route('competitions.results.store', $competition), [
            'registrant_type' => 'user',
            'registrant_id' => $participant->id,
            'value' => '10',
        ])
        ->assertSessionHas('error');

    expect(Result::count())->toBe(0);
});

it('refuses results for a draft competition', function () {
    $participant = User::factory()->create();
    $competition = outcomeCompetition('individual', [$participant], ['status' => 'draft']);

    $this->actingAs($competition->organizer)
        ->post(route('competitions.results.store', $competition), [
            'registrant_type' => 'user',
            'registrant_id' => $participant->id,
            'value' => '10',
        ])
        ->assertSessionHas('error');

    expect(Result::count())->toBe(0);
});

it('refuses results in the admin panel before the competition has started', function () {
    $participant = User::factory()->create();
    $competition = outcomeCompetition('individual', [$participant], [
        'start_time' => now()->addDay(),
        'end_time' => now()->addDay()->addHours(3),
        'registration_deadline' => now()->addHour(),
    ]);
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(CreateResult::class)
        ->fillForm([
            'competition_id' => $competition->id,
            'registrant_type' => 'user',
            'registrant_id' => $participant->id,
            'value' => '10',
        ])
        ->call('create')
        ->assertHasFormErrors(['competition_id']);

    expect(Result::count())->toBe(0);
});

it('refuses to declare a winner before the competition has finished', function () {
    $participant = User::factory()->create();
    $competition = outcomeCompetition('individual', [$participant], ['end_time' => now()->addHour()]);
    recordResult($competition, $participant, '10');

    putWinner($competition, ['method' => 'automatic'])->assertSessionHas('error');

    expect($competition->fresh()->winner_id)->toBeNull();
});

it('requires a registration deadline in the future for a new competition', function () {
    $sport = Sport::create(['name' => 'Running', 'slug' => 'running']);

    $this->actingAs(User::factory()->create())
        ->post(route('competitions.store'), [
            'sport_id' => $sport->id,
            'title' => 'Late Cup',
            'description' => 'Registration already closed.',
            'location' => 'Arena',
            'start_time' => now()->addWeek()->format('Y-m-d H:i'),
            'end_time' => now()->addWeek()->addHours(2)->format('Y-m-d H:i'),
            'registration_deadline' => now()->subDay()->format('Y-m-d H:i'),
            'registration_mode' => 'individual',
            'status' => 'published',
        ])
        ->assertSessionHasErrors('registration_deadline');

    expect(Competition::count())->toBe(0);
});

// --- Winner from results ---

it('declares the results leader as an automatic winner', function () {
    [$anna, $janis] = User::factory()->count(2)->create();
    $competition = outcomeCompetition('individual', [$anna, $janis]);
    recordResult($competition, $anna, '12');
    recordResult($competition, $janis, '15');

    putWinner($competition, ['method' => 'automatic'])->assertSessionHas('success');

    $competition->refresh();
    expect($competition->winner_method)->toBe('automatic')
        ->and($competition->winner_type)->toBe('user')
        ->and($competition->winner_id)->toBe($janis->id);
});

it('keeps an automatic winner in sync when the results change', function () {
    [$anna, $janis] = User::factory()->count(2)->create();
    $competition = outcomeCompetition('individual', [$anna, $janis]);
    recordResult($competition, $anna, '12');
    recordResult($competition, $janis, '15');
    app(CompetitionResults::class)->declareAutomaticWinner($competition);

    Result::where('registrant_id', $anna->id)->first()->update(['value' => '20']);
    expect($competition->fresh()->winner_id)->toBe($anna->id);

    // A tie for first leaves the winner undecided rather than picking one arbitrarily.
    Result::where('registrant_id', $janis->id)->first()->update(['value' => '20']);
    expect($competition->fresh())->winner_id->toBeNull()->winner_method->toBe('automatic');
});

it('refuses an automatic winner when nobody has a result', function () {
    $competition = outcomeCompetition('individual', [User::factory()->create()]);

    putWinner($competition, ['method' => 'automatic'])->assertSessionHas('error');

    expect($competition->fresh()->winner_id)->toBeNull();
});

it('requires a reason for a manually chosen winner and keeps it with the winner', function () {
    [$anna, $janis] = User::factory()->count(2)->create();
    $competition = outcomeCompetition('individual', [$anna, $janis]);
    recordResult($competition, $anna, '12');
    recordResult($competition, $janis, '15');

    putWinner($competition, ['method' => 'manual', 'winner_type' => 'user', 'winner_id' => $anna->id])
        ->assertSessionHasErrors('winner_note');
    expect($competition->fresh()->winner_id)->toBeNull();

    putWinner($competition, [
        'method' => 'manual',
        'winner_type' => 'user',
        'winner_id' => $anna->id,
        'winner_note' => 'Janis was disqualified for a false start.',
    ])->assertSessionHas('success');

    $competition->refresh();
    expect($competition->winner_method)->toBe('manual')
        ->and($competition->winner_id)->toBe($anna->id);

    // Later result changes do not override the organizer's decision.
    Result::where('registrant_id', $anna->id)->first()->update(['value' => '1']);
    expect($competition->fresh()->winner_id)->toBe($anna->id);

    $this->get(route('competitions.results.index', $competition))
        ->assertSee('Chosen by the organizer: Janis was disqualified for a false start.');
});

it('refuses a manual winner who is not a confirmed participant', function () {
    $competition = outcomeCompetition('individual', [User::factory()->create()]);

    putWinner($competition, [
        'method' => 'manual',
        'winner_type' => 'user',
        'winner_id' => User::factory()->create()->id,
        'winner_note' => 'Some long enough reason.',
    ])->assertSessionHas('error');

    expect($competition->fresh()->winner_id)->toBeNull();
});

// --- Matchups ---

it('refuses to record the same teams twice in one round but allows a rematch in another round', function () {
    $lions = outcomeTeam('Lions');
    $bears = outcomeTeam('Bears');
    $competition = outcomeCompetition('team', [$lions, $bears]);

    postMatchup($competition, $lions, $bears, 2, 1)->assertSessionHas('success');
    postMatchup($competition, $lions, $bears, 2, 1)->assertSessionHas('error');
    postMatchup($competition, $bears, $lions, 0, 0)->assertSessionHas('error');
    postMatchup($competition, $bears, $lions, 3, 0, round: 2)->assertSessionHas('success');

    expect(Matchup::orderBy('round')->pluck('round')->all())->toBe([1, 2]);
});

it('refuses matchups before the competition has started', function () {
    $lions = outcomeTeam('Lions');
    $bears = outcomeTeam('Bears');
    $competition = outcomeCompetition('team', [$lions, $bears], [
        'start_time' => now()->addDay(),
        'end_time' => now()->addDay()->addHours(3),
        'registration_deadline' => now()->addHour(),
    ]);

    postMatchup($competition, $lions, $bears, 2, 1)->assertSessionHas('error');

    expect(Matchup::count())->toBe(0);
});

it('ranks teams by matchup standings and takes the automatic winner from them', function () {
    $lions = outcomeTeam('Lions');
    $bears = outcomeTeam('Bears');
    $wolves = outcomeTeam('Wolves');
    $competition = outcomeCompetition('team', [$lions, $bears, $wolves]);

    postMatchup($competition, $lions, $bears, 2, 1, round: 1);   // Lions 3
    postMatchup($competition, $bears, $wolves, 1, 1, round: 2);  // Bears 1, Wolves 1
    postMatchup($competition, $wolves, $lions, 0, 3, round: 3);  // Lions 6; goal difference Bears -1, Wolves -3

    $standings = app(CompetitionResults::class)->standings($competition);
    expect($standings->pluck('team.name')->all())->toBe(['Lions', 'Bears', 'Wolves'])
        ->and($standings->pluck('points')->all())->toBe([6, 1, 1]);

    putWinner($competition, ['method' => 'automatic'])->assertSessionHas('success');
    expect($competition->fresh()->winner_id)->toBe($lions->id);

    // Removing Lions' wins makes the table level, so the automatic winner is withdrawn.
    Matchup::where('home_team_id', $lions->id)->orWhere('away_team_id', $lions->id)->get()->each->delete();
    expect($competition->fresh()->winner_id)->toBeNull();
});
