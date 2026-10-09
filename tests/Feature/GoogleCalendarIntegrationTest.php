<?php

use App\Exceptions\GoogleCalendarDisconnectedException;
use App\Jobs\CreateCompetitionCalendarEvent;
use App\Jobs\DeleteCompetitionCalendarEvent;
use App\Models\Competition;
use App\Models\Registration;
use App\Models\Sport;
use App\Models\User;
use App\Services\GoogleCalendarConnection;
use App\Services\GoogleCalendarService;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function calendarUser(): User
{
    return User::factory()->create([
        'google_access_token' => 'access-token',
        'google_refresh_token' => 'refresh-token',
        'google_token_expires_at' => now()->addHour(),
    ]);
}

function calendarRegistration(User $user, string $status = 'pending'): Registration
{
    $competition = Competition::create([
        'organizer_id' => User::factory()->create()->id,
        'sport_id' => Sport::firstOrCreate(['slug' => 'running'], ['name' => 'Running'])->id,
        'title' => 'Riga Run',
        'description' => 'A race.',
        'location' => 'Riga',
        'start_time' => now()->addWeek(),
        'end_time' => now()->addWeek()->addHours(3),
        'registration_deadline' => now()->addDays(5),
        'registration_mode' => 'individual',
        'status' => 'published',
    ]);

    return Registration::create([
        'competition_id' => $competition->id,
        'registrant_type' => 'user',
        'registrant_id' => $user->id,
        'status' => $status,
        'registered_at' => now(),
    ]);
}

/**
 * Replace the Google Calendar API with a mock for everything resolved through the container.
 */
function fakeCalendar(): Mockery\MockInterface
{
    $calendar = Mockery::mock(GoogleCalendarService::class);
    app()->bind(GoogleCalendarService::class, fn () => $calendar);

    return $calendar;
}

// --- Token storage ---

it('stores Google tokens encrypted', function () {
    $user = calendarUser();

    $raw = DB::table('users')->where('id', $user->id)->first();

    expect($raw->google_access_token)->not->toBe('access-token')
        ->and(Crypt::decryptString($raw->google_refresh_token))->toBe('refresh-token')
        ->and($user->fresh()->google_access_token)->toBe('access-token')
        ->and($user->toArray())->not->toHaveKey('google_access_token');
});

it('encrypts tokens that were stored as plain text', function () {
    $user = User::factory()->create();
    DB::table('users')->where('id', $user->id)->update(['google_access_token' => 'plain', 'google_refresh_token' => 'plain-refresh']);

    $migration = require database_path('migrations/2026_10_09_000002_encrypt_google_tokens.php');
    $migration->up();
    $migration->up(); // already encrypted values are left alone

    expect($user->fresh())
        ->google_access_token->toBe('plain')
        ->google_refresh_token->toBe('plain-refresh');
});

// --- Creating events ---

it('creates the event under an id derived from the registration and is safe to run twice', function () {
    $user = calendarUser();
    $registration = calendarRegistration($user);
    $eventId = GoogleCalendarService::registrationEventId($registration);

    $calendar = fakeCalendar();
    $calendar->shouldReceive('upsertEvent')->twice()->withArgs(fn ($id) => $id === $eventId);

    (new CreateCompetitionCalendarEvent($registration->id, $user->id))->handle();
    (new CreateCompetitionCalendarEvent($registration->id, $user->id))->handle();

    expect($registration->fresh()->google_event_id)->toBe($eventId)
        ->and($eventId)->toMatch('/^[0-9a-v]{5,1024}$/');
});

it('does not create an event for a registration cancelled before the job ran', function () {
    $user = calendarUser();
    $registration = calendarRegistration($user, 'cancelled');

    fakeCalendar()->shouldNotReceive('upsertEvent');

    (new CreateCompetitionCalendarEvent($registration->id, $user->id))->handle();

    expect($registration->fresh()->google_event_id)->toBeNull();
});

it('removes the event again when the registration is cancelled while it is being created', function () {
    $user = calendarUser();
    $registration = calendarRegistration($user);
    $eventId = GoogleCalendarService::registrationEventId($registration);

    $calendar = fakeCalendar();
    $calendar->shouldReceive('upsertEvent')->once()->andReturnUsing(function () use ($registration) {
        $registration->update(['status' => 'cancelled']);
    });
    $calendar->shouldReceive('deleteEvent')->once()->with($eventId);

    (new CreateCompetitionCalendarEvent($registration->id, $user->id))->handle();

    expect($registration->fresh()->google_event_id)->toBeNull();
});

it('lets the queue retry temporary Google failures', function () {
    $user = calendarUser();
    $registration = calendarRegistration($user);
    fakeCalendar()->shouldReceive('upsertEvent')->andThrow(new GoogleServiceException('Backend error', 503));

    $job = new CreateCompetitionCalendarEvent($registration->id, $user->id);

    expect(fn () => $job->handle())->toThrow(GoogleServiceException::class)
        ->and($job->tries)->toBeGreaterThan(1);
});

