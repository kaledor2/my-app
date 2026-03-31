# Server Patterns

## Controller Pattern

Controllers are thin — validation in Form Requests, business logic in Action classes or model methods:

```php
class PostController extends Controller
{
    public function store(StorePostRequest $request): RedirectResponse
    {
        $request->user()->posts()->create($request->validated());

        return to_route('posts.index');
    }
}
```

**Single-action controllers** — when an endpoint is clearer as one focused action instead of another method on a bloated resource controller:

```php
class PublishPostController extends Controller
{
    public function __invoke(Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $post->update(['status' => 'published', 'published_at' => now()]);

        return to_route('posts.show', $post);
    }
}
```

## Route Model Binding

Let Laravel resolve models automatically — never fetch manually when a route parameter matches:

```php
// Correct — implicit route model binding
Route::get('posts/{post}', [PostController::class, 'show']);

public function show(Post $post): Response
{
    return Inertia::render('posts/Show', ['post' => $post]);
}

// Wrong — manual fetch when binding would work
public function show(int $id): Response
{
    $post = Post::findOrFail($id);
}
```

## Authorization

Use Policies for model-level authorization and Gates for non-model actions:

```php
// Policy — register automatically by naming convention (PostPolicy for Post)
class PostPolicy
{
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}

// In controllers — use $this->authorize() or Gate::authorize()
public function update(UpdatePostRequest $request, Post $post): RedirectResponse
{
    $this->authorize('update', $post);

    $post->update($request->validated());

    return to_route('posts.show', $post);
}

// In Form Request — use authorize() method
class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('post'));
    }
}

// Gates — for non-model actions (e.g., admin dashboard)
Gate::define('access-admin', function (User $user): bool {
    return $user->is_admin;
});
```

## Inertia Rendering

```php
// Simple page
return Inertia::render('Dashboard');

// Page with props
return Inertia::render('settings/Profile', [
    'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
    'status' => $request->session()->get('status'),
]);

// Deferred props — loaded after initial page render
return Inertia::render('posts/Index', [
    'posts' => $posts,
    'stats' => Inertia::defer(fn () => $this->computeExpensiveStats()),
]);

// Optional props — only loaded when explicitly requested
return Inertia::render('posts/Show', [
    'post' => $post,
    'comments' => Inertia::optional(fn () => $post->comments()->paginate()),
]);

// Static page (no controller needed)
Route::inertia('/', 'Welcome', ['canRegister' => Features::enabled(Features::registration())])->name('home');
```

## Redirects

```php
// Named route redirect (preferred)
return to_route('profile.edit');

// With flash data
return to_route('profile.edit')->with('status', 'Profile updated.');

// Back to previous page
return back();
```

## Middleware

Use middleware via route groups or controller `HasMiddleware` interface:

```php
// Route group
Route::middleware(['auth', 'verified'])->group(function () {
    // ...
});

// Controller middleware
class SecurityController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['password.confirm'];
    }
}

// Per-route throttling
Route::put('settings/password', [SecurityController::class, 'update'])
    ->middleware('throttle:6,1');
```

## Action Classes

For complex business logic, use Action classes in `app/Actions/`:

```php
class CreateNewUser implements CreatesNewUsers
{
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
```

## Service Provider Defaults

`AppServiceProvider` configures production defaults:

- `CarbonImmutable` for all date instances
- `DB::prohibitDestructiveCommands()` in production
- Strict password rules in production
- Enable `Model::preventLazyLoading()` in non-production to catch N+1 queries early

## Environment Variables

- Read from `.env` via `config()` or `env()` (in config files only)
- Never call `env()` outside of config files — use `config()` in application code
- Use `php artisan config:show` to inspect values

## Error Handling

| Context | Method | Effect |
|---|---|---|
| Controller action | Return `RedirectResponse` with errors | Inertia shows errors on form |
| Form Request | Automatic — throws `ValidationException` | Inertia shows field errors |
| Model not found | `findOrFail()` — throws `ModelNotFoundException` | Laravel renders 404 |
| Authorization | `$this->authorize()` or Policy | Laravel renders 403 |
| Abort | `abort(404)` / `abort(403)` | Custom error page |

## Named Routes

Always use named routes:

```php
// Defining
Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');

// Using in PHP
return to_route('profile.edit');
route('profile.edit'); // URL generation

// Using in Svelte (via Wayfinder)
import { edit } from '@/routes/profile';
```

## Rules

- Controllers are thin — validation in Form Requests, authorization in Policies, logic in Actions or Models
- Use single-action controllers (`__invoke`) when a resource controller method feels forced
- Use route model binding — never manually fetch when a route parameter matches a model
- Use Policies for model authorization, Gates for non-model actions
- Use `$this->authorize()` in controllers or `authorize()` in Form Requests
- Use `Inertia::render()` for pages — never return Blade views for SPA pages
- Use `Inertia::defer()` for expensive props, `Inertia::optional()` for on-demand props
- Use `to_route()` for redirects — never hardcode URLs
- Use named routes everywhere — `route()` in PHP, Wayfinder functions in Svelte
- Use `$request->user()` — never `Auth::user()` in request context
- Use `$request->validated()` — never `$request->all()` for mass assignment
- Use `config()` in application code — never `env()` outside config files
- Create new files via `php artisan make:*` commands — never manually
- Run `vendor/bin/pint --dirty --format agent` after modifying PHP files
