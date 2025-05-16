<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use App\Filament\Pages\Profile; // Assuming this is the correct namespace for your Profile page
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;

// Test Case 5.1: Access Profile Page
test('profile page is displayed for authenticated users', function () {
    $user = User::factory()->create();
    actingAs($user);

    // The route for Filament pages can sometimes be a bit tricky to guess without seeing its registration.
    // Common patterns are /admin/profile or directly using the Livewire component rendering.
    // We'll use Livewire::test for more direct component testing.
    Livewire::test(Profile::class)
        ->assertStatus(200)
        ->assertFormSet([
            'name' => $user->name,
            'email' => $user->email,
        ]);
});

test('profile page cannot be accessed by guest', function () {
    // Ensure the user is a guest (not authenticated)
    assertGuest();

    // Attempt to access the profile page URL and assert redirection to login.
    // The Profile::getUrl() method should provide the correct URL for the page.
    get(Profile::getUrl())
        ->assertRedirect(route('filament.admin.auth.login')); // Assuming 'admin' is your panel ID
});


// Test Case 5.2: View Profile Information (covered by the 'profile page is displayed' test already)
// It checks for user's name and email.


// Test Case 5.3: Change Name
test('user can update their name', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
    ]);
    actingAs($user);

    Livewire::test(Profile::class)
        ->set('data.name', 'New Name')
        ->call('save')
        ->assertHasNoErrors()
        ->assertNotified(); // Check for success notification

    $user->refresh();
    expect($user->name)->toBe('New Name');
});

test('name is required for profile update', function () {
    $user = User::factory()->create();
    actingAs($user);

    Livewire::test(Profile::class)
        ->set('data.name', '')
        ->call('save')
        ->assertHasErrors(['data.name' => 'required']);
});

test('name cannot exceed 255 characters for profile update', function () {
    $user = User::factory()->create();
    actingAs($user);

    Livewire::test(Profile::class)
        ->set('data.name', str_repeat('a', 256))
        ->call('save')
        ->assertHasErrors(['data.name' => 'max']);
});


// Test Case 5.4: Change Password
test('user can change their password with correct current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);
    actingAs($user);

    Livewire::test(Profile::class)
        ->set('data.current_password', 'old-password')
        ->set('data.new_password', 'P@ssword123!')
        ->set('data.new_password_confirmation', 'P@ssword123!')
        ->call('save')
        ->assertHasNoErrors()
        ->assertNotified();

    $user->refresh();
    expect(Hash::check('P@ssword123!', $user->password))->toBeTrue();
});

test('user cannot change password with incorrect current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);
    actingAs($user);

    Livewire::test(Profile::class)
        ->set('data.current_password', 'wrong-current-password')
        ->set('data.new_password', 'P@ssword123!')
        ->set('data.new_password_confirmation', 'P@ssword123!')
        ->call('save')
        ->assertHasErrors(['data.current_password']); // Filament's currentPassword rule
});

test('new password is required if current password is provided and new_password is empty', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password') // Ensure current_password will pass its own validation if new_password was present
    ]);
    actingAs($user);

    Livewire::test(Profile::class)
        ->set('data.current_password', 'old-password') // User intends to change password & provides correct current one
        ->set('data.new_password', '') // But leaves new password empty
        ->set('data.new_password_confirmation', '') // And confirmation empty
        ->call('save')
        ->assertHasErrors(['data.new_password']); // Assert ANY error on data.new_password
});

test('new password must be confirmed', function () {
    $user = User::factory()->create();
    actingAs($user);

    Livewire::test(Profile::class)
        ->set('data.current_password', 'old-password')
        ->set('data.new_password', 'P@ssword123!')
        ->set('data.new_password_confirmation', 'WRONG-confirmation')
        ->call('save')
        ->assertHasErrors(['data.new_password' => 'confirmed']);
});

test('new password must meet strength requirements', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);
    actingAs($user);

    Livewire::test(Profile::class)
        ->set('data.current_password', 'old-password')
        ->set('data.new_password', 'short') // Too short, doesn't meet Password::defaults()
        ->set('data.new_password_confirmation', 'short')
        ->call('save')
        ->assertHasErrors(['data.new_password']); // Error for not meeting strength rules
});


// Helper to get the profile page URL if it's registered
// function getProfilePageUrl(): string
// {
//     // This is a placeholder. We need to ensure the page is routable
//     // and how Filament names its routes for custom pages.
//     // It might be something like route('filament.admin.pages.profile')
//     // Or, if your Profile page class has a getRouteName static method:
//     // return route(Profile::getRouteName());
//     return '/admin/profile'; // Placeholder
// }
