# Refactoring log

One entry per commit, in order. "Safety net" is what would have caught a mistake.

| # | Commit | What and why | Safety net |
|---|---|---|---|
| 1 | Add the invoice script as it is in production | The starting point. One function that calculates and prints, magic numbers, two globals. | none |
| 2 | Set TAX and SELLER_STATE through `$GLOBALS` | My first test run showed no tax: the globals only exist when the file is included at top level. Smallest possible fix, so the script behaves the same wherever it is loaded. | manual run of `run.php` |
| 3 | Add composer and phpunit | Tooling only. | |
| 4 | Add characterization tests | 26 orders, one per rule or edge, with golden output from the original script. | the tests themselves |
| 5 | Split calculating from printing | `calculate_invoice()` returns an array, `print_invoice()` prints it. Now each can change independently. | 26 golden tests |
| 6 | Give the magic numbers names | `50 => 0.10` and friends moved to named constants, the `if` ladders became lookups. | 26 golden tests |
| 7 | Move shipping into `ShippingCalculator` | First class extracted. Added unit tests for zones, weight rounding and free shipping. | golden + new unit tests |
| 8 | Move tax into `TaxCalculator`, drop the globals | Seller state and rate became constructor arguments. The global variables are gone. | golden + unit tests |
| 9 | Move discounts into small classes | `BulkDiscount`, `CustomerDiscount`, a `Coupon` interface with one class per coupon, and `CouponRegistry`. | golden + unit tests |
| 10 | Move calculating and printing into classes | `InvoiceCalculator` and `InvoicePrinter`. `build_invoice()` stays as a thin wrapper. | golden tests |
| 11 | Add orders where a discount ends in a fraction of a paisa | Found by comparing the original script with the new code on many random orders. Four of the differing orders were added as cases, with output **from the original script**. Green, because the code had not changed yet. | |
| 12 | Add `Money` | An amount in whole paise, with half-up rounding by basis points. Tests for it. | `MoneyTest` |
| 13 | Calculate in whole paise | **Behaviour change.** Discounts are now rounded to the paisa. The four cases from step 11 changed by one paisa in tax or total, the other 26 did not. I reviewed each diff before updating those four files. | golden diff review |
| 14 | Pin the CGST/SGST rounding quirk | CGST and SGST are rounded separately and can differ by a paisa from the rounded 18%. Left as it was and covered by a test, because it may be correct. | `TaxCalculatorTest` |
| 15 | Add CI workflow | PHPUnit on PHP 8.2 and 8.3. | |

## What I would do differently

- Write the random comparison against the original script **before** step 5, not at step 11. It found cases my hand-picked examples missed.
- Replace the order arrays with small typed objects early on. I kept arrays to keep each step small.
