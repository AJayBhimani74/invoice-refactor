<?php

// One order per business rule. The golden files next to this were produced by running the
// original script, so they record what it really does, including its quirks.

class Cases
{
    private static function item($name, $price, $qty, $weight = 0.5)
    {
        return ['sku' => strtoupper(substr($name, 0, 3)), 'name' => $name, 'price' => $price, 'qty' => $qty, 'weight' => $weight];
    }

    private static function order($items, $type = 'retail', $ship_to = 'MH', $coupon = null, $first_order = false)
    {
        return [
            'customer' => ['type' => $type, 'first_order' => $first_order, 'state' => 'MH'],
            'ship_to' => $ship_to,
            'coupon' => $coupon,
            'items' => $items,
        ];
    }

    public static function all(): array
    {
        $item = fn (...$a) => self::item(...$a);
        $order = fn (...$a) => self::order(...$a);

        return [
            'plain_retail_same_state' => $order([$item('Notebook', 120.00, 3, 0.3)]),
            'plain_retail_other_state' => $order([$item('Notebook', 120.00, 3, 0.3)], 'retail', 'KA'),
            'international_has_no_tax' => $order([$item('Notebook', 120.00, 3, 0.3)], 'retail', 'INTL'),
            'bulk_five_percent_at_10' => $order([$item('Pens', 20.00, 10, 0.02)]),
            'bulk_ten_percent_at_50' => $order([$item('Pens', 20.00, 50, 0.02)]),
            'just_below_bulk_threshold' => $order([$item('Pens', 20.00, 9, 0.02)]),
            'wholesale_discount' => $order([$item('Cables', 300.00, 4, 0.1)], 'wholesale'),
            'vip_discount' => $order([$item('Cables', 300.00, 4, 0.1)], 'vip'),
            'welcome10_first_order_retail' => $order([$item('Lamp', 899.00, 2, 1.2)], 'retail', 'MH', 'WELCOME10', true),
            'welcome10_not_first_order' => $order([$item('Lamp', 899.00, 2, 1.2)], 'retail', 'MH', 'WELCOME10', false),
            'welcome10_ignored_for_wholesale' => $order([$item('Lamp', 899.00, 2, 1.2)], 'wholesale', 'MH', 'WELCOME10', true),
            'coupon_is_case_and_space_insensitive' => $order([$item('Lamp', 899.00, 2, 1.2)], 'retail', 'MH', ' welcome10 ', true),
            'flat200_with_big_enough_cart' => $order([$item('Chair', 1500.00, 2, 4.0)], 'retail', 'GJ', 'FLAT200'),
            'flat200_cart_too_small' => $order([$item('Mug', 900.00, 2, 0.4)], 'retail', 'GJ', 'FLAT200'),
            'freeship_coupon_domestic' => $order([$item('Chair', 700.00, 1, 4.0)], 'retail', 'KA', 'FREESHIP'),
            'freeship_coupon_international_still_pays' => $order([$item('Chair', 700.00, 1, 4.0)], 'retail', 'INTL', 'FREESHIP'),
            'unknown_coupon_is_ignored' => $order([$item('Mug', 300.00, 1, 0.4)], 'retail', 'MH', 'SAVE50'),
            'free_shipping_over_5000' => $order([$item('Monitor', 6000.00, 1, 5.0)], 'retail', 'KA'),
            'no_free_shipping_over_5000_international' => $order([$item('Monitor', 6000.00, 1, 5.0)], 'retail', 'INTL'),
            'heavy_parcel_zone_1' => $order([$item('Dumbbell', 400.00, 6, 2.5)], 'retail', 'GJ'),
            'heavy_parcel_zone_2' => $order([$item('Dumbbell', 400.00, 6, 2.5)], 'retail', 'KA'),
            'heavy_parcel_zone_3' => $order([$item('Dumbbell', 400.00, 6, 2.5)], 'retail', 'INTL'),
            'tiny_parcel_is_billed_as_1kg' => $order([$item('Sticker', 15.00, 1, 0.01)]),
            'discounts_cannot_go_below_zero' => $order([$item('Sticker', 100.00, 1, 0.01)], 'retail', 'MH', 'FLAT200', false),
            'multi_line_mixed_bulk' => $order([$item('Pens', 20.00, 50, 0.02), $item('Notebook', 120.00, 10, 0.3), $item('Bag', 799.00, 1, 0.9)], 'wholesale', 'KA'),
            'rounding_cgst_sgst_one_paisa_off' => $order([$item('Odd', 100.03, 1, 0.01)]),
        ];
    }
}
