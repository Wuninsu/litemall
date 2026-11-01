# Business Rules

Plain-language rules the system enforces, why they exist, and where. _As of 2026-11-01 (Phase 3): user accounts, customers, products, sales, discounts, payments, balances, historical pricing and receipt numbering. Promotion, refund and cancellation rules will be added when built._

## Customers

| # | Rule | Why | Enforced in |
|---|---|---|---|
| C1 | Any signed-in staff member can add and edit customers — on the Customers page or from the POS ("New" opens a modal and selects the new customer for the sale). | Cashiers register customers at the counter without losing the sale in progress. | `CustomerPolicy::create/update`; `Sales\Create::saveNewCustomer` → `CustomerService::create` |
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

## Sales

| # | Rule | Why | Enforced in |
|---|---|---|---|
| S1 | Any signed-in staff member can record a sale. | Cashiers serve at the counter. | `SalePolicy::create` |
| S2 | A sale has at least one product; quantities are whole numbers ≥ 1; the same product added twice becomes one line. | A sale with nothing in it, or fractional units, is a mistake. | `Sales\Create` rules; `SaleService::mergeLines` (`noLines`, `invalidQuantity`) |
| S3 | Only active products with enough stock can be sold; stock is reduced when the sale is saved. | You cannot sell what you do not have. | `SaleService::lockProducts` (row lock, `productUnavailable`, `insufficientStock`); search hides inactive products and disables out-of-stock ones |
| S4 | **Historical pricing:** each line stores the product's name, SKU and unit price at the moment of sale. Later product edits never change past sales or receipts. | Receipts and balances must not change after the fact. | `sale_lines` snapshot columns; `SaleServiceTest::test_later_price_changes_do_not_change_past_sales` (ADR-0006) |
| S5 | Totals: subtotal = Σ unit price × qty; total = subtotal − discount. All in pesewas. | Exact money. | `SaleService::create`; see FINANCIAL_LOGIC.md |
| S6 | **Discounts:** one per sale, as GH₵ or %, never more than the subtotal; optional reason. % discounts round half up to the pesewa. | Simple, auditable discounts. | `SaleService::discountFor`; `Money::percentOf` |
| S7 | Cashiers may discount at most 10% of the subtotal (`sales.cashier_max_discount_percent`); larger discounts must be entered by an administrator (up to 100%). | Owner decision: discounts controlled, big ones approved. | `SaleService::discountFor` (`discountNeedsAdmin`); limit shown on the New sale screen |
| S8 | **Credit:** a sale may be paid in full, in part or not at all at the counter; the unpaid part is the customer's balance. No minimum deposit. | Owner decision: instalment sales. | `SaleService::create` |
| S9 | When no customer is chosen the sale is a **walk-in** sale (shown as "Walk-in customer" on the POS and "Walk-in" on lists and receipts). A balance requires a customer; walk-in sales must be paid in full. Inactive customers cannot be used. | Debt must belong to someone. | `creditNeedsCustomer`, `customerUnavailable` |
| S10 | The amount paid at the counter is between GH₵ 0.00 and the total, and needs a method (Cash, Mobile Money, Bank transfer, Card). With **Cash**, more than the total may be handed over: only the total is recorded and the difference is shown as change. | No overpayment or unknown money; cashiers see the change to give. | `overpayment`, `paymentMethodRequired`; `Sales\Create::summary()` caps cash |
| S11 | Sales are never edited or deleted once saved. | Financial history must be preserved (ADR-0004). | No edit/delete actions; corrections will come via refunds/cancellations |
| S12 | Submitting the same sale twice (double click, retry) records it once. | Prevents double stock reduction and double payments. | `sales.idempotency_key`; `SaleService` returns the existing sale |

## Receipt numbering

| # | Rule | Why | Enforced in |
|---|---|---|---|
| R1 | Receipts are numbered `R-` + 6-digit running number (`R-000001`, …), assigned when the sale is saved. | Owner decision; easy to read out and file. | `ReceiptNumberService`; `config/sales.php` |
| R2 | Numbers are unique, never reused, and a failed sale does not consume one (no gaps from errors). | Gaps or duplicates look like missing or hidden sales. | `counters` row locked and incremented inside the sale transaction; `sales.receipt_number` unique |

## Payments and balances

| # | Rule | Why | Enforced in |
|---|---|---|---|
| PY1 | Any signed-in staff member can receive a payment towards a customer's balance. | Customers pay at the counter. | `PaymentPolicy::create` |
| PY2 | **Oldest first:** a payment is applied to the customer's oldest unpaid sale, then the next, until it is used up. | Owner decision (ADR-0006): predictable and easy to explain. | `PaymentService::allocate`; preview shown before saving |
| PY3 | A payment must be more than GH₵ 0.00 and not more than the customer owes. Customers cannot build up credit. | Overpayments would need refunds or credit tracking, which do not exist yet. | `PaymentService::receive` (`amountMustBePositive`, `exceedsBalance`, `nothingOwed`) |
| PY4 | Each payment records method, optional reference, who received it and when; it shows which receipts it paid. | Traceability for cash-up and disputes. | `payments`, `payment_allocations` |
| PY5 | A customer's balance is the sum of (total − paid) over their sales; a sale's status is Paid, Part-paid or Unpaid from its paid amount. | One definition used everywhere. | `Customer::outstandingPesewas`, `scopeWithBalance`; `SaleStatus::for` |
| PY6 | Payments are never edited or deleted; inactive customers can still pay what they owe. | Preserve history; collect debts. | No edit/delete actions; `receive` does not check `is_active` |
| PY7 | Two payments taken at the same moment cannot over-settle a balance; submitting the same payment twice records it once. | Money must add up under real-world use. | Row locks on unpaid sales; `payments.idempotency_key` |

## Wholesale pricing (ADR-0019)

| # | Rule | Why | Enforced in |
|---|---|---|---|
| W1 | A product may have a wholesale price (optional), which must be more than GH₵ 0.00 and not more than the retail price, and a "wholesale from" minimum quantity (≥ 1, default 1). Only administrators set them. | Traders buy in bulk at lower prices; a wholesale price above retail is a mistake. | `ProductForm`, `ProductService` (`invalidWholesalePrice`, `invalidWholesaleMinimum`), `ProductPolicy` |
| W2 | Each sale is rung up at **Retail** (default) or **Wholesale** prices. Any staff member can switch on the POS. | Cashiers serve traders at the counter. | `Sales\Create::setPriceTier`; `SaleService` input `price_tier` |
| W3 | In wholesale mode, a line gets the wholesale price only if the product has one **and** the line quantity reaches its minimum; otherwise that line is charged retail. | Wholesale prices are for bulk quantities. | `Product::unitPriceFor()` (used by POS preview and `SaleService`) |
| W4 | Customers can be marked as wholesale customers; choosing one on the POS switches the sale to wholesale. Switching back to walk-in returns to retail. | Fewer mistakes with regular traders. | `customers.is_wholesale`; `Sales\Create::selectCustomer/clearCustomer` |
| W5 | The tier is recorded on the sale and on every line, together with the price charged, and shown on receipts and the sales list. Later price changes never alter past sales. | Audit trail for lower-price sales; historical pricing (S4). | `sales.price_tier`, `sale_lines.price_tier`, `unit_price_pesewas` snapshot |
| W6 | Discounts (S6–S7) still apply on top of wholesale prices. | One discount mechanism. | `SaleService::discountFor` on the wholesale subtotal |
