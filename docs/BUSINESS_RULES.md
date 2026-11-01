# Business Rules

Plain-language rules the system enforces, why they exist, and where. _As of 2026-11-01 (Phase 2): user-account, customer and product rules. Sales, payment, discount, promotion, refund, cancellation and receipt rules will be added here as those features are built._

## Customers

| # | Rule | Why | Enforced in |
|---|---|---|---|
| C1 | Any signed-in staff member can add and edit customers. | Cashiers register customers at the counter. | `CustomerPolicy::create/update` |
| C2 | A customer needs a name and a valid Ghana phone number (mobile or fixed line: national number starting 2, 3 or 5). Email, address and notes are optional. | The phone number is how staff find and contact the customer. | `CustomerForm` rules; `CustomerService` (`CustomerException::invalidPhone`) |
| C3 | Phone numbers are stored in one standard form (`+233XXXXXXXXX`) and each number can belong to only one customer, however it is typed (`024 412 3456` = `+233244123456`). | Prevents the same person being registered twice, which would split their balance across two records. | `PhoneNumber::normalise`; `CustomerForm` unique rule on the normalised value; `customers.phone` unique index; `CustomerService` (`phoneTaken`) for races |
| C4 | Only administrators can deactivate or reactivate a customer. | Hiding a customer affects everyone's work and, later, their credit. | `CustomerPolicy::changeStatus` |
| C5 | Customers are deactivated, never deleted; inactive customers stay in the database and are hidden from the default list. | Their sales and payments must remain traceable. Future sales will only accept active customers. | No delete action exists; `CustomerService::deactivate`; list status filter |
| C6 | The staff member who added a customer is recorded and cannot be changed by edits. | Basic accountability until full audit logging exists. | `customers.created_by` (FK, `restrictOnDelete`); `CustomerService::create` sets it, `update` never touches it |

## Products

| # | Rule | Why | Enforced in |
|---|---|---|---|
| P1 | Everyone can view products and prices; only administrators add products, edit them or change prices. | Prices are a management decision; cashiers must not change what customers are charged. | `ProductPolicy` (ADR-0012); edit controls hidden for cashiers |
| P2 | A product needs a name and a selling price greater than GH₵ 0.00, entered in cedis with at most two decimals. | A zero or fractional-pesewa price is always a mistake. | `ProductForm` price rule; `ProductService` (`ProductException::invalidPrice`) |
| P3 | Prices are stored as whole pesewas (integers). | Exact arithmetic for future totals and balances (ADR-0005). | `Money::parse`; `products.price_pesewas` |
| P4 | SKUs are optional; when given they are unique, ignoring upper/lower case (stored upper-case). Letters, numbers, `- _ . /` only. | Lets staff identify products by code without collisions. | `ProductForm` rules; `ProductService` upper-cases; `products.sku` unique index; `skuTaken` for races |
| P5 | Changing a price affects future sales only; past sales keep the price they were sold at. | Receipts and balances must never change after the fact. | Stated in the product panel; to be enforced by price snapshots on sale lines (ADR-0006) when sales are built |
| P6 | Products are deactivated, never deleted; the staff member who added them is recorded. | Past sales must keep pointing at the product. | No delete action; `ProductService::deactivate`; `products.created_by` |
| P7 | Each product has a quantity in stock: a whole number, 0 or more, set by administrators. 0 shows "Out of stock"; 1–5 shows "Low stock" (`Product::LOW_STOCK_THRESHOLD`). Stock value = quantity × current selling price. | Staff need to see what is available and what to reorder. | `ProductForm` (`integer`, `min:0`); `ProductService` (`invalidStock`); `Product::stockLevel()`, `stockValuePesewas()`. **Not yet:** sales do not reduce stock and there is no history of stock changes (ADR-0014) |
| P8 | A product may have one photo: JPG, PNG or WebP, up to 2 MB. Replacing or removing it deletes the old file; a failed save never leaves an orphaned file. | Helps staff pick the right item; keeps storage clean. | `ProductForm` `image` rules; `ProductService::persist()` |

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
