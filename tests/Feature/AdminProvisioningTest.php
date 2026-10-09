<?php

use App\Models\Competition;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;

it('creates the first admin account for ADMIN_EMAIL', function () {
    config(['app.admin_email' => 'owner@example.com']);

    $this->seed(AdminUserSeeder::class);

    expect(User::where('email', 'owner@example.com')->value('is_admin'))->toBeTrue();
});

it('never promotes an existing account to admin', function () {
    config(['app.admin_email' => 'someone@example.com']);
    $user = User::factory()->create(['email' => 'someone@example.com', 'is_admin' => false]);

    $this->seed(AdminUserSeeder::class);

    expect($user->fresh()->is_admin)->toBeFalse();
});

it('does not create another admin when one already exists', function () {
    User::factory()->create(['is_admin' => true]);
    config(['app.admin_email' => 'second@example.com']);

    $this->seed(AdminUserSeeder::class);

    expect(User::where('email', 'second@example.com')->exists())->toBeFalse();
});

it('creates no admin when ADMIN_EMAIL is empty', function () {
    config(['app.admin_email' => null]);

    $this->seed(AdminUserSeeder::class);

    expect(User::where('is_admin', true)->exists())->toBeFalse();
});

it('does not seed demo data outside local and testing environments', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['app.admin_email' => 'owner@example.com']);

    // Called directly: `db:seed` would stop to confirm running in production.
    (new DatabaseSeeder)->setContainer(app())->__invoke();

    expect(User::pluck('email')->all())->toBe(['owner@example.com'])
        ->and(Competition::count())->toBe(0);
});

it('grants admin access only through the explicit command', function () {
    $user = User::factory()->create(['email' => 'promote@example.com']);

    $this->artisan('user:set-admin', ['email' => 'promote@example.com'])->assertSuccessful();

    expect($user->fresh()->is_admin)->toBeTrue();
});
