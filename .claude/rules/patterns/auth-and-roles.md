# Auth & Roles

## Auth Stack

Authentication is handled by **Laravel Fortify** (backend) + **Inertia Svelte** (frontend). Fortify provides:

- Login / logout
- Registration
- Password reset
- Email verification
- Two-factor authentication (TOTP + recovery codes)

## Session Flow

1. Request hits Laravel middleware stack
2. `auth` middleware validates session (database-backed)
3. `verified` middleware checks email verification (where required)
4. Authenticated user available via `$request->user()` or `auth()->user()`

## Middleware Groups

| Middleware | Purpose |
|---|---|
| `auth` | User must be logged in |
| `verified` | User must have verified email |
| `guest` | User must NOT be logged in (Fortify handles this) |
| `throttle:6,1` | Rate limiting (6 attempts per minute) |
| `password.confirm` | Require recent password confirmation for sensitive actions |

## Route Protection

Routes are protected via middleware groups in route files:

```php
// Public — no middleware
Route::inertia('/', 'Welcome')->name('home');

// Auth required + email verified
Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

// Auth required (email verification not needed)
Route::middleware(['auth'])->group(function () {
    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
});

// Auth + verified (sensitive operations)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
```

## Authorization — Policies and Gates

Use **Policies** for model-level authorization. Laravel auto-discovers policies by convention (`PostPolicy` for `Post`):

```php
class PostPolicy
{
    public function view(User $user, Post $post): bool
    {
        return true; // All authenticated users can view
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
```

**Use in controllers:**

```php
public function update(UpdatePostRequest $request, Post $post): RedirectResponse
{
    $this->authorize('update', $post);
    $post->update($request->validated());
    return to_route('posts.show', $post);
}
```

**Use in Form Requests (preferred — keeps controllers thinner):**

```php
class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('post'));
    }
}
```

**Gates** for non-model actions:

```php
Gate::define('access-admin', fn (User $user) => $user->is_admin);

// Usage
if (Gate::allows('access-admin')) { ... }
$this->authorize('access-admin');
```

## Fortify View Rendering

Fortify views are registered in `FortifyServiceProvider` using Inertia:

```php
Fortify::loginView(fn () => Inertia::render('auth/Login', [
    'canResetPassword' => Features::enabled(Features::resetPasswords()),
    'canRegister' => Features::enabled(Features::registration()),
    'status' => session('status'),
]));
```

## Rate Limiting

Login and two-factor attempts are rate-limited in `FortifyServiceProvider`:

```php
RateLimiter::for('login', function (Request $request) {
    $throttleKey = Str::transliterate(Str::lower($request->string('email')).'|'.$request->ip());
    return Limit::perMinute(5)->by($throttleKey);
});
```

## Two-Factor Authentication

- Enabled via `Laravel\Fortify\TwoFactorAuthenticatable` trait on User model
- Columns: `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`
- Setup modal in `TwoFactorSetupModal.svelte`
- Challenge page at `auth/TwoFactorChallenge.svelte`
- 2FA settings require `password.confirm` middleware — users must re-enter password
- Confirmation flow: show QR → user enters TOTP code → only then activate (prevents lockout)

## Password Defaults

Production enforces strict password rules via `AppServiceProvider`:

```php
Password::defaults(fn (): ?Password => app()->isProduction()
    ? Password::min(12)->mixedCase()->letters()->numbers()->symbols()->uncompromised()
    : null,
);
```

## Accessing the User in Controllers

```php
// In controller methods
$user = $request->user();
$userId = $request->user()->id;

// Always use $request->user(), not Auth::user() in controllers
```

## Accessing the User in Svelte Pages

Inertia shares auth data. Access from any page:

```svelte
<script lang="ts">
    import { page } from '@inertiajs/svelte';

    const user = $derived(page.props.auth.user);
</script>
```

## Rules

- Use Fortify for all auth flows — never build custom auth
- Always apply `auth` middleware to protected routes
- Apply `verified` middleware for sensitive operations (delete, security changes)
- Use Policies for model authorization — auto-discovered by naming convention
- Use `$this->authorize()` in controllers or `authorize()` in Form Requests
- Use Gates for non-model authorization (admin access, feature flags)
- Use `password.confirm` middleware for sensitive settings (2FA, account deletion)
- Use Form Request classes for auth-related validation
- Use `to_route()` for post-auth redirects
- Rate-limit sensitive endpoints with `throttle` middleware
- Use `$request->user()` in controllers — never `Auth::user()` in request context
- 2FA must use confirmation flow — never activate without user entering a valid TOTP code
