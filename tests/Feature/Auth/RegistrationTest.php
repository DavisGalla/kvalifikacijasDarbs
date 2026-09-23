<?php

test('registration screen redirects to login', function () {
    $response = $this->get('/register');

    $response->assertRedirect('/login');
});

test('users cannot register with a password form', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
});
