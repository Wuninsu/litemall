# Module: Products

_Implemented in Phase 2 (2026-11-01)._

## Purpose

Keep the list of things the shop sells, with a photo, the current selling price in GH₵ and the quantity in stock, for use when sales are recorded.

## Who can do what

| Action | Admin | Cashier |
|---|---|---|
| View / search the list and prices | ✓ | ✓ |
| Add a product | ✓ | — |
| Edit a product, change its price or stock, upload a photo | ✓ | — |
| Set the wholesale price and "wholesale from" quantity | ✓ | — |
| Open the product details page | ✓ | ✓ |
| Deactivate / reactivate | ✓ | — |

Enforced by `App\Policies\ProductPolicy` (ADR-0012). Cashiers see the list with no "New product" button and no edit buttons, plus the note "Prices and stock are set by an administrator."; calling the actions directly returns 403.

## Data

Table `products` (UUID key): `name`, `sku` (optional, unique, stored upper-case), `category` (optional free text), `price_pesewas` (integer, > 0), `wholesale_price_pesewas` (optional, ≤ retail), `wholesale_min_quantity` (default 1), `stock_quantity` (integer ≥ 0), `description`, `image_path` (file on the `public` disk), `is_active`, `created_by` → `users.id`. See [DATABASE_SCHEMA.md](../DATABASE_SCHEMA.md) and [FINANCIAL_LOGIC.md](../FINANCIAL_LOGIC.md).

## Rules

See P1–P8 in [BUSINESS_RULES.md](../BUSINESS_RULES.md). In short: name and a price above GH₵ 0.00 are required; SKUs are unique ignoring case; price changes affect future sales only; stock is a whole number ≥ 0 (Low at 1–5, Out at 0); one photo (JPG/PNG/WebP ≤ 2 MB); products are deactivated, never deleted.

## Code map

| Piece | File |
|---|---|
| Model | `app/Models/Product.php` (`creator()`, `formattedPrice()`, `stockLevel()`, `stockValuePesewas()`, `imageUrl()`, `scopeActive`; constants `LOW_STOCK_THRESHOLD`, `IMAGE_DISK`, `IMAGE_DIRECTORY`) |
| Service | `app/Services/ProductService.php` (also `categories()`) |
| Exceptions | `app/Exceptions/Products/ProductException.php` (carries the form `field`) |
| Money helper | `app/Support/Money.php` |
| Policy | `app/Policies/ProductPolicy.php` |
| List screen | `app/Livewire/Products/Index.php` + `resources/views/livewire/products/index.blade.php` |
| Details page | `app/Livewire/Products/Show.php` + `resources/views/livewire/products/show.blade.php` |
| Shared panel | `app/Livewire/Products/Concerns/ManagesProductPanel.php` + `resources/views/livewire/products/partials/panel.blade.php` |
| Stock badge | `resources/views/components/stock-badge.blade.php` |
| Form | `app/Livewire/Forms/ProductForm.php` (price typed in cedis, converted to pesewas in `payload()`) |
| Routes | `GET /products` → `products.index`; `GET /products/{product}` → `products.show` |
| Factory | `database/factories/ProductFactory.php` (`inactive()`, `priced(int $pesewas)` states) |
| Tests | `tests/Feature/Products/ProductManagementTest.php`, `tests/Feature/Products/ProductImageAndStockTest.php`, `tests/Feature/Services/ProductServiceTest.php`, `tests/Unit/MoneyTest.php` |

## Screen behaviour

- List: thumbnail + name (+ SKU) linking to the details page, category, price (`GH₵ 1,899.50`), stock badge, status, view/edit buttons; 15 per page.
- Filters: search (name or SKU), category (distinct categories in use), stock (Any / Low / Out), status (default Active).
- Details page: photo, category/status, name, SKU, selling price, quantity with badge, stock value (quantity × price), description, who added it and when; admins can edit from here with the same panel.
- Panel (no section titles): full-width photo area that is the file picker (click or drop; Change / Remove on the image), then name, SKU, category, prices and stock (on edit, a note that past sales keep their price), description, status.

## Not yet implemented

Automatic stock reduction when sales are recorded, stock-change history, cost price and margins, price-change history, multiple photos / image resizing, barcodes, bulk import, promotions.
