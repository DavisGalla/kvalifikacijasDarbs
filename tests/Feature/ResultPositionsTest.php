<?php

use App\Models\Competition;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Sport;
use App\Models\User;

/**
 * A finished competition in a new sport with `$count` confirmed participants.
 *
 * @return array{0: Competition, 1: array<int, User>}
 */
function rankedCompetition(string $resultType, int $count): array
{
    $competition = Competition::create([
        'organizer_id' => User::factory()->create()->id,
        'sport_id' => Sport::create(['name' => 'Sport', 'slug' => 'sport-'.uniqid(), 'result_type' => $resultType])->id,
        'title' => 'City Cup',
        'description' => 'A competition.',
        'location' => 'Arena',
        'start_time' => now()->subHours(3),
        'end_time' => now()->subHour(),
        'registration_deadline' => now()->subDay(),
        'registration_mode' => 'individual',
        'status' => 'published',
    ]);

    $participants = User::factory()->count($count)->create()->all();

    foreach ($participants as $participant) {
        Registration::create([
            'competition_id' => $competition->id,
            'registrant_type' => 'user',
            'registrant_id' => $participant->id,
            'status' => 'confirmed',
            'registered_at' => now()->subDays(2),
        ]);
    }

    return [$competition, $participants];
}

function enterResult(Competition $competition, User $participant, string $value): void
{
    test()->actingAs($competition->organizer)
        ->post(route('competitions.results.store', $competition), [
            'registrant_type' => 'user',
            'registrant_id' => $participant->id,
            'value' => $value,
        ])
        ->assertSessionHas('success');
}

/**
 * @param  array<int, User>  $participants
 * @return array<int, int|null> positions in participant order
 */
function positionsOf(Competition $competition, array $participants): array
{
    $positions = $competition->results()->pluck('position', 'registrant_id');

    return array_map(fn (User $participant) => $positions[$participant->id] ?? null, $participants);
}

// --- Automatic ranking ---

it('ranks times fastest first as soon as results are entered', function () {
    [$competition, $runners] = rankedCompetition('time', 3);

    enterResult($competition, $runners[0], '12.50');
    enterResult($competition, $runners[1], '11.90');
    enterResult($competition, $runners[2], '13.10');

    expect(positionsOf($competition, $runners))->toBe([2, 1, 3]);
});

it('ranks scores highest first', function () {
    [$competition, $players] = rankedCompetition('score', 3);

    enterResult($competition, $players[0], '12');
    enterResult($competition, $players[1], '30');
    enterResult($competition, $players[2], '7');

    expect(positionsOf($competition, $players))->toBe([2, 1, 3]);
});

it('gives tied results the same position and skips the next one', function () {
    [$competition, $players] = rankedCompetition('score', 4);

    enterResult($competition, $players[0], '30');
    enterResult($competition, $players[1], '20');
    enterResult($competition, $players[2], '20');
    enterResult($competition, $players[3], '10');

    expect(positionsOf($competition, $players))->toBe([1, 2, 2, 4]);
});

it('re-ranks when a result is corrected', function () {
    [$competition, $runners] = rankedCompetition('time', 2);
    enterResult($competition, $runners[0], '12.00');
    enterResult($competition, $runners[1], '13.00');

    enterResult($competition, $runners[1], '11.00');

    expect(positionsOf($competition, $runners))->toBe([2, 1]);
});

it('re-ranks when a result is removed', function () {
    [$competition, $runners] = rankedCompetition('time', 3);
    enterResult($competition, $runners[0], '10.00');
    enterResult($competition, $runners[1], '11.00');
    enterResult($competition, $runners[2], '12.00');

    $this->actingAs($competition->organizer)
        ->delete(route('competitions.results.destroy', [$competition, $competition->results()->where('registrant_id', $runners[0]->id)->sole()]));

    expect(positionsOf($competition, $runners))->toBe([null, 1, 2]);
});

// --- Positions are derived, never entered ---

it('ignores a position passed in when saving a result', function () {
    [$competition, $players] = rankedCompetition('score', 1);

    $result = Result::create([
        'competition_id' => $competition->id,
        'registrant_type' => 'user',
        'registrant_id' => $players[0]->id,
        'value' => 5,
        'position' => 99,
    ]);

    expect($result->fresh()->position)->toBe(1);
});

it('re-ranks every competition of a sport when its result type changes', function () {
    [$competition, $players] = rankedCompetition('score', 2);
    enterResult($competition, $players[0], '10');
    enterResult($competition, $players[1], '20');

    $competition->sport->update(['result_type' => 'time']);

    expect(positionsOf($competition, $players))->toBe([1, 2]);
});

it('re-ranks a competition moved to a sport with the opposite ranking direction', function () {
    [$competition, $players] = rankedCompetition('score', 2);
    enterResult($competition, $players[0], '10');
    enterResult($competition, $players[1], '20');

    $competition->update(['sport_id' => Sport::create(['name' => 'Sprint', 'slug' => 'sprint', 'result_type' => 'time'])->id]);

    expect(positionsOf($competition, $players))->toBe([1, 2]);
});

it('repairs missing or stale positions with the recalculation command', function () {
    [$competition, $runners] = rankedCompetition('time', 2);
    enterResult($competition, $runners[0], '12.00');
    enterResult($competition, $runners[1], '11.00');
    Result::query()->update(['position' => null]);

    $this->artisan('results:recalculate-positions')->assertSuccessful();

    expect(positionsOf($competition, $runners))->toBe([2, 1]);
});

it('lists results in ranking order even when stored positions are stale', function () {
    $alice = User::factory()->create(['name' => 'Alice Fast']);
    $bob = User::factory()->create(['name' => 'Bob Slow']);
    [$competition] = rankedCompetition('time', 0);

    foreach ([[$bob, '13.00'], [$alice, '11.00']] as [$runner, $time]) {
        Registration::create(['competition_id' => $competition->id, 'registrant_type' => 'user', 'registrant_id' => $runner->id, 'status' => 'confirmed', 'registered_at' => now()]);
        enterResult($competition, $runner, $time);
    }
    Result::query()->update(['position' => null]);

    $this->actingAs($alice)
        ->get(route('competitions.show', $competition))
        ->assertOk()
        ->assertSeeInOrder(['Alice Fast', 'Bob Slow']);
});
