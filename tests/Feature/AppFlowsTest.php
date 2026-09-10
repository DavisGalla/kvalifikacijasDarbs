<?php

use App\Models\PersonalBest;
use App\Models\Competition;
use App\Models\Registration;
use App\Models\Sport;
use App\Models\User;

it('allows an authenticated user to create a blog post', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('blog.store'), [
        'title' => 'My training week',
        'content' => 'I improved my squat and deadlift.',
    ]);

    $response->assertRedirect(route('blog.index'));

    $this->assertDatabaseHas('posts', [
        'user_id' => $user->id,
        'title' => 'My training week',
    ]);
});

it('rejects invalid blog post input', function () {
    $user = User::factory()->create();

    $response = $this->from(route('blog.create'))
        ->actingAs($user)
        ->post(route('blog.store'), [
            'title' => '',
            'content' => '',
        ]);

    $response->assertRedirect(route('blog.create'));
    $response->assertSessionHasErrors(['title', 'content']);
});

it('creates comments only for existing posts', function () {
    $user = User::factory()->create();

    $response = $this->from(route('blog.index'))
        ->actingAs($user)
        ->post(route('comments.store'), [
            'post_id' => 999999,
            'content' => 'Nice progress!',
        ]);

    $response->assertRedirect(route('blog.index'));
    $response->assertSessionHasErrors(['post_id']);
});

it('prevents deleting another users personal best', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $pb = PersonalBest::create([
        'user_id' => $owner->id,
        'exercise' => 'Bench press',
        'weight' => 95,
    ]);

    $response = $this->actingAs($otherUser)->delete(route('pbs.destroy', $pb));

    $response->assertForbidden();
    $this->assertDatabaseHas('personal_bests', ['id' => $pb->id]);
});

it('shows only the authenticated users competition registration history', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $sport = Sport::create(['name' => 'Running', 'slug' => 'running']);
    $firstCompetition = Competition::create([
        'organizer_id' => $otherUser->id,
        'sport_id' => $sport->id,
        'title' => 'Spring 5K',
        'description' => 'A spring race.',
        'location' => 'City Park',
        'start_time' => now()->addDays(10),
        'end_time' => now()->addDays(10)->addHour(),
        'registration_deadline' => now()->addDays(5),
        'status' => 'published',
    ]);
    $secondCompetition = Competition::create([
        'organizer_id' => $otherUser->id,
        'sport_id' => $sport->id,
        'title' => 'Summer 10K',
        'description' => 'A summer race.',
        'location' => 'River Road',
        'start_time' => now()->addDays(20),
        'end_time' => now()->addDays(20)->addHour(),
        'registration_deadline' => now()->addDays(15),
        'status' => 'cancelled',
    ]);
    $otherCompetition = Competition::create([
        'organizer_id' => $otherUser->id,
        'sport_id' => $sport->id,
        'title' => 'Autumn 20K',
        'description' => 'An autumn race.',
        'location' => 'Forest Trail',
        'start_time' => now()->addDays(30),
        'end_time' => now()->addDays(30)->addHour(),
        'registration_deadline' => now()->addDays(25),
        'status' => 'published',
    ]);
    $finishedCompetition = Competition::create([
        'organizer_id' => $otherUser->id,
        'sport_id' => $sport->id,
        'title' => 'Winter Team Cup',
        'description' => 'A finished team competition.',
        'location' => 'Old Town Stadium',
        'start_time' => now()->subDays(10),
        'end_time' => now()->subDays(10)->addHours(2),
        'registration_deadline' => now()->subDays(13),
        'status' => 'published',
    ]);

    Registration::create([
        'competition_id' => $firstCompetition->id,
        'registrant_type' => 'user',
        'registrant_id' => $user->id,
        'status' => 'cancelled',
        'registered_at' => now()->subDay(),
    ]);
    Registration::create([
        'competition_id' => $secondCompetition->id,
        'registrant_type' => 'user',
        'registrant_id' => $user->id,
        'status' => 'confirmed',
        'registered_at' => now(),
    ]);
    Registration::create([
        'competition_id' => $otherCompetition->id,
        'registrant_type' => 'user',
        'registrant_id' => $otherUser->id,
        'status' => 'confirmed',
        'registered_at' => now(),
    ]);
    Registration::create([
        'competition_id' => $finishedCompetition->id,
        'registrant_type' => 'user',
        'registrant_id' => $user->id,
        'status' => 'confirmed',
        'registered_at' => now()->subDays(20),
    ]);

    $response = $this->actingAs($user)->get(route('competitions.history'));

    $response->assertOk()
        ->assertSeeInOrder(['Summer 10K', 'Spring 5K'])
        ->assertSee('Left')
        ->assertSee('Canceled')
        ->assertSee('Finished')
        ->assertDontSee('Forest Trail');
});
