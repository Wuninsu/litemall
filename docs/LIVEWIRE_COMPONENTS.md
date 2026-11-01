# Livewire Components

Livewire **4.4** with **class-based** components (ADR-0008): PHP class in `app/Livewire`, Blade view in `resources/views/livewire`. `config/livewire.php` sets `make_command.type = 'class'` so `php artisan make:livewire Name` produces this layout. Visual design: ADR-0011.

Full-page components are registered with `Route::livewire()` in `routes/web.php` and render inside a layout from `resources/views/layouts` (`layouts::app` by default, `layouts::guest` via `#[Layout]`). The `#[Title]` attribute sets both the browser title and the top-bar heading.

## Rules

- Components never contain business rules — they call services (see [SERVICE_LAYER.md](SERVICE_LAYER.md)).
- Authorise in `mount()` **and** in every action that changes data; route middleware does not protect later Livewire update requests on its own.
- Public properties are client-editable. Lock identifiers with `#[Locked]`.
- Catch `App\Exceptions\DomainException` subclasses and show them with `addError()`.
- Build screens from the shared Blade components below. Colours: `bg-brand` (deep teal `#0b3b3a`) for brand surfaces (auth background, sidebar, dashboard banner); the `brand-texture` grid utility is used on the auth background only; `bg-canvas` for the signed-in page background; Tailwind `teal` for accents and buttons; `slate` for neutrals. Tables and forms stay on white for readability. Tokens live in `resources/css/app.css`.
- No component dispatches browser/Livewire events yet.

## The list + modal pattern (users now; customers and products next)

One full-page `Index` component per resource holds the list **and** a single modal used for both "New" and "Edit":

| Piece | Users implementation | Notes for new resources |
|---|---|---|
| List filters | `#[Url] $search`, `$role`, `$status`; `updated()` resets pagination | Keep filters in the URL so they survive refresh |
| Form object | `public UserForm $form` with `#[Locked] public ?User $user` | `$form->user === null` means "creating" |
| Modal state | `public bool $showModal`, bound with `<x-modal wire:model="showModal" position="right" max-width="max-w-xl">` | Right-hand full-height panel; Escape, backdrop click and Cancel all close it |
| Open for create | `create()` → authorise `create`, `form->reset()`, `resetValidation()`, open | Always reset so old values never leak in |
| Open for edit | `edit(string $id)` → `findOrFail`, authorise `update`, `form->setUser()`, open | Takes the UUID; authorise on the loaded record |
| Save | `save(Service)` → authorise create/update, `form->payload()`, call service, set `$notice`, `closeModal()` | Domain errors → `addError()` and keep the modal open |
| Extra record actions | `deactivate()`, `activate()` inside the modal | Destructive actions use `wire:confirm` and the `danger` button |
| Feedback | `public ?string $notice` rendered as an auto-hiding banner | No session flash: there is no redirect |

## Layouts

| Layout | File | Description |
|---|---|---|
| `layouts::app` | `resources/views/layouts/app.blade.php` | Signed-in shell. Dark brand sidebar (flat `bg-brand`, no grid or glow; 256 px; slide-out with backdrop below `lg`, toggled by Alpine `sidebarOpen`) with sections Dashboard · Operations (Sales, Customers, Products, Payments — shown as **Soon** until built) · Insights (Reports, Soon) · Admin (Users, only if `can('viewAny', User)`); translucent user card with initials, role and sign-out button (POST `/logout`). Sticky top bar shows the page title and the mobile menu button. Shows `session('status')` flash. |
| `layouts::guest` | `resources/views/layouts/guest.blade.php` | Full-screen teal brand background (grid texture, two soft glows) with the logo top-left, the form centred in a frosted translucent card (`bg-white/[.07]`, `backdrop-blur-xl`), and feature lines plus the staff-only note at the bottom. Auth views use `x-input tone="dark"` and `x-button variant="bright"`. Shows `session('status')` flash. |

Both load Manrope through `@fonts` (self-hosted by the Vite fonts plugin, see `vite.config.js`) and assets through `@vite`.

## Shared Blade components (`resources/views/components`)

