# Testing

## Stack

| Tool | Role |
|------|------|
| **Pest v4** | Test runner (modern PHP testing, `it()`/`test()`/`expect()` syntax) |
| **PHPUnit 12** | Underlying test framework |
| **RefreshDatabase** | Resets database between Feature tests |

## Test Structure

| Directory | What it tests | Trait |
|-----------|---------------|-------|
| `tests/Feature/` | HTTP requests, controllers, middleware, full stack | `RefreshDatabase` (via Pest.php) |
| `tests/Unit/` | Pure logic, helpers, value objects | None |

**Feature tests first.** They cover more real behavior and catch more expensive regressions. Most tests should be Feature tests. Unit tests are for pure logic that doesn't need the framework.

## Commands

```bash
php artisan test --compact                        # all tests
php artisan test --compact --filter=testName       # specific test
php artisan test --compact tests/Feature/Auth       # specific directory
```

## Creating Tests

```bash
php artisan make:test PostCreationTest --pest              # Feature test
php artisan make:test PostCalculationTest --pest --unit     # Unit test
```

## Feature Test Pattern

```php
use App\Models\User;

test('dashboard requires authentication', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

test('user can update profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'New Name',
        'email' => $user->email,
    ]);

    $response->assertRedirect(route('profile.edit'));
    expect($user->fresh()->name)->toBe('New Name');
});
```

## Test Organization by Behavior

Describe what the user can or cannot do, not implementation details:

```php
// Good — describes behavior
test('user can publish their own draft post', function () { ... });
test('user cannot publish another users post', function () { ... });
test('admin can publish any post', function () { ... });

// Bad — describes implementation
test('publish method calls update on model', function () { ... });
```

## Model Factory Usage

Always use factories for test data. Check for existing states before manually setting attributes:

```php
// Use factory
$user = User::factory()->create();

// Use factory states
$user = User::factory()->unverified()->create();

// Override specific attributes
$user = User::factory()->create(['name' => 'Alice']);

// Relationships
$post = Post::factory()->for(User::factory())->create();
$user = User::factory()->has(Post::factory()->count(3))->create();
```

## Testing Auth Flows

```php
test('users can authenticate', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('guests are redirected to login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
```

## Testing Authorization

```php
test('user can only update their own post', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $post = Post::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->patch(route('posts.update', $post), ['title' => 'Hacked'])
        ->assertForbidden();
});
```

## Testing Inertia Responses

```php
test('profile page renders correct component', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Profile')
            ->has('mustVerifyEmail')
        );
});
```

## Testing Validation

```php
test('post title is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('posts.store'), ['title' => '', 'body' => 'Content'])
        ->assertSessionHasErrors('title');
});

test('post title cannot exceed 255 characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('posts.store'), ['title' => str_repeat('a', 256), 'body' => 'Content'])
        ->assertSessionHasErrors('title');
});
```

## Turn Bugs into Tests

Every bug fix should include a regression test that would have caught the bug:

```php
// Regression: email change should reset verification
test('changing email resets email verification', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => 'new@example.com',
    ]);

    expect($user->fresh()->email_verified_at)->toBeNull();
});
```

## Named Routes in Tests

Always use `route()` helper — never hardcode URLs:

```php
// Correct
$this->get(route('dashboard'));
$this->post(route('login.store'), [...]);

// Wrong
$this->get('/dashboard');
$this->post('/login', [...]);
```

## Rate Limiting in Tests

```php
test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login' . implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
```

## Fortify Feature Skipping

Skip tests for disabled Fortify features:

```php
test('two factor challenge works', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());
    // ...
});
```

## Pest Datasets

Use datasets for data-driven tests:

```php
dataset('invalid_emails', [
    'missing @' => ['not-an-email'],
    'missing domain' => ['user@'],
    'empty string' => [''],
]);

test('rejects invalid emails', function (string $email) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => 'Test', 'email' => $email])
        ->assertSessionHasErrors('email');
})->with('invalid_emails');
```

## Rules

- Every new feature must have Feature tests — most tests should be Feature tests
- Turn every bug into a regression test
- Use Pest `test()` syntax — never PHPUnit class syntax
- Use `User::factory()` — never manually create users in tests
- Use `route()` helper — never hardcode URLs
- Use `actingAs()` for authenticated requests
- Use `assertOk()`, `assertRedirect()`, `assertForbidden()`, `assertNotFound()` — be specific
- Use `assertInertia()` to verify rendered component and props
- Use `assertSessionHasErrors()` to test validation failures
- Test authorization — verify users cannot access other users' resources
- Check factory states before manually setting model attributes
- Use datasets for testing the same behavior with multiple inputs
- Run `php artisan test --compact` with specific filter to run minimum tests needed
- Do not mock the database — use `RefreshDatabase` with real queries
