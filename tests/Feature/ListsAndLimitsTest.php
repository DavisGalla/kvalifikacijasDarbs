<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\Team;
use App\Models\Sport;
use App\Models\User;
use App\Models\WorkoutLog;

// --- Atomic workout sessions ---

it('saves nothing from a workout session when one exercise fails to save', function () {
    $user = User::factory()->create();

    WorkoutLog::creating(function (WorkoutLog $log) {
        if ($log->exercise === 'Tricep dips') {
            throw new RuntimeException('Simulated failure while saving.');
        }
    });

    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($user)->post(route('workout-logs.session'), [
        'day_name' => 'Push Day',
        'exercises' => [
            ['name' => 'Bench press', 'set_weights' => [80, 85]],
            ['name' => 'Overhead press', 'set_weights' => [50]],
            ['name' => 'Tricep dips', 'set_weights' => [20]],
        ],
    ]))->toThrow(RuntimeException::class);

    expect(WorkoutLog::count())->toBe(0);
});

// --- Pagination ---

it('paginates the blog', function () {
    $author = User::factory()->create();
    foreach (range(1, 16) as $i) {
        Post::create(['user_id' => $author->id, 'title' => "Post number {$i}", 'content' => 'Text']);
    }

    $firstPage = $this->actingAs($author)->get(route('blog.index'))->assertOk();
    $secondPage = $this->actingAs($author)->get(route('blog.index', ['page' => 2]))->assertOk();

    expect($firstPage->viewData('posts')->count())->toBe(15)
        ->and($firstPage->viewData('posts')->total())->toBe(16)
        ->and($secondPage->viewData('posts')->count())->toBe(1);
});

it('paginates comments and shows their total', function () {
    $author = User::factory()->create();
    $post = Post::create(['user_id' => $author->id, 'title' => 'Training update', 'content' => 'Text']);
    foreach (range(1, 21) as $i) {
        Comment::create(['user_id' => $author->id, 'post_id' => $post->id, 'content' => "Comment number {$i}."]);
    }

    $this->actingAs($author)->get(route('blog.show', $post))
        ->assertSee('(21)')
        ->assertSee('Comment number 20.')
        ->assertDontSee('Comment number 21.');

    $this->actingAs($author)->get(route('blog.show', [$post, 'page' => 2]))
        ->assertSee('Comment number 21.');
});

it('pages workout history by day and still offers exercises from older pages', function () {
    $user = User::factory()->create();
    foreach (range(0, 15) as $daysAgo) {
        $user->workoutLogs()->create([
            'exercise' => $daysAgo === 15 ? 'Romanian deadlift' : 'Squat',
            'set_weights' => [100],
            'performed_on' => now()->subDays($daysAgo)->toDateString(),
        ]);
    }

    $firstPage = $this->actingAs($user)->get(route('pbs.index'))->assertOk();
    expect($firstPage->viewData('logs'))->toHaveCount(14)
        ->and($firstPage->viewData('exercises')->all())->toContain('Romanian deadlift');

    $secondPage = $this->actingAs($user)->get(route('pbs.index', ['history_page' => 2]))->assertOk();
    expect($secondPage->viewData('logs'))->toHaveCount(2);
});

it('lists only teams the user may see, paginated', function () {
    $sport = Sport::create(['name' => 'Football', 'slug' => 'football']);
    $viewer = User::factory()->create();
    $owner = User::factory()->create();
    Team::create(['name' => 'Hidden Squad', 'sport_id' => $sport->id, 'captain_id' => $owner->id, 'is_public' => false]);
    foreach (range(1, 13) as $i) {
        Team::create(['name' => "Open Team {$i}", 'sport_id' => $sport->id, 'captain_id' => $owner->id, 'is_public' => true]);
    }

    $response = $this->actingAs($viewer)->get(route('teams.index'))->assertDontSee('Hidden Squad');

    expect($response->viewData('teams')->total())->toBe(13)
        ->and($response->viewData('teams')->count())->toBe(12);
});

// --- Rate limits ---

it('rate limits comments', function () {
    $user = User::factory()->create();
    $post = Post::create(['user_id' => $user->id, 'title' => 'Training update', 'content' => 'Text']);

    foreach (range(1, 6) as $i) {
        $this->actingAs($user)->post(route('comments.store'), ['post_id' => $post->id, 'content' => "Comment {$i}"])->assertRedirect();
    }

    $this->actingAs($user)->post(route('comments.store'), ['post_id' => $post->id, 'content' => 'One too many'])->assertStatus(429);

    expect(Comment::count())->toBe(6);
});

it('rate limits blog posts', function () {
    $user = User::factory()->create();

    foreach (range(1, 3) as $i) {
        $this->actingAs($user)->post(route('blog.store'), ['title' => "Post {$i}", 'content' => 'Text'])->assertRedirect();
    }

    $this->actingAs($user)->post(route('blog.store'), ['title' => 'Spam', 'content' => 'Text'])->assertStatus(429);

    expect(Post::count())->toBe(3);
});
