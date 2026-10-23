<?php

namespace Invoice;

use Invoice\Coupons\CouponRegistry;

class InvoiceCalculator
{
    public function __construct(
        private BulkDiscount $bulk = new BulkDiscount,
        private CustomerDiscount $customerDiscount = new CustomerDiscount,
        private CouponRegistry $coupons = new CouponRegistry,
        private ShippingCalculator $shipping = new ShippingCalculator,
        private TaxCalculator $tax = new TaxCalculator,
    ) {
    }

    public function calculate(array $order): array
    {
        $sub = 0;
        $weight = 0;
        $lines = [];
        foreach ($order['items'] as $i) {
            $line = round($this->bulk->lineTotal($i['price'], $i['qty']), 2);
            $sub = $sub + $line;
            $weight = $weight + $i['weight'] * $i['qty'];
            $lines[] = ['name' => $i['name'], 'qty' => $i['qty'], 'amount' => $line];
        }

        $disc = $this->customerDiscount->amount($order['customer']['type'], $sub);
        $after = $sub - $disc;

        $coupon = $this->coupons->find($order['coupon'] ?? null);
        $cdisc = $coupon ? $coupon->amount($order, $sub, $after) : 0;
        $freeship = $coupon ? $coupon->freeShipping() : false;
        $after = max(0, $after - $cdisc);

        $ship = $this->shipping->cost($order['ship_to'], $weight, $after, $freeship);

        $base = $after + $ship;
        $taxLines = $this->tax->lines($base, $order['ship_to']);

        return [
            'lines' => $lines,
            'subtotal' => $sub,
            'customer_discount' => $disc,
            'coupon_discount' => $cdisc,
            'shipping' => $ship,
            'tax_lines' => $taxLines,
            'total' => round($base + array_sum($taxLines), 2),
        ];
    }
}
