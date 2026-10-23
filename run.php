<?php

require __DIR__.'/src/invoice.php';

build_invoice(require $argv[1] ?? __DIR__.'/sample/order.php');
