# How invoice-refactor works

Read this before an interview. The code is small, the method is the interesting part.

## What the code does

`build_invoice($order)` takes an order (customer, destination, coupon, items) and prints an invoice:

1. **Lines.** Price x quantity, minus a bulk discount: 5% from 10 pieces, 10% from 50.
2. **Customer discount** on the subtotal: wholesale 8%, VIP 12%.
3. **Coupon** (optional): `WELCOME10` is 10% off for retail customers on their first order, `FLAT200` is 200 off if the subtotal is at least 2000, `FREESHIP` removes shipping. Unknown codes are ignored.
4. **Shipping** by zone (MH/GJ, other states, international) and by weight rounded up to whole kilos. Free for domestic orders when the payable amount is 5000 or more.
5. **Tax**, 18% GST: same state as the seller gives CGST + SGST (9% each), another state gives IGST 18%, international gives none.

## Where things live after the refactoring

| File | Job |
|---|---|
| `src/invoice.php` | `build_invoice()`, kept for old callers. Calculates, prints, returns the total. |
| `src/Invoice/InvoiceCalculator.php` | Runs the steps above in order and returns a plain array. |
| `src/Invoice/InvoicePrinter.php` | Turns that array into the text. |
| `src/Invoice/BulkDiscount.php`, `CustomerDiscount.php` | The two automatic discounts. |
| `src/Invoice/Coupons/` | A `Coupon` interface, one class per coupon, and `CouponRegistry` that finds a coupon by code. |
| `src/Invoice/ShippingCalculator.php` | Zones, weight, free shipping. |
| `src/Invoice/TaxCalculator.php` | CGST/SGST/IGST. |
| `src/Invoice/Money.php` | An amount in whole paise with plus, minus, share and format. |
| `tests/` | Characterization tests with golden files, plus unit tests per class. |

## The method: refactoring with a safety net

You can't safely change code you don't understand and can't test. The steps are:

1. **Don't change anything yet. Record what it does.** For each business rule, write an example order, run the *original* script and save its output in `tests/golden/`. These are **characterization tests**: they don't say the output is right, they say it is what happens today. A refactoring is only a refactoring if all of them stay green.
2. **Change structure in tiny steps.** After each step run the tests. If they go red, the last step was the problem, and it was small, so it is easy to find.
3. **Keep behaviour changes separate from structure changes.** When something really has to change, make it its own commit, say so in the message, and look at exactly which golden files changed.

The golden files came from the original script (`tests/make-golden.php`). The four newest ones were also produced by the original script, then updated by hand and reviewed when I made the one deliberate change.

## What I found on the way

- **A hidden dependency.** The first test run printed no tax at all. The script used global variables, and they only exist if the file is included from the top level. Composer includes it from inside a function. That is why globals are bad: the code works or not depending on *where it is loaded*. Fixed in its own tiny commit, then removed entirely a few commits later.
- **A float rounding problem.** With floats, a discount like 8% of 1393.45 is 111.476 and was never rounded, so that fraction flowed into the tax and could shift the total by one paisa. Moving to whole paise fixed it, but this changes results, so it was done as a labelled behaviour change. Only the four cases built to hit this changed.
- **A bug I introduced.** After moving to `Money`, 26 tests failed even though the printed text was identical. My wrapper returned an `int` when the paise divided evenly (`63000 / 100` is the integer `630` in PHP) and the test expected a float. The tests caught it immediately.
- **Something that looks like a bug but may not be.** CGST and SGST are each rounded, so together they can differ by a paisa from rounding the full 18%. GST invoices normally round each component, so I did not "fix" it. A test pins it and the comment says it is a question for the business.

## Questions you may get

**How do you refactor code that has no tests?** Characterization tests first. Pick inputs that cover each rule and edge, run the old code, save the output, and assert on it. It's not elegant but it is safe.

**How do you decide what to extract first?** The easiest and safest seams first: separate calculating from printing, name the numbers, then pull out one responsibility at a time (shipping, tax, discounts).

**Why a Money class and not just floats?** Floats can't represent most decimal amounts exactly, and errors show up as one-paisa differences in tax or totals. Whole integers don't have that problem. For rounding I used basis points (1000 = 10%) with integer math, so even the rounding doesn't touch floats.

**Why one class per coupon?** A new coupon is a new class and one registry line. You don't open and edit a function that already works for other coupons, so you can't break them (open/closed principle).

**What did you not do?** Typed order objects instead of arrays, validation of input, a configuration file for rates, and money in more than one currency. They are sensible next steps, but each would be its own change.

**What if the golden files are wrong?** Then the tests lock in the wrong behaviour, which is why unusual results (like the CGST/SGST one) get written down and discussed rather than silently "fixed".

## Try it yourself

Check out an early commit (`git checkout 3058702`), run the tests, and read the original script. Then move forward one commit at a time with `git checkout <next hash>` and `vendor/bin/phpunit`. Try breaking something in the final code (change `1000` to `1100` in `WelcomeCoupon`) and see which tests fail.
