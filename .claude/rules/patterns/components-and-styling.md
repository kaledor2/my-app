# Components & Styling

## Svelte 5 Runes

All reactivity uses runes. Never use legacy stores or reactive declarations.

```svelte
<script lang="ts">
    // Props via $props() destructuring with types
    let { data, optionalProp = 'default' }: { data: SomeType; optionalProp?: string } = $props();

    // Reactive state
    let showForm = $state(false);

    // Derived values (recompute when dependencies change) — use for PURE computations
    const total = $derived(items.reduce((sum, i) => sum + i.amount, 0));

    // Effects — use ONLY for side effects (DOM, localStorage, fetch), NEVER for derived values
    $effect(() => {
        localStorage.setItem('theme', theme);
    });
</script>
```

### $derived vs $effect — the golden rule

**90% of the time, use `$derived` instead of `$effect`.**

```svelte
<!-- Correct — derived value -->
const doubled = $derived(count * 2);

<!-- Wrong — effect to set derived state -->
let doubled = $state(0);
$effect(() => { doubled = count * 2; }); // NEVER do this
```

| Use `$derived` when | Use `$effect` when |
|---|---|
| Computing a value from state | Syncing with external systems (DOM, localStorage, network) |
| Filtering, mapping, reducing | Running cleanup logic |
| Any pure transformation | Logging, analytics |

### $effect pitfalls

- **No state mutations inside `$derived`** — Svelte disallows this
- **No circular updates in `$effect`** — if effect A mutates state B and effect B mutates state A, infinite loop
- **Keep effects focused** — one effect per concern, not a mega-effect
- **Use cleanup** — return a cleanup function for subscriptions/timers:

```svelte
$effect(() => {
    const interval = setInterval(() => tick++, 1000);
    return () => clearInterval(interval);
});
```

## Inertia Page Pattern

Pages live in `resources/js/pages/`. Module-level exports define layout metadata:

```svelte
<script module lang="ts">
    import { edit } from '@/routes/profile';

    export const layout = {
        breadcrumbs: [
            { title: 'Profile settings', href: edit() },
        ],
    };
</script>

<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';

    let { mustVerifyEmail, status = '' }: { mustVerifyEmail: boolean; status?: string } = $props();

    const user = $derived(page.props.auth.user);
</script>
```

## Shared Auth Props

Access the authenticated user from any page via `page.props.auth.user`:

```svelte
<script lang="ts">
    import { page } from '@inertiajs/svelte';

    const user = $derived(page.props.auth.user);
</script>
```

## Deferred Props — skeleton loading

When using `Inertia::defer()` on the server, show a skeleton while loading:

```svelte
<script lang="ts">
    let { stats }: { stats?: StatsData } = $props();
</script>

{#if stats}
    <StatsDisplay {stats} />
{:else}
    <div class="animate-pulse h-24 rounded bg-muted" />
{/if}
```

## shadcn-svelte

- Components live in `resources/js/components/ui/` — copied in, fully owned
- Add new components: `npx shadcn-svelte@latest add <component>`
- Built on `bits-ui` (headless primitives)
- Never edit `ui/` files directly — re-add to update
- Style: `new-york-v4` with `neutral` base color
- Icon library: `lucide-svelte`

## Design Tokens

Always use semantic tokens, never hardcode colors:

```
bg-background          — Page background
text-foreground        — Primary text
text-muted-foreground  — Secondary text
bg-card                — Card background
border-border          — Default border
bg-destructive         — Error/danger background
text-destructive       — Error text
bg-primary             — Primary button/accent
text-primary-foreground — Text on primary
bg-accent              — Hover/active states
bg-sidebar             — Sidebar background
```

## Theme

- Light and dark mode supported via `.dark` CSS class
- CSS custom properties in `:root` (light) and `.dark` (dark) in `resources/css/app.css`
- Theme management in `resources/js/lib/theme.svelte.ts`
- Use `dark:` Tailwind prefix for dark mode variants

## Layout Patterns

- Layout resolution in `app.ts`: page name determines which layout wraps the page
- Nested layouts supported: `[AppLayout, SettingsLayout]`
- Grid layouts: `grid gap-6 lg:grid-cols-[1fr_320px]` for main + sidebar
- Card-based pages: `space-y-6` for vertical card stacking
- Form layouts: `space-y-6` inside form containers

## Utilities

All utility functions live in `resources/js/lib/utils.ts`:

```typescript
import { cn } from '@/lib/utils';

// Class merging — use cn() for conditional Tailwind classes
cn('bg-background', isActive && 'bg-primary');
```

## Reactive Classes

For complex shared state, use classes with runes instead of stores:

```typescript
class Counter {
    count = $state(0);
    doubled = $derived(this.count * 2);

    increment() {
        this.count++;
    }
}

export const counter = new Counter();
```

## Forbidden

- `$effect` for derived values — use `$derived` instead
- Mutating state inside `$derived` — it's for pure computations
- Circular `$effect` chains — restructure as derived values
- Svelte stores (`writable`, `readable`) — use runes
- Inline `clsx()` or `twMerge()` calls — use `cn()` from `@/lib/utils`
- Hardcoded color values — use semantic design tokens
- Editing files in `resources/js/components/ui/` — re-add via shadcn CLI
- Editing files in `resources/js/actions/` or `resources/js/routes/` — auto-generated by Wayfinder
