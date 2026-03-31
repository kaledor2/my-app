<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;

function mockGoogleUser(array $overrides = []): void
{
    $socialiteUser = new SocialiteUser;
    $socialiteUser->id = $overrides['id'] ?? '123456789';
    $socialiteUser->name = $overrides['name'] ?? 'Test User';
    $socialiteUser->email = $overrides['email'] ?? 'test@example.com';
    $socialiteUser->avatar = $overrides['avatar'] ?? 'https://example.com/avatar.jpg';
    $socialiteUser->token = 'test-token';

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($provider);
}

test('google redirect redirects to google', function () {
    $response = $this->get(route('socialite.google.redirect'));

    $response->assertRedirect();
    expect($response->getTargetUrl())->toContain('accounts.google.com');
});

test('google callback creates new user and authenticates', function () {
    mockGoogleUser([
        'id' => '999',
        'name' => 'New User',
        'email' => 'new@example.com',
        'avatar' => 'https://example.com/photo.jpg',
    ]);

    $response = $this->get(route('socialite.google.callback'));

    $response->assertRedirect(config('fortify.home'));
    $this->assertAuthenticated();

    $user = User::where('email', 'new@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->google_id)->toBe('999');
    expect($user->name)->toBe('New User');
    expect($user->avatar)->toBe('https://example.com/photo.jpg');
    expect($user->password)->toBeNull();
    expect($user->email_verified_at)->not->toBeNull();
});

test('google callback authenticates existing google user', function () {
    $user = User::factory()->withGoogleAccount()->create([
        'google_id' => '555',
        'email' => 'existing@example.com',
    ]);

    mockGoogleUser([
        'id' => '555',
        'email' => 'existing@example.com',
    ]);

    $response = $this->get(route('socialite.google.callback'));

    $response->assertRedirect(config('fortify.home'));
    $this->assertAuthenticated();
    expect(auth()->id())->toBe($user->id);
});

test('google callback links google to existing email user', function () {
    $user = User::factory()->create([
        'email' => 'linked@example.com',
        'google_id' => null,
    ]);

    mockGoogleUser([
        'id' => '777',
        'email' => 'linked@example.com',
        'avatar' => 'https://example.com/linked-avatar.jpg',
    ]);

    $response = $this->get(route('socialite.google.callback'));

    $response->assertRedirect(config('fortify.home'));
    $this->assertAuthenticated();

    $user->refresh();
    expect($user->google_id)->toBe('777');
    expect($user->avatar)->toBe('https://example.com/linked-avatar.jpg');
    expect(auth()->id())->toBe($user->id);
});

test('google callback verifies email when linking unverified user', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'unverified@example.com',
        'google_id' => null,
    ]);

    expect($user->email_verified_at)->toBeNull();

    mockGoogleUser([
        'id' => '888',
        'email' => 'unverified@example.com',
    ]);

    $this->get(route('socialite.google.callback'));

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();
});

test('google callback handles cancelled authentication', function () {
    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andThrow(new InvalidStateException);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($provider);

    $response = $this->get(route('socialite.google.callback'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('authenticated users cannot access google redirect', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('socialite.google.redirect'));

    $response->assertRedirect(config('fortify.home'));
});
