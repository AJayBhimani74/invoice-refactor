<?php

namespace Invoice;

/** An amount in whole paise, so there are no floating point surprises. */
final class Money
{
    private function __construct(public readonly int $paise)
    {
    }

    public static function fromPaise(int $paise): self
    {
        return new self($paise);
    }

    public static function fromRupees(float|int|string $rupees): self
    {
        return new self((int) round($rupees * 100));
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function plus(self $other): self
    {
        return new self($this->paise + $other->paise);
    }

    public function minus(self $other): self
    {
        return new self($this->paise - $other->paise);
    }

    public function times(int $quantity): self
    {
        return new self($this->paise * $quantity);
    }

    /** Share of this amount, rounded half up to the paisa. 1000 basis points is 10%. */
    public function basisPoints(int $points): self
    {
        return new self(intdiv($this->paise * $points * 2 + 10000, 20000));
    }

    public function atLeastZero(): self
    {
        return new self(max(0, $this->paise));
    }

    public function isZero(): bool
    {
        return $this->paise === 0;
    }

    public function isPositive(): bool
    {
        return $this->paise > 0;
    }

    public function isAtLeast(self $other): bool
    {
        return $this->paise >= $other->paise;
    }

    public function format(): string
    {
        return number_format($this->paise / 100, 2, '.', '');
    }
}
