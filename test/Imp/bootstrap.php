<?php

$loader = function($candidates) {
    // Cover root case and library case
foreach ($candidates as $candidate) {
    if (file_exists($candidate)) {
        require_once $candidate;
    }
}
};

$candidates1 = [
    dirname(__FILE__, 6) . '/vendor/autoload.php',
    '/vendor/autoload.php',
    'Horde/Test/Bootstrap.php'
];


$loader($candidates1);

Horde_Test_Bootstrap::bootstrap(dirname(__FILE__));


$candidates2 = [
     dirname(__FILE__, 1) . '/TestCase.php',
     'Horde/Test/Bootstrap.php',
    dirname(__FILE__, 4) . '/test/lib/Horde/Test/Bootstrap.php',
    dirname(__FILE__, 1) . '/Autoload.php',
    dirname(__FILE__, 1) .'/Stub/HtmlViewer.php',
    dirname(__FILE__, 1) . '/Stub/Imap.php',
    dirname(__FILE__, 1) . '/Stub/ItipRequest.php'
];

$loader($candidates2);

