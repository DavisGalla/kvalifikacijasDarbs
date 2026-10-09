<?php

use App\Filament\Resources\Results\Pages\CreateResult;
use App\Models\Competition;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Sport;
use App\Models\User;
use App\Support\InvalidResultException;
use Livewire\Livewire;

/**
 * A competition in the given sport with one confirmed participant; returns [competition, participant].
 */
function scoredCompetition(array $sport): array
{
    $organizer = User::factory()->create();
    $participant = User::factory()->create(['name' => 'Anna Berzina']);

    $competition = Competition::create([
        'organizer_id' => $organizer->id,
        'sport_id' => Sport::create(array_merge(['name' => 'Sport', 'slug' => 'sport'], $sport))->id,
        'title' => 'City Cup',
        'description' => 'A competition.',
        'location' => 'Arena',
        'start_time' => now()->subHours(3),
        'end_time' => now()->subHour(),
        'registration_deadline' => now()->subDay(),
        'registration_mode' => 'individual',
        'status' => 'published',
    ]);

    Registration::create([
        'competition_id' => $competition->id,
        'registrant_type' => 'user',
        'registrant_id' => $participant->id,
        'status' => 'confirmed',
        'registered_at' => now()->subDays(2),
    ]);

    return [$competition, $participant];
}

function submitResult(Competition $competition, User $participant, string $value)
{
    return test()->actingAs($competition->organizer)
        ->from(route('competitions.results.index', $competition))
        ->post(route('competitions.results.store', $competition), [
            'registrant_type' => 'user',
            'registrant_id' => $participant->id,
            'value' => $value,
        ]);
}

// --- Organizer results page ---

it('stores a time entered as m:ss.cc in seconds', function () {
    [$competition, $participant] = scoredCompetition(['result_type' => 'time']);

    submitResult($competition, $participant, '1:03.48')->assertSessionHas('success');

    expect(Result::sole()->value)->toBe('63.480');
    expect(Result::sole()->formattedValue())->toBe('1:03.48');
});

it('rejects a negative time', function () {
    [$competition, $participant] = scoredCompetition(['result_type' => 'time']);

    submitResult($competition, $participant, '-12.4')->assertSessionHasErrors('value');

    $this->assertDatabaseCount('results', 0);
});

it('rejects a time more precise than the sport allows', function () {
    [$competition, $participant] = scoredCompetition(['result_type' => 'time', 'result_decimals' => 1]);

    submitResult($competition, $participant, '12.43')
        ->assertSessionHasErrors(['value' => 'The result may have at most 1 decimals.']);
});

it('rejects a fractional score for a sport scored in whole points', function () {
    [$competition, $participant] = scoredCompetition(['result_type' => 'score']);

    submitResult($competition, $participant, '87.5')
        ->assertSessionHasErrors(['value' => 'The score must be a whole number.']);
});

it('rejects a negative score', function () {
    [$competition, $participant] = scoredCompetition(['result_type' => 'score']);

    submitResult($competition, $participant, '-5')->assertSessionHasErrors('value');

    $this->assertDatabaseCount('results', 0);
});

it('rejects a score above the sport maximum', function () {
    [$competition, $participant] = scoredCompetition(['result_type' => 'score', 'result_decimals' => 2, 'result_max' => 10]);

    submitResult($competition, $participant, '10.25')
        ->assertSessionHasErrors(['value' => 'The result may be at most 10 pts.']);

    submitResult($competition, $participant, '9.85')->assertSessionHas('success');
    expect(Result::sole()->value)->toBe('9.850');
});

it('shows the sport result format and the error next to the submitted participant', function () {
    [$competition, $participant] = scoredCompetition(['result_type' => 'time']);

    $this->followingRedirects();
    submitResult($competition, $participant, '1:75')
        ->assertOk()
        ->assertSee('Time as seconds, m:ss or h:mm:ss (up to 2 decimals). Fastest wins.')
        ->assertSee('Seconds must be two digits between 00 and 59', false)
        ->assertSee('value="1:75"', false);
});

// --- Model guard ---

it('refuses to save an invalid value through the model, whatever the write path', function () {
    [$competition, $participant] = scoredCompetition(['result_type' => 'score']);

    expect(fn () => Result::create([
        'competition_id' => $competition->id,
        'registrant_type' => 'user',
        'registrant_id' => $participant->id,
        'value' => -3,
    ]))->toThrow(InvalidResultException::class);

    $this->assertDatabaseCount('results', 0);
});

// --- Admin panel ---

it('validates admin results against the sport format and confirmed participants', function () {
    [$competition, $participant] = scoredCompetition(['result_type' => 'time']);
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Livewire::test(CreateResult::class)
        ->fillForm([
            'competition_id' => $competition->id,
            'registrant_type' => 'user',
            'registrant_id' => User::factory()->create()->id,
            'value' => '-1',
        ])
        ->call('create')
        ->assertHasFormErrors(['registrant_id', 'value']);

    Livewire::test(CreateResult::class)
        ->fillForm([
            'competition_id' => $competition->id,
            'registrant_type' => 'user',
            'registrant_id' => $participant->id,
            'value' => '2:05.5',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Result::sole()->value)->toBe('125.500');
});
