<?php

use App\Models\PersonalBest;
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
