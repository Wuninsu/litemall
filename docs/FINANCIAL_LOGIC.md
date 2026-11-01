# Financial Logic

_As of 2026-11-01 (Phase 2). Only money **representation** and **product prices** exist. Sale totals, discounts, promotions, payments, allocation, balances, refunds, reversals and cancellations are not implemented yet; add each section here when it is built._

## Money representation (ADR-0005)

| Rule | Where |
|---|---|
| Every amount is stored as an **integer number of pesewas** (1 GHS = 100 pesewas). Column names end in `_pesewas`. | Migrations (`products.price_pesewas`, `unsignedBigInteger`) |
| Floats are never used for money: parsing is string-based, arithmetic is integer. | `App\Support\Money` |
| Conversion to/from cedis happens only at the edges: form input (`Money::parse`) and display (`Money::format`). | `ProductForm::payload()`, `Product::formattedPrice()` |

### `App\Support\Money`

| Method | Input → output | Notes |
|---|---|---|
| `parse(string)` | `"1,200.50"` → `120050` | Accepts thousands commas, up to 2 decimals, an optional `GH₵`/`GHS` prefix, surrounding spaces. Rejects negatives, 3+ decimals, letters, scientific notation, amounts above GH₵ 9,999,999,999.99 (`MAX_PESEWAS`). Throws `InvalidArgumentException`. |
| `isValid(string)` | `"12.345"` → `false` | Same rules as `parse` without throwing. |
| `format(int, bool $withSymbol = true)` | `120050` → `"GH₵ 1,200.50"` | Negative values render as `-GH₵ 12.30` (for future refunds/reversals). |
| `toInput(int)` | `120050` → `"1200.50"` | Pre-fills edit forms; round-trips through `parse`. |

### Worked examples (GH₵)

| Typed by staff | Stored (`price_pesewas`) | Shown |
|---|---|---|
| `3450` | 345000 | GH₵ 3,450.00 |
| `1,899.50` | 189950 | GH₵ 1,899.50 |
| `899.99` | 89999 | GH₵ 899.99 |
| `0.05` | 5 | GH₵ 0.05 |
| `12.5` | 1250 | GH₵ 12.50 |
| `0.10` + `0.20` | 10 + 20 = **30** | GH₵ 0.30 (exact; with floats 0.1 + 0.2 = 0.30000000000000004) |
| `12.345` | rejected | "Enter the price in cedis, e.g. 1200 or 1,200.50." |

## Product prices

- A product has one **current selling price** (`products.price_pesewas`), which must be greater than GH₵ 0.00 (`ProductForm` rule and `ProductService` guard → `ProductException::invalidPrice()`).
- Only administrators set or change prices (ADR-0012).
- Changing a price affects **future sales only**. Sales are not built yet; when they are, each sale line must copy the unit price at the moment of sale (ADR-0006, Proposed) so later price changes never alter past sales, receipts or balances. The product panel already tells admins this.
- There is no price-change history table yet; `products.updated_at` records only when the product was last changed.

## Stock value

`Product::stockValuePesewas()` = `stock_quantity × price_pesewas` (integer multiplication, no rounding). Shown on the product details page.

| Product | Quantity | Price | Stock value |
|---|---|---|---|
| Samsung 43-inch Smart TV | 12 | GH₵ 3,450.00 (345000) | 4,140,000 → **GH₵ 41,400.00** |
| Midea Microwave 20L | 15 | GH₵ 899.99 (89999) | 1,349,985 → **GH₵ 13,499.85** |
| Nasco 32-inch LED TV | 0 | GH₵ 1,650.00 | 0 → **GH₵ 0.00** |

It is valued at the **selling** price; there is no cost price yet, so it is not an accounting (cost) valuation.

## Not yet implemented

Sale totals · discounts · promotional prices · initial and subsequent payments · payment allocation (oldest-first proposed, ADR-0006) · outstanding balances · refunds · reversals · cancelled sales · concurrency rules for payments.
