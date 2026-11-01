# Security

_As of 2026-11-01 (Phase 1). Update with every security-relevant change._

| Area | Current state |
|---|---|
| Authentication | Session-based sign-in, sign-out and password reset. See [AUTHENTICATION_AND_AUTHORIZATION.md](AUTHENTICATION_AND_AUTHORIZATION.md). No public registration. |
| Authorisation / roles | `admin` / `cashier` roles; `UserPolicy`; checks in route middleware **and** in every Livewire mount/action. |
| Account deactivation | `is_active` flag blocks sign-in; `active` middleware ends live sessions; session rows deleted and remember token rotated on deactivation. |
| Password security | bcrypt via `hashed` cast (cost 12; 4 in tests). `Password::defaults()` in `AppServiceProvider`: production = min 10 chars with letters, mixed case and numbers; other environments = min 8. Password changes by reset or by an admin end the user's other sessions. |
| Sessions | Database driver, 120-minute lifetime, `SESSION_ENCRYPT=false`. Regenerated on sign-in; invalidated on sign-out. |
| CSRF | Laravel's `web` middleware validates CSRF tokens on all POSTs, including Livewire requests and `/logout`. Token regenerated on sign-out. |
| XSS | All output uses Blade `{{ }}`. The only `{!! !!}` is in `components/icon.blade.php`, printing hard-coded SVG paths (never user data). |
| SQL injection | Eloquent / query builder bindings only. The user search uses a bound `LIKE` parameter. |
| Mass assignment | `User`, `Customer` and `Product` fillable fields are only ever filled from validated form data inside their services; `created_by` and `is_active` are set by the services, never from input. Livewire `#[Locked]` on `UserForm::$user` (the record being edited in the modal; covered by `test_the_user_being_edited_cannot_be_swapped_by_the_client`) `CustomerForm::$customer`, `ProductForm::$product`, `ProductsShow::$product` and `ResetPassword::$token`. |
| Rate limiting | Sign-in: 5 failures/min per email+IP. Reset-link requests: 5/min per IP, plus broker throttle of 1 link/min per account. |
| Account enumeration | Sign-in uses one message for unknown email and wrong password; reset-link form always shows the same confirmation. *Residual:* response timing differs slightly when the email is unknown (no hash check), and inactive accounts get a distinct message **after** a correct password. |
| Lockout protection | `UserService` prevents removing or demoting the last active admin, self-deactivation and self role change; uses `lockForUpdate()` so concurrent changes cannot bypass the check. |
| File uploads | Product photos only (`ProductForm` / `ProductService`). Validated server-side: `image` + `mimes:jpg,jpeg,png,webp` (content-checked, so a renamed file fails), max 2 MB; SVG is not accepted (avoids script-bearing images). Uploads go first to Livewire's temporary storage, then are stored under `storage/app/public/products/` with a random file name (the original name is never used). Only admins can upload (`ProductPolicy`). Old files are deleted on replace/remove; files from failed saves are cleaned up. Photos are public URLs (anyone with the link can view them) — do not upload sensitive images. |
| Financial operation protection | Sales and payments: returns/refunds are admin-only (`SalePolicy::refund`) with a required reason, locked rows and idempotency keys; policy checks in routes and actions; every write is one DB transaction; row locks on products (stock) and unpaid sales (balances); receipt counter locked; idempotency keys prevent duplicates; amounts validated as integer pesewas (no floats); sales and payments cannot be edited or deleted through the app; cashier discount limit enforced server-side (the screen only previews). Locked Livewire properties: `customerId` and `idempotencyKey` on the sale and payment screens, `Sales\Show::$sale`. |
| Audit logging | **No general audit log.** Recorded: `last_login_at`; who added each customer/product (`created_by`); who made each sale (`sold_by`) and received each payment (`received_by`) with timestamps. Not recorded: who edited customers/products/users, stock changes, price changes. |
| Sensitive data | Secrets in `.env` (git-ignored). `APP_DEBUG` must be `false` in production. `DatabaseSeeder` creates `password`-protected demo accounts and refuses to run in production. |
| Dependencies | `composer audit` (2026-11-01): 1 medium advisory, `symfony/mime` CVE-2026-45070. Unresolved. `npm audit`: 0 vulnerabilities. |

## Assumptions

- Production runs over HTTPS behind a correctly configured web server pointing at `public/`.
- `.env` is never committed and `APP_KEY` is unique per environment.
- A real mailer is configured in production; with `MAIL_MAILER=log`, reset links only appear in `storage/logs`.

## Known limitations

- No audit trail of administrative actions (user created/edited/deactivated).
- No two-factor authentication.
- No email verification (accounts are created by admins).
- Rate-limit state lives in the cache (`CACHE_STORE=database`); clearing the cache resets counters.
