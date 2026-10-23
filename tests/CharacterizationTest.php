<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CharacterizationTest extends TestCase
{
    public static function orders(): array
    {
        $cases = [];
        foreach (Cases::all() as $name => $order) {
            $cases[$name] = [$name, $order];
        }

        return $cases;
    }

    #[DataProvider('orders')]
    public function test_output_matches_what_the_original_script_printed(string $name, array $order): void
    {
        ob_start();
        $total = build_invoice($order);
        $printed = ob_get_clean();

        $golden = file_get_contents(__DIR__."/golden/{$name}.txt");

        $this->assertSame($golden, $printed);
        $this->assertSame((float) trim(substr($golden, strrpos($golden, 'TOTAL: ') + 7)), $total);
    }
}
