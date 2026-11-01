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

## The list + modal pattern (users, customers, products)

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
| `layouts::app` | `resources/views/layouts/app.blade.php` | Signed-in shell. Dark brand sidebar (flat `bg-brand`, no grid or glow; 256 px; slide-out with backdrop below `lg`, toggled by Alpine `sidebarOpen`) with sections Dashboard · Operations (Sales — Soon, Customers, Products, Payments — Soon) · Insights (Reports, Soon) · Admin (Users, only if `can('viewAny', User)`); translucent user card with initials, role and sign-out button (POST `/logout`). Sticky top bar shows the page title and the mobile menu button. Shows `session('status')` flash. |
| `layouts::guest` | `resources/views/layouts/guest.blade.php` | Full-screen teal brand background (grid texture, two soft glows) with the logo top-left, the form centred in a frosted translucent card (`bg-white/[.07]`, `backdrop-blur-xl`), and feature lines plus the staff-only note at the bottom. Auth views use `x-input tone="dark"` and `x-button variant="bright"`. Shows `session('status')` flash. |

Both load Manrope through `@fonts` (self-hosted by the Vite fonts plugin, see `vite.config.js`) and assets through `@vite`.

## Shared Blade components (`resources/views/components`)

| Component | Props | Purpose |
|---|---|---|
| `<x-icon name="…">` | `name` (home, cart, people, box, wallet, chart, shield, search, plus, edit, x, logout, menu, check, receipt, clock, eye, arrow-left) | Inline stroke icons; pass a `size-*` class to resize (default `size-5` only when none is given) |
| `<x-input>` | `label`, `id`, `type`, `hint`, `error` (defaults to the `wire:model` name), `tone` (`light` default, `dark` for translucent inputs on the brand background), `prefix` (fixed text inside the field, e.g. `GH₵`) | Labelled input with error/hint text and `aria-invalid` |
| `<x-textarea>` | `label`, `id`, `rows` (3), `hint`, `error` | Multi-line version of `<x-input>` (light tone only) |
| `<x-stock-badge :product>` | `product` | Pill with quantity and "In stock" (green), "Low stock" (amber, 1–5) or "Out of stock" (red) from `Product::stockLevel()` |
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
- **Shows:** brand banner (`bg-brand`) with date, time-based greeting and tagline; for everyone, `shopStats` (computed: active customers, active products — each card links to its list); for admins, `teamStats` (computed: active staff, active admins, active cashiers, inactive accounts — `null` for non-admins); a "Coming next" list (Sales, Payments); the user's account details. No sales figures until those modules exist.
- **Authorisation:** `auth`, `active` middleware; team stats gated by `can('viewAny', User)`.

### Users\Index — `/users` (`users.index`)
- **Class / view:** `app/Livewire/Users/Index.php` · `…/users/index.blade.php`
- **Properties:** `search`, `role`, `status` (all `#[Url]`), `form` (`UserForm`), `showModal`, `notice`; pagination (15 per page)
- **Actions:** `create()`, `edit(string $userId)`, `save()` (→ `UserService::create()` / `update()`), `deactivate()` (→ `UserService::deactivate()`, policy `deactivate`), `activate()` (→ `UserService::activate()`), `closeModal()`
- **Modal details:** right-hand full-height panel with sections Profile · Role · Password · Account status; role chosen from a stacked radio list, one row per `UserRole` case with label and `description()` (scales as roles are added; teal highlight when selected; disabled when editing yourself); password optional when editing; deactivate/reactivate section hidden for your own account; `UserManagementException` messages shown under Role (save) or the status section (deactivate).
- **Authorisation:** route `can:viewAny,User`; `authorize()` in `mount()` and every action.

### Customers\Index — `/customers` (`customers.index`)
- **Class / view:** `app/Livewire/Customers/Index.php` · `…/customers/index.blade.php`
- **Properties:** `search` (name, email, or phone in any format via `PhoneNumber::searchFragment`), `status` (default `active`; `inactive`, `''` = all) — both `#[Url]`; `form` (`CustomerForm`), `showModal`, `notice`; 15 per page
- **Actions:** `create()`, `edit(string $customerId)`, `save()` (→ `CustomerService::create($payload, auth()->user())` / `update()`; `CustomerException` shown under `form.phone`), `deactivate()` / `activate()` (policy `changeStatus`, admins only), `closeModal()`
- **Panel:** sections Contact (name, phone with hint, email) · Details (location, notes) · Status (admins only).
- **Authorisation:** route `can:viewAny,Customer`; `authorize()` in `mount()` and every action. Cashiers can create/edit; the "New customer" button is wrapped in `@can('create')`.

