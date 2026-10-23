# invoice-refactor

A refactoring case study. It starts with one realistic, messy PHP script that prices an order (bulk discounts, customer discounts, coupons, shipping zones, GST) and ends with small classes that each do one thing, with tests around all of it. The point is the **history**: every commit is a small step, and the tests were green after every one of them.

Read the commits in order:

```bash
git log --reverse --format='%h %s'
git show <hash>
```

It is a made-up example, not code from any client or employer.

## The script

`php run.php` prints an invoice for `sample/order.php`:

```
INVOICE
-------
Cotton t-shirt           12     5688.60
Steel mug                 2      499.00

Subtotal: 6187.60
Coupon discount: -618.76
Shipping: 0.00
CGST 9%: 501.20
SGST 9%: 501.20
TOTAL: 6571.24
```

## Before and after

| | Before | After |
|---|---|---|
| Structure | one 120-line function that calculates and prints | `InvoiceCalculator`, `InvoicePrinter`, and one class each for shipping, tax, bulk and customer discounts and coupons |
| Config | numbers inside `if` conditions, two global variables | constants named at the top of the class that uses them |
| Money | floats | whole paise in a small `Money` class |
| Adding a coupon | edit a long `if` / `else if` chain | add a class and one line in `CouponRegistry` |
| Tests | none | 51 tests: 30 that pin the original behaviour, the rest unit tests per class |

`build_invoice($order)` still exists and prints the same text, so callers of the old function are not affected.

## Run the tests

```bash
composer install
vendor/bin/phpunit
```

CI runs them on PHP 8.2 and 8.3.

## How to read this repo

- [docs/HOW-IT-WORKS.md](docs/HOW-IT-WORKS.md) explains the code and the method in plain words.
- [docs/REFACTORING-LOG.md](docs/REFACTORING-LOG.md) goes through the commits one by one: what changed, why, and what protected it.
