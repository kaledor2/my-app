# Project Architecture

## Root Config Files

```
vite.config.ts        — Vite + Inertia + Svelte + Tailwind + Wayfinder
eslint.config.js      — ESLint flat config (TS + Svelte + import order)
tsconfig.json         — TypeScript config
components.json       — shadcn-svelte config (new-york-v4 style)
phpunit.xml           — PHPUnit / Pest config
```

## Directory Structure

```
app/
├── Actions/                      — Business logic actions
│   └── Fortify/                  — Fortify user creation, password reset
├── Concerns/                     — Shared traits
├── Http/
│   ├── Controllers/
│   │   ├── Controller.php        — Base controller
│   │   └── Settings/             — Settings controllers (Profile, Security)
│   ├── Middleware/                — HTTP middleware
│   └── Requests/                 — Form Request validation classes
│       └── Settings/             — Settings form requests
├── Models/
│   └── User.php                  — User model (Fortify, 2FA)
└── Providers/
    ├── AppServiceProvider.php    — App defaults (CarbonImmutable, Password rules, DB safety)
    └── FortifyServiceProvider.php — Auth views, actions, rate limiting

resources/
├── css/
│   └── app.css                   — Tailwind v4 config (@theme, CSS variables, dark mode)
├── js/
│   ├── app.ts                    — Inertia app entry (layout resolver)
│   ├── actions/                  — Wayfinder-generated controller functions (never edit)
│   ├── routes/                   — Wayfinder-generated route functions (never edit)
│   ├── wayfinder/                — Wayfinder config (never edit)
│   ├── components/
│   │   ├── ui/                   — shadcn-svelte components (never edit manually)
│   │   ├── AppHead.svelte        — Page title/meta
│   │   ├── AppLayout.svelte      — Main layout wrapper
│   │   ├── AppShell.svelte       — Shell container
│   │   ├── AppSidebar.svelte     — Navigation sidebar
│   │   ├── Breadcrumbs.svelte    — Breadcrumb navigation
│   │   ├── DeleteUser.svelte     — Account deletion form
│   │   ├── InputError.svelte     — Form error display
│   │   ├── PasswordInput.svelte  — Password field with visibility toggle
│   │   ├── TextLink.svelte       — Styled anchor/link
│   │   └── Heading.svelte        — Section heading
│   ├── layouts/
│   │   ├── AppLayout.svelte      — Authenticated layout
│   │   ├── AuthLayout.svelte     — Auth pages layout
│   │   ├── app/
│   │   │   ├── AppSidebarLayout.svelte
│   │   │   └── AppHeaderLayout.svelte
│   │   └── settings/
│   │       └── Layout.svelte     — Settings page layout
│   ├── pages/                    — Inertia pages (auto-resolved by filename)
│   │   ├── Welcome.svelte        — Landing page (no layout)
│   │   ├── Dashboard.svelte      — Authenticated dashboard
│   │   ├── auth/                 — Auth pages (Login, Register, ForgotPassword, etc.)
│   │   └── settings/             — Settings pages (Profile, Security, Appearance)
│   ├── lib/
│   │   ├── utils.ts              — cn() utility, toUrl()
│   │   └── theme.svelte.ts       — Dark/light mode management
│   └── types/                    — TypeScript type definitions
└── views/                        — Blade views (minimal — Inertia handles rendering)

routes/
├── web.php                       — Web routes (Inertia pages)
├── settings.php                  — Settings routes (included by web.php)
└── console.php                   — Artisan console commands

database/
├── migrations/                   — Schema migrations
├── factories/
│   └── UserFactory.php           — User factory
└── seeders/

tests/
├── Feature/
│   ├── Auth/                     — Auth tests (login, register, password, 2FA, etc.)
│   ├── Settings/                 — Settings tests (profile, security)
│   └── DashboardTest.php
├── Unit/
├── Pest.php                      — Pest config (RefreshDatabase for Feature tests)
└── TestCase.php                  — Base test case
```

## Layout Resolution

Layout is resolved in `resources/js/app.ts` based on page name:

| Page name pattern | Layout |
|---|---|
| `Welcome` | No layout (standalone) |
| `auth/*` | `AuthLayout` |
| `settings/*` | `[AppLayout, SettingsLayout]` (nested) |
| Everything else | `AppLayout` |

## Route Organization

- **Public routes** — No middleware (`/` landing page)
- **Auth routes** — Handled by Fortify (`/login`, `/register`, `/forgot-password`, etc.)
- **Protected routes** — `auth` + `verified` middleware (`/dashboard`)
- **Settings routes** — Split between `auth` (profile view/edit) and `auth` + `verified` (delete, security, appearance)

## Wayfinder

Auto-generates two directories (never edit manually):

- `resources/js/actions/` — TypeScript functions for controller methods (e.g., `ProfileController.update.form()`)
- `resources/js/routes/` — TypeScript functions for named routes (e.g., `login()`, `register()`)

Import from `@/actions/` for controller actions, `@/routes/` for named routes.

## Database

- SQLite in development (`database/database.sqlite`)
- Database-backed sessions, cache, and queue
- Migrations managed via `php artisan migrate`

## Theme

- Light + dark mode via `.dark` CSS class
- CSS custom properties in `:root` and `.dark` in `resources/css/app.css`
- Semantic tokens: `bg-background`, `text-foreground`, `text-muted-foreground`, `bg-card`, etc.
- Font: Instrument Sans