### Products\Index — `/products` (`products.index`)
- **Class / view:** `app/Livewire/Products/Index.php` · `…/products/index.blade.php`
- **Properties:** `search` (name or SKU), `category` (exact match; options from `ProductService::categories()`), `stock` (`''`, `low` = 1–5, `out` = 0), `status` (default `active`) — all `#[Url]`; 15 per page. Panel state comes from the `ManagesProductPanel` trait.
- **List:** thumbnail (or box icon) + name + SKU, linking to the details page; category; price; `<x-stock-badge>`; status; view (eye) button for everyone and edit button for admins.
- **Authorisation:** route `can:viewAny,Product`; `authorize()` in `mount()` and every action. Cashiers get a read-only list: no New button, no edit buttons, note "Prices and stock are set by an administrator." (ADR-0012).

### Products\Show — `/products/{product}` (`products.show`)
- **Class / view:** `app/Livewire/Products/Show.php` · `…/products/show.blade.php`
- **Properties:** `product` (`#[Locked]`, route-model bound by UUID; unknown id → 404); panel state from `ManagesProductPanel`. Page title = product name (`->title()` in `render()`).
- **Shows:** large photo (or placeholder), category and status pills, name, SKU; cards for selling price, quantity in stock with badge, and stock value (quantity × price); description; record (added by, added on, last updated). "All products" back link.
- **Actions:** admins get "Edit product", which opens the same panel as the list; `productSaved()` reloads the product after save / status change so the page updates in place.
- **Authorisation:** everyone who can `viewAny` products can open it; the panel is rendered and its actions allowed only for admins.

### Products\Concerns\ManagesProductPanel (trait)
- **File:** `app/Livewire/Products/Concerns/ManagesProductPanel.php`; view partial `resources/views/livewire/products/partials/panel.blade.php` (expects `$form`, `$categories`).
- **Provides:** `WithFileUploads`, `form` (`ProductForm`), `showModal`, `notice`; actions `create()`, `edit($id)`, `save()` (→ `ProductService::create($payload, $user, $image)` / `update($product, $payload, $image, $removeImage)`; `ProductException` shown under `form.{field}`), `deactivate()`, `activate()` (policy `changeStatus`), `closeModal()`. Calls the component's optional `productSaved(Product)` hook.
- **Panel sections:** Photo (preview of the new upload via `temporaryUrl()`, or the current photo; "Upload/Change photo"; "Remove current photo" checkbox; "Uploading…" indicator) · Product (name, SKU, category with `<datalist>`) · Price and stock (`GH₵` price, quantity) · Description · Status. Save button shows its loader during `save` and during the photo upload.

## Form objects

### Forms\UserForm — `app/Livewire/Forms/UserForm.php`
- Fields: `user` (`#[Locked]`, null when creating), `name`, `email`, `role`, `password`, `password_confirmation`.
- Rules: `name` required|max:255; `email` required|email|max:255|unique (ignoring the edited user); `role` `Rule::enum(UserRole)`; `password` required on create / nullable on edit, `confirmed`, `Password::defaults()`.
- `payload()` lower-cases/trims email, validates, returns the array `UserService` expects.

### Forms\CustomerForm — `app/Livewire/Forms/CustomerForm.php`
- Fields: `customer` (`#[Locked]`), `name`, `phone`, `email`, `address`, `notes`. On edit the phone is shown formatted (`024 412 3456`).
- Rules: `name` required|max:255; `phone` required, valid Ghana number (closure using `PhoneNumber::normalise`), unique in `customers.phone` ignoring the edited customer; `email` nullable|email; `address` nullable|max:255; `notes` nullable|max:2000.
- `payload()` validates against the **normalised** phone (so `0244123456` collides with stored `+233244123456`), restores what the user typed in the field, and returns the normalised value.

### Forms\ProductForm — `app/Livewire/Forms/ProductForm.php`
- Fields: `product` (`#[Locked]`), `name`, `sku`, `category`, `price` (cedi text), `stock` (text, default `0`), `description`, `image` (Livewire temporary upload), `removeImage`. On edit the price is pre-filled with `Money::toInput()` (e.g. `1899.50`) and stock with the current quantity.
- Rules: `name` required|max:255; `sku` nullable|max:64|`[A-Za-z0-9-_./]`|unique (checked upper-cased, ignoring the edited product); `category` nullable|max:100; `price` required, `Money::isValid`, and > 0; `stock` required|integer|min:0|max:1000000; `description` nullable|max:2000; `image` nullable|image|mimes:jpg,jpeg,png,webp|max:2048 KB (`IMAGE_MAX_KB`).
- `payload()` returns `price_pesewas` via `Money::parse()` — the only place cedi text becomes pesewas for products — and `stock_quantity` as an int. The image is passed to the service separately.

## Non-Livewire entry points

| Entry point | File | Purpose |
|---|---|---|
| `POST /logout` (`logout`) | `app/Http/Controllers/Auth/LogoutController.php` | `LoginService::logout()`, invalidate session, regenerate CSRF token, redirect to login |
| `php artisan app:create-admin-user` | `app/Console/Commands/CreateAdminUser.php` | Create an administrator. Options `--name --email --password`; prompts (password hidden) when omitted |
