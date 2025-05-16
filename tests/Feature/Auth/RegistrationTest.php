<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));
    $response->assertStatus(200);
});

test('new users can register and are authenticated', function () {
    $this->assertGuest();

    $response = $this->post(route('register'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'P@ssword123!',
        'password_confirmation' => 'P@ssword123!',
    ]);

    $response->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
    $user = User::where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Test User')
        ->and(Hash::check('P@ssword123!', $user->password))->toBeTrue();

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);
});

test('registration email must be a valid email address', function () {
    $response = $this->post(route('register'), [
        'name' => 'Test User',
        'email' => 'not-an-email', // Invalid email
        'password' => 'P@ssword123!',
        'password_confirmation' => 'P@ssword123!',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('registration email must be unique', function () {
    // Create a user first
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->post(route('register'), [
        'name' => 'Another User',
        'email' => 'test@example.com', // Duplicate email
        'password' => 'P@ssword123!',
        'password_confirmation' => 'P@ssword123!',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('registration password must be at least 8 characters', function () {
    $response = $this->post(route('register'), [
        'name' => 'Test User',
        'email' => 'newuser@example.com',
        'password' => 'Pass1!', // Too short
        'password_confirmation' => 'Pass1!',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

test('registration password must be confirmed', function () {
    $response = $this->post(route('register'), [
        'name' => 'Test User',
        'email' => 'newuser-confirm@example.com', // Unique email for this test
        'password' => 'P@ssword123!',
        'password_confirmation' => 'WrongP@ssword123!', // Mismatch
    ]);
    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});