it('asks the user to reconnect instead of retrying when Google rejects their access', function () {
    $user = calendarUser();
    $registration = calendarRegistration($user);
    app()->bind(GoogleCalendarService::class, fn () => throw new GoogleCalendarDisconnectedException('invalid_grant'));

    (new CreateCompetitionCalendarEvent($registration->id, $user->id))->handle();

    expect($user->fresh())
        ->google_access_token->toBeNull()
        ->google_refresh_token->toBeNull();

    $this->actingAs($user->fresh())->get(route('calendar.index'))->assertRedirect(route('calendar.connect'));
});

// --- Cancelling ---

it('removes the calendar event on cancellation even before its id was saved', function () {
    Queue::fake();
    $user = calendarUser();
    $registration = calendarRegistration($user);

    $this->actingAs($user)
        ->delete(route('competitions.registration.cancel', $registration->competition))
        ->assertSessionHas('success');

    Queue::assertPushed(DeleteCompetitionCalendarEvent::class, fn ($job) => $job->eventId === GoogleCalendarService::registrationEventId($registration)
        && $job->registrationId === $registration->id);
});

it('treats an event that is already gone as deleted', function () {
    $user = calendarUser();
    $registration = calendarRegistration($user, 'cancelled');
    fakeCalendar()->shouldReceive('deleteEvent')->once()->andThrow(new GoogleServiceException('Gone', 410));

    (new DeleteCompetitionCalendarEvent($user->id, 'eventid1', $registration->id))->handle();

    expect($user->fresh()->google_access_token)->toBe('access-token');
});

it('keeps the event when the user registered again before the delete job ran', function () {
    $user = calendarUser();
    $registration = calendarRegistration($user, 'pending');
    fakeCalendar()->shouldNotReceive('deleteEvent');

    (new DeleteCompetitionCalendarEvent($user->id, 'eventid1', $registration->id))->handle();
});

// --- Connecting and disconnecting ---

it('explains the calendar connection before sending the user to Google', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('calendar.index'))->assertRedirect(route('calendar.connect'));
    $this->actingAs($user)->get(route('calendar.connect'))
        ->assertOk()
        ->assertSee('Permission to see and edit events in your Google Calendar')
        ->assertSee(route('google.calendar.redirect'));
});

it('returns to the connect page when the user cancels on Google', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['google_calendar_connect' => true])
        ->get('/auth/google/callback?error=access_denied')
        ->assertRedirect(route('calendar.connect'))
        ->assertSessionHas('error');

    expect($user->fresh()->google_access_token)->toBeNull();
});

it('does not store tokens when calendar access was not granted', function () {
    $user = User::factory()->create(['email' => 'anna@example.com']);

    $googleUser = (new SocialiteUser)->map(['id' => 'g-1', 'name' => 'Anna', 'email' => 'anna@example.com', 'avatar' => null])
        ->setToken('new-access')
        ->setRefreshToken('new-refresh')
        ->setExpiresIn(3600)
        ->setApprovedScopes(['openid', 'email', 'profile']);
    Socialite::shouldReceive('driver->user')->andReturn($googleUser);

    $this->withSession(['google_calendar_connect' => true])
        ->get('/auth/google/callback?code=abc&state=xyz')
        ->assertRedirect(route('calendar.connect'));

    expect($user->fresh()->google_access_token)->toBeNull();
});

it('stores tokens and adds upcoming registrations to the calendar after connecting', function () {
    Queue::fake();
    $user = User::factory()->create(['email' => 'anna@example.com']);
    $registration = calendarRegistration($user);

    $googleUser = (new SocialiteUser)->map(['id' => 'g-1', 'name' => 'Anna', 'email' => 'anna@example.com', 'avatar' => null])
        ->setToken('new-access')
        ->setRefreshToken('new-refresh')
        ->setExpiresIn(3600)
        ->setApprovedScopes(['openid', 'https://www.googleapis.com/auth/calendar']);
    Socialite::shouldReceive('driver->user')->andReturn($googleUser);

    $this->withSession(['google_calendar_connect' => true])
        ->get('/auth/google/callback?code=abc&state=xyz')
        ->assertRedirect(route('calendar.index'));

    expect($user->fresh()->google_access_token)->toBe('new-access');
    Queue::assertPushed(CreateCompetitionCalendarEvent::class, fn ($job) => $job->registrationId === $registration->id);
});

it('revokes access at Google when the user disconnects', function () {
    $user = calendarUser();
    $connection = Mockery::mock(GoogleCalendarConnection::class)->makePartial();
    $connection->shouldReceive('revoke')->once()->with('refresh-token')->andReturn(true);
    app()->instance(GoogleCalendarConnection::class, $connection);

    $this->actingAs($user)
        ->delete(route('calendar.disconnect'))
        ->assertRedirect(route('calendar.connect'));

    expect($user->fresh())
        ->google_access_token->toBeNull()
        ->google_refresh_token->toBeNull();
});
