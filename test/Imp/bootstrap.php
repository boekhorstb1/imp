<?php

$candidates = [
    'Horde/Test/Bootstrap.php',
    dirname(__FILE__, 4) . '/test/lib/Horde/Test/Bootstrap.php',
    dirname(__FILE__, 1) . '/TestCase.php',
    dirname(__FILE__, 6) . '/vendor/autoload.php'
];
// Cover root case and library case
foreach ($candidates as $candidate) {
    if (file_exists($candidate)) {
        require_once $candidate;
    }
}

Horde_Test_Bootstrap::bootstrap(dirname(__FILE__));