| Component | Props | Purpose |
|---|---|---|
| `<x-icon name="…">` | `name` (home, cart, people, box, wallet, chart, shield, search, plus, edit, x, logout, menu, check, receipt, clock) | Inline stroke icons; pass a `size-*` class to resize (default `size-5` only when none is given) |
| `<x-input>` | `label`, `id`, `type`, `hint`, `error` (defaults to the `wire:model` name), `tone` (`light` default, `dark` for translucent inputs on the brand background) | Labelled input with error/hint text and `aria-invalid` |
| `<x-button>` | `variant` (primary, secondary, ghost, danger, subtle, bright — for dark backgrounds), `size` (sm, md, lg, icon), `type`, `href` (renders a link), `navigate` (default true for links), `icon`, `icon-right`, `block`, `target`, `loading-text`, `loading` (false disables the loader) | One button for the whole app. **Loader:** shows a spinner and disables itself while its Livewire action runs; the target defaults to the `wire:click` expression (arguments included), or to any request for `type="submit"`; pass `target` for `wire:submit` forms. With `loading-text` the label is swapped; a leading `icon` is replaced by the spinner. Links never show the loader. Usage examples are in the component header comment |
| `<x-modal wire:model="prop">` | `title`, `subtitle`, `maxWidth`, `position` (`center` default, or `right`) | Dialog entangled with a boolean Livewire property; Escape/backdrop close. `center`: centred dialog, bottom sheet on phones. `right`: full-height (`h-dvh`) panel sliding in from the right; give the slot a flex-column form with a scrollable body and pinned footer (see users view) |
| `<x-nav-link>` | `href`, `icon`, `active`, `soon` | Sidebar item; `soon` renders a disabled item with a "Soon" pill |

## Components

### Auth\Login — `/login` (`login`, guest only)
- **Class / view:** `app/Livewire/Auth/Login.php` · `resources/views/livewire/auth/login.blade.php`
- **Properties:** `email`, `password`, `remember`
- **Actions:** `login()` — trims email, validates (`email` required|email, `password` required, `remember` boolean), calls `LoginService::attempt()`; on domain exception clears password and shows error on `email`; on success regenerates session and redirects to intended URL or `dashboard`.

### Auth\ForgotPassword — `/forgot-password` (`password.request`, guest only)
- **Class / view:** `app/Livewire/Auth/ForgotPassword.php` · `…/auth/forgot-password.blade.php`
- **Properties:** `email`, `linkRequested` (switches to the neutral "check your email" message)
- **Actions:** `sendResetLink()` — trims and validates email, calls `PasswordResetService::sendResetLink()`; throttling error shown on `email`.

### Auth\ResetPassword — `/reset-password/{token}?email=…` (`password.reset`, guest only)
- **Class / view:** `app/Livewire/Auth/ResetPassword.php` · `…/auth/reset-password.blade.php`
- **Properties:** `token` (`#[Locked]`), `email` (pre-filled from query string), `password`, `password_confirmation`
- **Actions:** `resetPassword()` — validates (`password` required|confirmed|`Password::defaults()`), calls `PasswordResetService::reset()`; on success flashes `passwords.reset` and redirects to `login`.

### Dashboard — `/dashboard` (`dashboard`)
- **Class / view:** `app/Livewire/Dashboard.php` · `…/dashboard.blade.php`
- **Shows:** brand banner (`bg-brand`) with date, time-based greeting and tagline; for admins, `teamStats` (computed: active staff, active admins, active cashiers, inactive accounts — `null` for non-admins); a "Coming next" list of modules; the user's account details. No sales figures until those modules exist.
- **Authorisation:** `auth`, `active` middleware; team stats gated by `can('viewAny', User)`.

### Users\Index — `/users` (`users.index`)
- **Class / view:** `app/Livewire/Users/Index.php` · `…/users/index.blade.php`
- **Properties:** `search`, `role`, `status` (all `#[Url]`), `form` (`UserForm`), `showModal`, `notice`; pagination (15 per page)
- **Actions:** `create()`, `edit(string $userId)`, `save()` (→ `UserService::create()` / `update()`), `deactivate()` (→ `UserService::deactivate()`, policy `deactivate`), `activate()` (→ `UserService::activate()`), `closeModal()`
- **Modal details:** right-hand full-height panel with sections Profile · Role · Password · Account status; role chosen from a stacked radio list, one row per `UserRole` case with label and `description()` (scales as roles are added; teal highlight when selected; disabled when editing yourself); password optional when editing; deactivate/reactivate section hidden for your own account; `UserManagementException` messages shown under Role (save) or the status section (deactivate).
- **Authorisation:** route `can:viewAny,User`; `authorize()` in `mount()` and every action.

## Form objects

### Forms\UserForm — `app/Livewire/Forms/UserForm.php`
- Fields: `user` (`#[Locked]`, null when creating), `name`, `email`, `role`, `password`, `password_confirmation`.
- Rules: `name` required|max:255; `email` required|email|max:255|unique (ignoring the edited user); `role` `Rule::enum(UserRole)`; `password` required on create / nullable on edit, `confirmed`, `Password::defaults()`.
- `payload()` lower-cases/trims email, validates, returns the array `UserService` expects.

## Non-Livewire entry points

| Entry point | File | Purpose |
|---|---|---|
| `POST /logout` (`logout`) | `app/Http/Controllers/Auth/LogoutController.php` | `LoginService::logout()`, invalidate session, regenerate CSRF token, redirect to login |
| `php artisan app:create-admin-user` | `app/Console/Commands/CreateAdminUser.php` | Create an administrator. Options `--name --email --password`; prompts (password hidden) when omitted |
