<?php

namespace {
    class WP_Error
    {
        public function foo(): void
        {
            $unused = 'method bodies are never traversed';
        }
    }

    abstract class WP_Abstract_Thing
    {
    }

    interface FooInterface
    {
    }

    trait FooTrait
    {
    }

    trait BarTrait
    {
    }

    enum FooEnum
    {
        case ONE;
        case TWO;
    }

    function foo(): void
    {
    }

    function bar(): void
    {
    }

    const DB_NAME = 'bar';

    define('DB_USER', 'foo');

    \define('DB_PASSWORD', 'baz');

    $wpdb = null; // a loose variable assignment - must not be captured
}
