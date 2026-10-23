<?php

use Invoice\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_rupees_become_whole_paise(): void
    {
        $this->assertSame(1999, Money::fromRupees(19.99)->paise);
        $this->assertSame(1999, Money::fromRupees('19.99')->paise);
        $this->assertSame(500, Money::fromRupees(5)->paise);
    }

    public function test_adding_and_subtracting(): void
    {
        $this->assertSame(350, Money::fromPaise(100)->plus(Money::fromPaise(250))->paise);
        $this->assertSame(-50, Money::fromPaise(100)->minus(Money::fromPaise(150))->paise);
        $this->assertSame(0, Money::fromPaise(-50)->atLeastZero()->paise);
    }

    public function test_share_is_rounded_half_up_to_the_paisa(): void
    {
        $this->assertSame(8, Money::fromPaise(100)->basisPoints(800)->paise);
        $this->assertSame(6, Money::fromPaise(125)->basisPoints(500)->paise);
        $this->assertSame(6, Money::fromPaise(110)->basisPoints(500)->paise);
        $this->assertSame(5, Money::fromPaise(109)->basisPoints(500)->paise);
        $this->assertSame(0, Money::fromPaise(0)->basisPoints(1800)->paise);
    }

    public function test_formatting(): void
    {
        $this->assertSame('1234.50', Money::fromPaise(123450)->format());
        $this->assertSame('0.05', Money::fromPaise(5)->format());
    }

    public function test_comparing(): void
    {
        $this->assertTrue(Money::fromPaise(500)->isAtLeast(Money::fromPaise(500)));
        $this->assertFalse(Money::fromPaise(499)->isAtLeast(Money::fromPaise(500)));
        $this->assertTrue(Money::fromPaise(1)->isPositive());
        $this->assertFalse(Money::zero()->isPositive());
    }
}
