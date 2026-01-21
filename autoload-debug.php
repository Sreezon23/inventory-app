<?php

require __DIR__ . '/vendor/autoload.php';

foreach (get_included_files() as $file) {
    echo $file . PHP_EOL;
}
