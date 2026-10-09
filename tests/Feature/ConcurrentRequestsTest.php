<?php

use App\Models\Competition;
use App\Models\Sport;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Real concurrency needs separate processes sharing one database file; the in-memory
// test database is private to this process, so these tests use their own SQLite file.

beforeEach(function () {
    $this->raceDatabase = tempnam(sys_get_temp_dir(), 'race');

    config(['database.connections.race' => array_merge(config('database.connections.sqlite'), [
        'database' => $this->raceDatabase,
    ])]);

    Artisan::call('migrate', ['--database' => 'race', '--force' => true]);
});

afterEach(function () {
    DB::purge('race');
    @unlink($this->raceDatabase);
});

/**
 * Send each request as its user from a separate process, all at the same moment, and return
 * their results in order.
 *
 * Each worker pauses 300ms after every query containing `$pauseAfterSql`. A request's options
 * can override that (`pauseAfter`, '' for no pause) and start it slightly later (`delay` seconds),
 * to force a specific interleaving.
 *
 * @param  array<int, array{0: User, 1: string, 2: string, 3?: array, 4?: array{delay?: float, pauseAfter?: string}}>  $requests  [user, method, path, params, options]
 */
function sendConcurrently(string $databasePath, array $requests, string $pauseAfterSql = 'count(*)'): array
{
    $worker = base_path('tests/Support/concurrent_request_worker.php');
    $startAt = microtime(true) + 4;
    $processes = [];

    foreach ($requests as $request) {
        [$user, $method, $path] = $request;
        $options = $request[4] ?? [];
        $command = [
            PHP_BINARY, $worker, $databasePath, (string) $user->id, $method, $path,
            json_encode($request[3] ?? []),
            (string) ($startAt + ($options['delay'] ?? 0)),
            $options['pauseAfter'] ?? $pauseAfterSql,
        ];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
        $processes[] = [$process, $pipes];
    }

    return array_map(function (array $entry) {
        [$process, $pipes] = $entry;
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        expect($exitCode)->toBe(0, "Worker failed: {$errors}{$output}");

        $result = json_decode($output, true);
        expect($result)->toBeArray("Worker returned unexpected output: {$errors}{$output}");

        return $result;
    }, $processes);
}

it('does not exceed the participant limit when two users register for the last spot at once', function () {
    $organizer = User::factory()->connection('race')->create();
    $first = User::factory()->connection('race')->create();
    $second = User::factory()->connection('race')->create();
    $sport = Sport::on('race')->create(['name' => 'Running', 'slug' => 'running']);

    $competition = Competition::on('race')->create([
        'organizer_id' => $organizer->id,
        'sport_id' => $sport->id,
        'title' => 'Last Spot Race',
        'description' => 'Concurrency test competition.',
        'location' => 'City Park',
        'start_time' => now()->addDays(10),
        'end_time' => now()->addDays(10)->addHours(2),
        'registration_deadline' => now()->addDays(5),
        'registration_mode' => 'individual',
        'max_participants' => 1,
        'status' => 'published',
    ]);

    $path = "/competitions/{$competition->id}/register";
    $results = sendConcurrently($this->raceDatabase, [[$first, 'POST', $path], [$second, 'POST', $path]]);

    expect(array_column($results, 'status'))->each->toBe(302);
    expect(array_filter(array_column($results, 'success')))->toHaveCount(1);
    expect(array_column($results, 'error'))->toContain('This competition has reached its participant limit.');

    expect(DB::connection('race')->table('registrations')
        ->where('competition_id', $competition->id)
        ->whereIn('status', ['pending', 'confirmed'])
        ->count())->toBe(1);
});

it('registers both users when the competition has room for them', function () {
    $organizer = User::factory()->connection('race')->create();
    $first = User::factory()->connection('race')->create();
    $second = User::factory()->connection('race')->create();
    $sport = Sport::on('race')->create(['name' => 'Running', 'slug' => 'running']);

    $competition = Competition::on('race')->create([
        'organizer_id' => $organizer->id,
        'sport_id' => $sport->id,
        'title' => 'Roomy Race',
        'description' => 'Concurrency test competition.',
        'location' => 'City Park',
        'start_time' => now()->addDays(10),
        'end_time' => now()->addDays(10)->addHours(2),
        'registration_deadline' => now()->addDays(5),
        'registration_mode' => 'individual',
        'max_participants' => 2,
        'status' => 'published',
    ]);

    $path = "/competitions/{$competition->id}/register";
    $results = sendConcurrently($this->raceDatabase, [[$first, 'POST', $path], [$second, 'POST', $path]]);

    expect(array_filter(array_column($results, 'success')))->toHaveCount(2);
    expect(DB::connection('race')->table('registrations')
        ->where('competition_id', $competition->id)
        ->count())->toBe(2);
});

