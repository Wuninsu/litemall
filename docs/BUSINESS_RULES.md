# Business Rules

Plain-language rules the system enforces, why they exist, and where. _As of 2026-11-01 only user-account rules exist; sales, payment, discount, promotion, refund, cancellation, pricing and receipt rules will be added here as those features are built._

## User accounts

| # | Rule | Why | Enforced in |
|---|---|---|---|
| U1 | Only administrators can create, edit, activate or deactivate users. | Staff access is a management decision. | `UserPolicy`; route `can:` middleware; `authorize()` in `Users\*` components |
| U2 | There is no public sign-up; the first administrator is created with `php artisan app:create-admin-user`. | The system is for staff only. | No registration route; `CreateAdminUser` command |
| U3 | Email addresses are unique and case-insensitive (stored lower-case). | One account per person; avoid `Ama@` vs `ama@` duplicates. | `UserService`, `LoginService`, `UserForm` unique rule, `users.email` unique index |
| U4 | Each user has exactly one role: Administrator or Cashier. | Simple, auditable permission model. | `UserRole` enum cast; `Rule::enum` validation |
| U5 | Users are deactivated, never deleted. | Future sales/payments must keep pointing at the staff member who recorded them. | No delete action exists; `UserService::deactivate()` |
| U6 | A deactivated user cannot sign in and is signed out of any open session immediately. | Leavers must lose access at once. | `LoginService`, `EnsureUserIsActive` middleware, session rows deleted in `UserService::deactivate()` |
| U7 | At least one active administrator must always exist. | Otherwise nobody could manage users again without database access. | `UserService::update()` / `deactivate()` with row locks |
| U8 | Users cannot deactivate themselves or change their own role. | Prevents accidental self-lockout and self-promotion. | `UserService`; `UserPolicy::deactivate`; Edit view hides the button |
| U9 | Changing a user's password (by reset or by an admin) signs them out everywhere else. | A password change usually means the old one may be compromised. | `PasswordResetService::reset()`, `UserService::update()` |
| U10 | Sign-in is blocked for one minute after 5 failed attempts for the same email from the same IP. | Slows password guessing. | `LoginService` |
