# Database Patterns

## Schema Conventions

- Migrations in `database/migrations/`, created via `php artisan make:migration`
- Models in `app/Models/`, created via `php artisan make:model`
- Auto-incrementing `id` as primary key (default Laravel convention)
- Timestamps: `created_at`, `updated_at` via `$table->timestamps()`
- Use PHP 8 attributes for model configuration: `#[Fillable([...])]`, `#[Hidden([...])]`
- Always define `casts()` method for date, hashed, and enum columns

## Migration Pattern

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

## Model Pattern

```php
#[Fillable(['title', 'body', 'status'])]
#[Hidden(['internal_notes'])]
class Post extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
```

## N+1 Prevention

N+1 is the most common Laravel performance issue. Prevent it at multiple levels:

**1. Enable strict mode in development** (add to `AppServiceProvider::boot()`):

```php
Model::preventLazyLoading(! app()->isProduction());
```

This throws an exception when a relationship is lazy-loaded, forcing you to eager-load.

**2. Always eager-load relationships you know you need:**

```php
// Correct — 2 queries total
$posts = Post::with('user', 'comments')->where('status', 'published')->get();

// Wrong — 1 + N queries (1 for posts, N for each user)
$posts = Post::where('status', 'published')->get();
// Then in template: $post->user->name triggers a query per post
```

**3. Use `$with` on models for always-needed relationships:**

```php
class Post extends Model
{
    protected $with = ['user']; // Always eager-loaded
}
```

**4. Nested eager loading:**

```php
$posts = Post::with('comments.user')->get(); // Loads comments AND their users
```

## Query Scopes

Encapsulate common query patterns:

```php
class Post extends Model
{
    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published');
    }

    public function scopeByUser(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }
}

// Usage
$posts = Post::published()->byUser($request->user())->latest()->get();
```

## Eloquent Conventions

**Querying:**

```php
// Single record — findOrFail throws 404 automatically
$post = Post::findOrFail($id);

// Scoped query
$posts = Post::where('user_id', $request->user()->id)
    ->where('status', 'published')
    ->latest()
    ->get();

// With relationships (avoid N+1)
$posts = Post::with('user')->where('status', 'published')->get();

// Count without loading models
$count = Post::where('status', 'published')->count();
```

**Creating:**

```php
$post = $request->user()->posts()->create($request->validated());
```

**Updating:**

```php
$post->update($request->validated());
```

**Bulk operations:**

```php
// Mass update — no model events fired
Post::where('status', 'draft')->where('created_at', '<', now()->subYear())->delete();
```

## Ownership Scoping

Every mutation must be scoped to the authenticated user:

```php
// Correct — scoped to user
$post = $request->user()->posts()->findOrFail($id);
$post->update($request->validated());

// Wrong — no ownership check
$post = Post::findOrFail($id);
$post->update($request->validated());
```

For indirect ownership, use Policies (see `server-patterns.md`).

## Factories

Every model must have a factory. Use states for common variations:

```php
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
            'status' => 'draft',
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
```

## Rules

- Create migrations with `php artisan make:migration` — never write migration files manually
- Create models with `php artisan make:model` — always create factory alongside (`-f` flag)
- Always add foreign key indexes (Laravel does this automatically with `foreignId()->constrained()`)
- Use `cascadeOnDelete()` on foreign keys unless business logic requires otherwise
- Use `$table->string()` for short text, `$table->text()` for long text
- Use Eloquent relationships — never raw joins for simple associations
- Always eager-load relationships to prevent N+1 (`with()`)
- Enable `Model::preventLazyLoading()` in non-production environments
- Use query scopes for reusable query conditions
- Scope all mutations to the authenticated user
- Use `findOrFail()` — never `find()` + manual null check
- Use `$request->validated()` for mass assignment — never `$request->all()`
- Use `$with` model property for relationships that are always needed
- Prefer `select()` to limit columns when only specific fields are needed for performance