it('keeps the roster within competition limits when a member leaves while the captain registers', function () {
    $captain = User::factory()->connection('race')->create();
    $leaver = User::factory()->connection('race')->create();
    $third = User::factory()->connection('race')->create();
    $sport = Sport::on('race')->create(['name' => 'Basketball', 'slug' => 'basketball']);

    $team = Team::on('race')->create([
        'name' => 'Riga Lions',
        'sport_id' => $sport->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);
    $team->members()->create(['user_id' => $captain->id, 'role' => 'captain', 'joined_at' => now()]);
    $team->members()->create(['user_id' => $leaver->id, 'role' => 'member', 'joined_at' => now()]);
    $team->members()->create(['user_id' => $third->id, 'role' => 'member', 'joined_at' => now()]);

    $competition = Competition::on('race')->create([
        'organizer_id' => User::factory()->connection('race')->create()->id,
        'sport_id' => $sport->id,
        'title' => 'City Cup',
        'description' => 'Concurrency test competition.',
        'location' => 'Arena',
        'start_time' => now()->addDays(10),
        'end_time' => now()->addDays(10)->addHours(4),
        'registration_deadline' => now()->addDays(5),
        'registration_mode' => 'team',
        'min_team_members' => 3,
        'max_team_members' => 5,
        'status' => 'published',
    ]);

    [$registration, $leaving] = sendConcurrently($this->raceDatabase, [
        [$captain, 'POST', "/competitions/{$competition->id}/register", ['team_id' => $team->id]],
        [$leaver, 'DELETE', "/teams/{$team->id}/leave"],
    ]);

    expect($registration['status'])->toBe(302);
    expect($leaving['status'])->toBe(302);

    // Exactly one of the two requests may win, whichever order the lock serialized them in.
    expect([(bool) $registration['success'], (bool) $leaving['success']])->toContain(true, false);

    $memberCount = DB::connection('race')->table('team_members')->where('team_id', $team->id)->count();
    $registered = DB::connection('race')->table('registrations')
        ->where('competition_id', $competition->id)
        ->whereIn('status', ['pending', 'confirmed'])
        ->exists();

    if ($registered) {
        expect($memberCount)->toBe(3);
        expect($leaving['error'])->toBe('This team is registered for City Cup, which requires at least 3 members.');
    } else {
        expect($memberCount)->toBe(2);
        expect($registration['error'])->toBe('Your team needs at least 3 members to register.');
    }
});

function racePublicTeam(): Team
{
    $captain = User::factory()->connection('race')->create();
    $team = Team::on('race')->create([
        'name' => 'Riga Lions',
        'sport_id' => Sport::on('race')->create(['name' => 'Basketball', 'slug' => 'basketball'])->id,
        'captain_id' => $captain->id,
        'is_public' => true,
    ]);
    $team->members()->create(['user_id' => $captain->id, 'role' => 'captain', 'joined_at' => now()]);

    return $team;
}

function raceInvitation(Team $team, User $invitee): TeamInvitation
{
    return TeamInvitation::on('race')->create([
        'team_id' => $team->id,
        'invited_user_id' => $invitee->id,
        'invited_by' => $team->captain_id,
        'status' => 'pending',
    ]);
}

it('adds a user to the team once when they press join in two tabs at the same time', function () {
    $team = racePublicTeam();
    $joiner = User::factory()->connection('race')->create();

    $results = sendConcurrently($this->raceDatabase, [
        [$joiner, 'POST', "/teams/{$team->id}/join"],
        [$joiner, 'POST', "/teams/{$team->id}/join"],
    ]);

    expect(array_column($results, 'status'))->each->toBe(302);
    expect(array_filter(array_column($results, 'success')))->toHaveCount(1);
    expect(array_column($results, 'error'))->toContain('You are already a member of this team.');
    expect(DB::connection('race')->table('team_members')
        ->where('team_id', $team->id)
        ->where('user_id', $joiner->id)
        ->count())->toBe(1);
});

it('accepts an invitation once when it is accepted in two tabs at the same time', function () {
    $team = racePublicTeam();
    $invitee = User::factory()->connection('race')->create();
    $invitation = raceInvitation($team, $invitee);
    $path = "/team-invitations/{$invitation->id}/accept";

    $results = sendConcurrently($this->raceDatabase, [[$invitee, 'POST', $path], [$invitee, 'POST', $path]]);

    expect(array_column($results, 'status'))->each->toBe(302);
    expect(array_filter(array_column($results, 'success')))->toHaveCount(1);
    expect(array_column($results, 'error'))->toContain('This invitation is no longer pending.');
    expect(DB::connection('race')->table('team_invitations')->where('id', $invitation->id)->value('status'))->toBe('accepted');
    expect(DB::connection('race')->table('team_members')
        ->where('team_id', $team->id)
        ->where('user_id', $invitee->id)
        ->count())->toBe(1);
});

it('lets only one response win when an invitation is accepted and declined at the same time', function () {
    $team = racePublicTeam();
    $invitee = User::factory()->connection('race')->create();
    $invitation = raceInvitation($team, $invitee);

    [$accepting, $declining] = sendConcurrently($this->raceDatabase, [
        [$invitee, 'POST', "/team-invitations/{$invitation->id}/accept"],
        [$invitee, 'DELETE', "/team-invitations/{$invitation->id}"],
    ]);

    expect([(bool) $accepting['success'], (bool) $declining['success']])->toContain(true, false);

    $status = DB::connection('race')->table('team_invitations')->where('id', $invitation->id)->value('status');
    $isMember = DB::connection('race')->table('team_members')
        ->where('team_id', $team->id)
        ->where('user_id', $invitee->id)
        ->exists();

    // The invitation's final status and the membership must tell the same story.
    expect($status)->toBe($accepting['success'] ? 'accepted' : 'declined');
    expect($isMember)->toBe((bool) $accepting['success']);
});
