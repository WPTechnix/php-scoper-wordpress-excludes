<?php

namespace WP_CLI {

    class Autoloader
    {
    }

    function wp_not_installed(): void
    {
    }

    // define() constants are never namespace-qualified, even when the call
    // itself lives inside a namespace block - this mirrors real PHP semantics.
    define('FOO', 'BAR');
}

namespace WP_CLI\Utils {

    function get_upgrader(): void
    {
    }
}

namespace WP_CLI\Bootstrap {

    abstract class AutoloaderStep
    {
    }

    trait BarTrait
    {
    }

    trait FooTrait
    {
    }
}
