# Livewire Components

Livewire **4.4** with **class-based** components (ADR-0008): PHP class in `app/Livewire`, Blade view in `resources/views/livewire`. `config/livewire.php` sets `make_command.type = 'class'` so `php artisan make:livewire Name` produces this layout.

Full-page components are registered with `Route::livewire()` in `routes/web.php` and render inside a layout from `resources/views/layouts` (`layouts::app` by default, `layouts::guest` via `#[Layout]`).

## Rules

- Components never contain business rules — they call services (see [SERVICE_LAYER.md](SERVICE_LAYER.md)).
- Authorise in `mount()` **and** in every action that changes data; route middleware does not protect later Livewire update requests on its own.
- Public properties are client-editable. Lock identifiers with `#[Locked]`.
- Catch `App\Exceptions\DomainException` subclasses and show them with `addError()`.
- No component dispatches browser/Livewire events yet.

## Layouts

| Layout | File | Used by |
|---|---|---|
| `layouts::app` | `resources/views/layouts/app.blade.php` | All signed-in pages (default `component_layout`). Nav: Dashboard; Users (only if `can('viewAny', User)`); user name + role; sign-out form (POST `/logout`). Shows `session('status')` flash. |
| `layouts::guest` | `resources/views/layouts/guest.blade.php` | Sign-in and password pages. Shows `session('status')` flash. |

## Components

### Auth\Login — `/login` (`login`, guest only)
- **Class / view:** `app/Livewire/Auth/Login.php` · `resources/views/livewire/auth/login.blade.php`
- **Properties:** `email`, `password`, `remember`
- **Actions:** `login()` — trims email, validates (`email` required|email, `password` required, `remember` boolean), calls `LoginService::attempt()`; on domain exception clears password and shows error on `email`; on success regenerates session and redirects to intended URL or `dashboard`.
- **Authorisation:** `guest` middleware.

### Auth\ForgotPassword — `/forgot-password` (`password.request`, guest only)
- **Class / view:** `app/Livewire/Auth/ForgotPassword.php` · `…/auth/forgot-password.blade.php`
- **Properties:** `email`, `linkRequested` (bool, switches the view to the neutral confirmation message)
- **Actions:** `sendResetLink()` — validates email, calls `PasswordResetService::sendResetLink()`; throttling error shown on `email`.

### Auth\ResetPassword — `/reset-password/{token}?email=…` (`password.reset`, guest only)
- **Class / view:** `app/Livewire/Auth/ResetPassword.php` · `…/auth/reset-password.blade.php`
- **Properties:** `token` (`#[Locked]`, from route), `email` (pre-filled from query string), `password`, `password_confirmation`
- **Actions:** `resetPassword()` — validates (`password` required|confirmed|`Password::defaults()`), calls `PasswordResetService::reset()`; on success flashes `passwords.reset` and redirects to `login`.

### Dashboard — `/dashboard` (`dashboard`)
- **Class / view:** `app/Livewire/Dashboard.php` · `…/dashboard.blade.php`
- **Purpose:** placeholder landing page showing the user's name and role.
- **Authorisation:** `auth`, `active` middleware.

### Users\Index — `/users` (`users.index`)
- **Class / view:** `app/Livewire/Users/Index.php` · `…/users/index.blade.php`
- **Properties:** `search` (`#[Url]`, filters name/email with `LIKE`), pagination via `WithPagination` (20 per page)
- **Actions:** none (search resets page).
- **Authorisation:** route `can:viewAny,User`; `authorize('viewAny', User::class)` in `mount()`.

### Users\Create — `/users/create` (`users.create`)
- **Class / view:** `app/Livewire/Users/Create.php` · `…/users/create.blade.php` (fields in `…/users/partials/form-fields.blade.php`)
- **Properties:** `form` (`UserForm`)
- **Actions:** `save()` — authorises `create`, validates via `UserForm::payload()`, calls `UserService::create()`, flashes status, redirects to `users.index`.

### Users\Edit — `/users/{user}/edit` (`users.edit`)
- **Class / view:** `app/Livewire/Users/Edit.php` · `…/users/edit.blade.php`
- **Properties:** `user` (`#[Locked]`, route-model bound by UUID), `form` (`UserForm`), `statusMessage`
- **Actions:**
  - `save()` — authorises `update`, calls `UserService::update()`; `UserManagementException` shown on `form.role`.
  - `deactivate()` — authorises `deactivate` (admin and not self), calls `UserService::deactivate()`; errors shown on `status`. Hidden in the view for the signed-in user's own record.
  - `activate()` — authorises `update`, calls `UserService::activate()`.

## Form objects

### Forms\UserForm — `app/Livewire/Forms/UserForm.php`
- Fields: `name`, `email`, `role`, `password`, `password_confirmation`, `user` (null when creating).
- Rules: `name` required|max:255; `email` required|email|max:255|unique (ignoring the edited user); `role` `Rule::enum(UserRole)`; `password` required on create / nullable on edit, `confirmed`, `Password::defaults()`.
- `payload()` lower-cases/trims email, validates, returns the array `UserService` expects.

## Non-Livewire entry points

| Entry point | File | Purpose |
|---|---|---|
| `POST /logout` (`logout`) | `app/Http/Controllers/Auth/LogoutController.php` | `LoginService::logout()`, invalidate session, regenerate CSRF token, redirect to login |
| `php artisan app:create-admin-user` | `app/Console/Commands/CreateAdminUser.php` | Create an administrator (bootstrap a new install). Options `--name --email --password`; prompts (password hidden) when omitted |
