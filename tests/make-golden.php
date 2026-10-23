<?php

// Records what build_invoice() prints for every case in cases.php.
// Run it only against the original script, never after refactoring started.

require __DIR__.'/../vendor/autoload.php';

foreach (Cases::all() as $name => $order) {
    ob_start();
    build_invoice($order);
    file_put_contents(__DIR__."/golden/{$name}.txt", ob_get_clean());
}

echo count(glob(__DIR__.'/golden/*.txt'))." golden files written\n";
