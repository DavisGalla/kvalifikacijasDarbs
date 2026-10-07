<?php

use App\Models\User;

it('reports that an admin user can access the filament panel', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    expect($admin->canAccessPanel(filament()->getCurrentOrDefaultPanel()))->toBeTrue();
});

it('reports that a regular user cannot access the filament panel outside local', function () {
    $user = User::factory()->create(['is_admin' => false]);

    expect(app()->environment('local'))->toBeFalse();
    expect($user->canAccessPanel(filament()->getCurrentOrDefaultPanel()))->toBeFalse();
});

it('denies the admin panel to a regular authenticated user', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('allows an admin user into the admin panel', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get('/admin')->assertOk();
});

it('redirects guests away from the admin panel to the filament login page', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});
