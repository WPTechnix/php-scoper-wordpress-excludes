<?php

declare(strict_types=1);

/**
 * Load excluded symbols for the given packages.
 */
function getExcludedSymbols(array $packages, string $symbolType): array
{
    $excludedSymbols = [];

    foreach ($packages as $package) {
        $file = __DIR__
            . DIRECTORY_SEPARATOR
            . 'symbols'
            . DIRECTORY_SEPARATOR
            . $package
            . DIRECTORY_SEPARATOR
            . $symbolType
            . '.json';

        if (! is_file($file)) {
            continue;
        }

        try {
            $symbols = json_decode(
                (string) file_get_contents($file),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (Throwable) {
            continue;
        }

        if (! is_array($symbols)) {
            continue;
        }

        $excludedSymbols = array_merge($excludedSymbols, $symbols);
    }

    return array_values(array_unique($excludedSymbols));
}

return static function (
    array $include = [],
    array $exclude = [],
): array {
    $defaultPackages = [
        'wordpress',
        'woocommerce',
        'action-scheduler',
        'acf',
        'wp-cli',
        'plugin-update-checker',
    ];

    $packages = array_unique(
        array_merge(
            array_diff($defaultPackages, $exclude),
            $include
        )
    );

    return [
        'exclude-classes'   => getExcludedSymbols($packages, 'classes'),
        'exclude-functions' => getExcludedSymbols($packages, 'functions'),
        'exclude-constants' => getExcludedSymbols($packages, 'constants'),
    ];
};
