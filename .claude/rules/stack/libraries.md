# Library Choices

## Core Framework

| Library | Why |
| --- | --- |
| **Laravel 13** | Full-stack PHP framework. MVC, Eloquent ORM, routing, middleware, queues, mail, validation. |
| **PHP 8.4** | Constructor property promotion, attributes, enums, match expressions, named arguments. |
| **Inertia.js v3** | SPA without SPA complexity. Server-side routing, client-side rendering. No API layer needed. |
| **Svelte 5** | Runes (`$props`, `$state`, `$derived`, `$effect`) replace stores. Cleaner reactivity model. |
| **TypeScript** | Strict mode on frontend. Type-safe imports, props, and Wayfinder-generated routes. |

## Auth

| Library | Why |
| --- | --- |
| **Laravel Fortify** | Authentication backend — login, registration, password reset, email verification, two-factor. No UI opinions. |

## Routing (Frontend)

| Library | Why |
| --- | --- |
| **Laravel Wayfinder** | Auto-generates typed TypeScript functions for controllers and named routes. No hardcoded URLs in frontend. |
| **@laravel/vite-plugin-wayfinder** | Vite plugin that runs `wayfinder:generate` automatically on file changes. Enables `formVariants`. |

## Styling

| Library | Why |
| --- | --- |
| **Tailwind CSS v4** | Utility-first, no context switching. v4 uses CSS-native config via `@theme` in `app.css`. |
| **shadcn-svelte** | Copy-paste UI components (not a dependency). Full control over source. New York v4 style. |
| **bits-ui** | Headless primitives under shadcn-svelte. Handles accessibility (ARIA, keyboard). |
| **tailwind-merge** | Safely merge Tailwind classes without conflicts. Used in `cn()`. |
| **clsx** | Conditional class string builder. Used in `cn()` with tailwind-merge. |
| **lucide-svelte** | Icon library. Tree-shakeable SVG icons. |
| **tw-animate-css** | Tailwind animation utilities for shadcn components. |

## Build

| Library | Why |
| --- | --- |
| **Vite 8** | Frontend bundler. HMR, TypeScript, Svelte compilation. |
| **laravel-vite-plugin** | Laravel asset bundling integration. Handles `resources/css/app.css` + `resources/js/app.ts` entry points. |
| **@inertiajs/vite** | Inertia SSR plugin. Auto-resolves pages from `resources/js/pages/`. |
| **@sveltejs/vite-plugin-svelte** | Svelte compiler for Vite. |
| **@tailwindcss/vite** | Tailwind CSS v4 Vite plugin. Replaces PostCSS config. |

## Testing

| Library | Why |
| --- | --- |
| **Pest v4** | Modern PHP testing with `it()`/`test()`/`expect()` syntax. Built on PHPUnit 12. |
| **pestphp/pest-plugin-laravel** | Laravel-specific Pest helpers and assertions. |

## Code Quality

| Library | Why |
| --- | --- |
| **Laravel Pint** | PHP code formatter. Uses Laravel preset. Run with `--dirty` to format changed files only. |
| **ESLint 9** | Lints TypeScript and Svelte files. Flat config with `@stylistic`, `import` plugins. |
| **Prettier 3** | Formats `.svelte` files. With `prettier-plugin-svelte` + `prettier-plugin-tailwindcss`. |

## Dev Tools

| Library | Why |
| --- | --- |
| **Laravel Sail** | Docker-based local development. |
| **Laravel Pail** | Real-time log viewer in the terminal. |
| **Laravel Boost** | MCP server for database, docs, and error inspection. |
| **Laravel Tinker** | PHP REPL in application context. |
