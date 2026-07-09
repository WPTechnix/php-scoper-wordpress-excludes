<?php

declare(strict_types=1);

/**
 * Maps each published package (a key under /symbols) to:
 *  - "require": the upstream composer package(s) it is sourced from
 *               (installed into build/vendor by build/composer.json).
 *  - "inputs":  the file/directory paths, relative to build/vendor/, that
 *               should be parsed for symbol declarations. Multiple inputs
 *               are merged into that single package's output.
 *
 * @return array<string, array{require: array<string, string>, inputs: string[]}>
 */
return [
    'wordpress' => [
        'require' => [
            'php-stubs/wordpress-stubs' => '*',
            'php-stubs/wordpress-globals' => '*',
        ],
        'inputs' => [
            'php-stubs/wordpress-stubs/wordpress-stubs.php',
            'php-stubs/wordpress-globals/wordpress-globals.php',
        ],
    ],

    // Note: woocommerce-packages-stubs.php includes stubs for WooCommerce's
    // bundled ActionScheduler_* classes, which overlap with the real source
    // parsed by the "action-scheduler" package below. This is harmless -
    // scoper.inc.php dedupes across packages when a consumer includes both.
    'woocommerce' => [
        'require' => [
            'php-stubs/woocommerce-stubs' => '*',
        ],
        'inputs' => [
            'php-stubs/woocommerce-stubs/woocommerce-stubs.php',
            'php-stubs/woocommerce-stubs/woocommerce-packages-stubs.php',
        ],
    ],

    // Real source (not stub-only) - woocommerce/action-scheduler ships its
    // actual implementation, so directories are walked recursively.
    'action-scheduler' => [
        'require' => [
            'woocommerce/action-scheduler' => '*',
        ],
        'inputs' => [
            'woocommerce/action-scheduler/action-scheduler.php',
            'woocommerce/action-scheduler/functions.php',
            'woocommerce/action-scheduler/classes',
            'woocommerce/action-scheduler/deprecated',
        ],
    ],

    'acf' => [
        'require' => [
            'php-stubs/acf-pro-stubs' => '*',
        ],
        'inputs' => [
            // Only the stub file itself. This package also ships unrelated
            // generator-tooling files (finder.php, functionMap.php,
            // visitor.php) at the same root that must NOT be parsed.
            'php-stubs/acf-pro-stubs/acf-pro-stubs.php',
        ],
    ],

    'wp-cli' => [
        'require' => [
            'php-stubs/wp-cli-stubs' => '*',
        ],
        'inputs' => [
            'php-stubs/wp-cli-stubs/wp-cli-stubs.php',
            'php-stubs/wp-cli-stubs/wp-cli-commands-stubs.php',
            'php-stubs/wp-cli-stubs/wp-cli-i18n-stubs.php',
            'php-stubs/wp-cli-stubs/wp-cli-tools-stubs.php',
        ],
    ],

    // Real source (not stub-only) - PSR-4 root is the `Puc/` directory.
    'plugin-update-checker' => [
        'require' => [
            'yahnis-elsts/plugin-update-checker' => '*',
        ],
        'inputs' => [
            'yahnis-elsts/plugin-update-checker/Puc',
        ],
    ],
];
