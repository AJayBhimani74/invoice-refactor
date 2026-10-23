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
        $subtotal = Money::zero();
        $weight = 0;
        $lines = [];

        foreach ($order['items'] as $item) {
            $amount = $this->bulk->lineTotal(Money::fromRupees($item['price']), $item['qty']);
            $subtotal = $subtotal->plus($amount);
            $weight += $item['weight'] * $item['qty'];
            $lines[] = ['name' => $item['name'], 'qty' => $item['qty'], 'amount' => $amount];
        }

        $customerDiscount = $this->customerDiscount->amount($order['customer']['type'], $subtotal);
        $payable = $subtotal->minus($customerDiscount);

        $coupon = $this->coupons->find($order['coupon'] ?? null);
        $couponDiscount = $coupon ? $coupon->amount($order, $subtotal, $payable) : Money::zero();
        $payable = $payable->minus($couponDiscount)->atLeastZero();

        $shipping = $this->shipping->cost($order['ship_to'], $weight, $payable, $coupon?->freeShipping() ?? false);

        $base = $payable->plus($shipping);
        $taxLines = $this->tax->lines($base, $order['ship_to']);

        $total = $base;
        foreach ($taxLines as $amount) {
            $total = $total->plus($amount);
        }

        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'customer_discount' => $customerDiscount,
            'coupon_discount' => $couponDiscount,
            'shipping' => $shipping,
            'tax_lines' => $taxLines,
            'total' => $total,
        ];
    }
}
