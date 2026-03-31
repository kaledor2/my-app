# Type Safety

TypeScript strict mode is enabled for frontend code. Never bypass the type system.

## Forbidden Patterns (TypeScript)

- **`any`** — Never use `any`. Use `unknown` and narrow with type guards.
- **`as` casts** — Never use `as Type` to force a type. Fix the actual type instead.
- **`@ts-ignore` / `@ts-expect-error`** — Never suppress type errors. Fix the root cause.
- **`!` non-null assertion** — Avoid. Handle the null case explicitly.
- **Svelte stores** (`writable`, `readable`, `derived` from `svelte/store`) — Use runes instead.

## Allowed Exceptions

- `as const` — Fine, it narrows to literal types.
- `@typescript-eslint/no-explicit-any` is off in ESLint config — but still avoid `any` in new code.

## Type Imports

ESLint enforces separate type imports:

```typescript
// Correct
import type { PageData } from './$types';
import { Form } from '@inertiajs/svelte';

// Wrong — mixed import
import { Form, type PageData } from '@inertiajs/svelte';
```

## Import Order

ESLint enforces alphabetical import ordering by group: builtin → external → internal → parent → sibling → index.

## Braces and Control Flow

ESLint enforces:
- `curly: 'all'` — always use braces, even for single-line `if`/`else`
- `1tbs` brace style — opening brace on same line, no single-line blocks
- Blank lines around control statements (`if`, `return`, `for`, `while`, etc.)

```typescript
// Correct
if (condition) {
    doSomething();
}

// Wrong — no braces
if (condition) doSomething();
```

## PHP Type Safety

- Use explicit return type declarations on all methods
- Use type hints for all parameters
- Use PHP 8 constructor property promotion
- Use `casts()` for model attribute types
- Use PHP 8 attributes (`#[Fillable]`, `#[Hidden]`) over `$fillable`/`$hidden` properties
- Use PHP enums for fixed sets of values

```php
// Correct
public function edit(Request $request): Response
{
    return Inertia::render('settings/Profile', [...]);
}

// Wrong — missing return type
public function edit(Request $request)
{
    return Inertia::render('settings/Profile', [...]);
}
```

## Wayfinder Type Safety

Wayfinder generates fully typed TypeScript functions. Always use them:

```typescript
// Correct — typed, auto-generated
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
ProfileController.update.form();

import { store } from '@/routes/login';
store.form();

// Wrong — hardcoded URL
<Form action="/settings/profile" method="patch">
```

## Svelte 5 Reactivity Types

- `$state()` — mutable reactive value
- `$derived()` — computed value (pure, no side effects)
- `$effect()` — side effect runner (DOM, localStorage, network only)
- `$props()` — component props with destructuring types

**Never use `$effect` to compute derived values** — use `$derived` instead.

## Rules

- All function parameters and return types must be explicit in PHP
- Use TypeScript `type` imports for type-only imports
- Use Wayfinder functions for all route references in frontend — never hardcode URLs
- Use `$derived()` for computed values — never mutable variables updated in `$effect`
- Use `$state()` for mutable reactive values — never `let` without runes for reactive state
- Prefer `satisfies` over `as` when checking type conformance in TypeScript
- Always use braces for control flow — ESLint enforces `curly: 'all'`
- Keep blank lines around control statements — ESLint enforces padding
