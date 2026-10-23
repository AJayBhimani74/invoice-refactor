<?php

namespace Invoice\Coupons;

class CouponRegistry
{
    private const COUPONS = [
        'WELCOME10' => WelcomeCoupon::class,
        'FLAT200' => FlatCoupon::class,
        'FREESHIP' => FreeShippingCoupon::class,
    ];

    /** Unknown or empty codes give null: they are ignored, as before. */
    public function find(?string $code): ?Coupon
    {
        $class = self::COUPONS[strtoupper(trim((string) $code))] ?? null;

        return $class ? new $class : null;
    }
}
