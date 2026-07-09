<?php

// Declarations guarded by class_exists()/function_exists() checks are a very
// common pattern in real WordPress-ecosystem source (unlike curated
// stub-only files) and must still be found.

if (! class_exists('Guarded_Class')) {
    class Guarded_Class
    {
    }
}

if (! function_exists('guarded_function')) {
    function guarded_function(): void
    {
    }
}

switch (PHP_VERSION_ID) {
    case 80100:
        class Switch_Guarded_Class
        {
        }
        break;
    default:
        break;
}

try {
    class Try_Guarded_Class
    {
    }
} catch (\Throwable $e) {
    // no-op
}

if (! defined('GUARDED_CONST')) {
    define('GUARDED_CONST', 'value');
}
