# Forms & Validation

## Two Layers: Laravel Form Requests + Inertia Forms

Validation is server-side via Laravel Form Requests. Inertia handles form state and error display on the client.

## Server-Side: Form Requests

All validation rules live in `app/Http/Requests/`. Create with `php artisan make:request`.

**Create a dedicated Form Request for each distinct action** (e.g., `StorePostRequest`, `UpdatePostRequest`):

```php
class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Or use Policy: $this->user()->can('create', Post::class)
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'status' => ['sometimes', 'in:draft,published'],
        ];
    }
}
```

**Update requests — use `sometimes` for optional fields:**

```php
class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('post'));
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'body' => ['sometimes', 'required', 'string'],
        ];
    }
}
```

**Form Request lifecycle:** `authorize()` → `prepareForValidation()` → `rules()` → `validated()`

**Input preparation** — normalize data before validation:

```php
protected function prepareForValidation(): void
{
    $this->merge([
        'slug' => Str::slug($this->input('title')),
    ]);
}
```

**Custom error messages:**

```php
public function messages(): array
{
    return [
        'title.required' => 'Every post needs a title.',
        'email.unique' => 'This email is already taken.',
    ];
}
```

## Server-Side: Controller Pattern

```php
class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }
}
```

## Client-Side: Inertia Form Component

Use the `Form` component from `@inertiajs/svelte` with Wayfinder-generated form actions:

```svelte
<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
</script>

<Form
    {...ProfileController.update.form()}
    class="space-y-6"
    options={{ preserveScroll: true }}
>
    {#snippet children({ errors, processing, recentlySuccessful })}
        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input id="name" name="name" value={user.name} required />
            <InputError message={errors.name} />
        </div>

        <Button type="submit" disabled={processing}>
            {#if processing}<Spinner />{/if}
            Save
        </Button>

        {#if recentlySuccessful}
            <p class="text-sm text-neutral-600">Saved.</p>
        {/if}
    {/snippet}
</Form>
```

## Client-Side: Wayfinder Form Variants

Wayfinder generates `.form()` variants for controller methods. Use these instead of hardcoded URLs:

```svelte
<!-- Controller action form -->
<Form {...ProfileController.update.form()}>

<!-- Named route form -->
import { store } from '@/routes/login';
<Form {...store.form()}>
```

## Client-Side: useForm (for programmatic forms)

```svelte
<script lang="ts">
    import { useForm } from '@inertiajs/svelte';

    const form = useForm({
        name: user.name,
        email: user.email,
    });

    function submit() {
        form.patch(ProfileController.update.url());
    }
</script>
```

## Client-Side: useHttp (for non-navigating requests)

Use `useHttp` for requests that don't trigger a page navigation (e.g., toggling a like, marking as read):

```svelte
<script lang="ts">
    import { useHttp } from '@inertiajs/svelte';

    const http = useHttp();

    function toggleLike(postId: number) {
        http.post(`/posts/${postId}/like`);
    }
</script>
```

`useHttp` provides the same reactive state as `useForm` (`processing`, `errors`, `progress`), but without page visits. It also supports optimistic updates.

## Optimistic Updates

Apply changes immediately, rollback on failure:

```svelte
<script lang="ts">
    import { router } from '@inertiajs/svelte';

    function toggleLike() {
        router.post(`/posts/${post.id}/like`, {}, {
            optimistic: (props) => ({
                ...props,
                post: { ...props.post, isLiked: !props.post.isLiked },
            }),
        });
    }
</script>
```

## Error Display

Inertia passes server validation errors to the `errors` object in the `children` snippet:

```svelte
{#snippet children({ errors })}
    <InputError message={errors.name} />
{/snippet}
```

The `InputError` component renders the error message if present.

## Form State

The `children` snippet provides:

| Property | Type | Description |
|---|---|---|
| `errors` | `Record<string, string>` | Server validation errors keyed by field name |
| `processing` | `boolean` | True while form is submitting |
| `recentlySuccessful` | `boolean` | True briefly after successful submission |

## Reset on Success

Clear specific fields after successful submission:

```svelte
<Form {...store.form()} resetOnSuccess={['password']}>
```

## Rules

- Always use Form Request classes for validation — never validate inline in controllers
- Create one Form Request per action (`StorePostRequest`, `UpdatePostRequest`) — never reuse
- Use `authorize()` in Form Requests for model authorization — keeps controllers thinner
- Use `prepareForValidation()` to normalize input — never transform in controllers
- Use `sometimes` for optional fields in update requests — avoid false validation errors
- Use `$request->validated()` — never `$request->all()` or `$request->input()`
- Use Wayfinder `.form()` variants for form actions — never hardcode URLs
- Use the `InputError` component for displaying field errors
- Show a `Spinner` inside the submit button when `processing` is true
- Use `preserveScroll: true` in options when the form is not the main page content
- Use `to_route()` for redirects after form submissions — never hardcode redirect URLs
- Use `useHttp` for non-navigating requests — never `fetch()` or `axios`
- Use optimistic updates for instant UI feedback on low-risk actions
