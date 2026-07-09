# php-scoper-wordpress-excludes

[![CI](https://github.com/WPTechnix/php-scoper-wordpress-excludes/actions/workflows/ci.yml/badge.svg)](https://github.com/WPTechnix/php-scoper-wordpress-excludes/actions/workflows/ci.yml)
[![Update Exclusion Symbols](https://github.com/WPTechnix/php-scoper-wordpress-excludes/actions/workflows/update.yml/badge.svg)](https://github.com/WPTechnix/php-scoper-wordpress-excludes/actions/workflows/update.yml)
[![Packagist Version](https://img.shields.io/packagist/v/wptechnix/php-scoper-wordpress-excludes)](https://packagist.org/packages/wptechnix/php-scoper-wordpress-excludes)
[![License](https://img.shields.io/packagist/l/wptechnix/php-scoper-wordpress-excludes)](LICENSE)

Automatically generated, curated exclude lists for [PHP-Scoper](https://github.com/humbug/php-scoper) covering the WordPress ecosystem.

Instead of maintaining large `exclude-classes`, `exclude-functions` and `exclude-constants` arrays by hand, this package generates them from upstream packages and keeps them up to date.

---

## Why this exists

When you scope a WordPress plugin or theme with PHP-Scoper, symbols provided by the host environment must **not** be prefixed. This includes WordPress itself, WooCommerce, Action Scheduler, ACF, WP-CLI and other commonly used packages.

Maintaining those symbol lists yourself is tedious, error-prone and quickly becomes outdated as upstream packages evolve.

This package automatically generates those lists from upstream source or stub packages, so you always have an up-to-date set of exclusions without any manual maintenance.

---

## Installation

```bash
composer require --dev wptechnix/php-scoper-wordpress-excludes
```

---

## Supported packages

| Package                 | Upstream source                                             | Coverage                                          |
| ----------------------- | ----------------------------------------------------------- | ------------------------------------------------- |
| `wordpress`             | `php-stubs/wordpress-stubs` + `php-stubs/wordpress-globals` | WordPress core                                    |
| `woocommerce`           | `php-stubs/woocommerce-stubs`                               | WooCommerce core and bundled packages             |
| `action-scheduler`      | `woocommerce/action-scheduler`                              | Action Scheduler                                  |
| `acf`                   | `php-stubs/acf-pro-stubs`                                   | Advanced Custom Fields Pro (superset of free ACF) |
| `wp-cli`                | `php-stubs/wp-cli-stubs`                                    | WP-CLI                                            |
| `plugin-update-checker` | `yahnis-elsts/plugin-update-checker`                        | Plugin Update Checker                             |

All supported packages are included by default.

---

## Usage

Require the helper from your `scoper.inc.php`:

```php
<?php

$getExcludes = require __DIR__ . '/vendor/wptechnix/php-scoper-wordpress-excludes/scoper.inc.php';

$wpExcludes = $getExcludes();

return [
    // Your PHP-Scoper configuration...

    'exclude-classes' => [
        ...$wpExcludes['exclude-classes'],
        // Add your own excluded classes here.
    ],

    'exclude-functions' => [
        ...$wpExcludes['exclude-functions'],
        // Add your own excluded functions here.
    ],

    'exclude-constants' => [
        ...$wpExcludes['exclude-constants'],
        // Add your own excluded constants here.
    ],
];
```

### Include only specific packages

By default, every supported package is included.

If you only need exclusions for specific packages, use the `include` argument.

```php
$wpExcludes = $getExcludes(
    include: [
        'wordpress',
        'woocommerce',
    ],
);
```

The generated lists will contain symbols from **only** the specified packages.

### Exclude default packages

If you want to start with the default set and remove specific packages, use the `exclude` argument.

For example, if your project never runs under WP-CLI and does not bundle Plugin Update Checker:

```php
$wpExcludes = $getExcludes(
    exclude: [
        'wp-cli',
        'plugin-update-checker',
    ],
);
```

The generated lists will include every supported package **except** those specified.

---

## How symbols are generated

Each supported package is processed using `nikic/php-parser`.

During generation the build pipeline:

1. Parses the upstream source or stub files into an abstract syntax tree (AST).
2. Collects all top-level classes, interfaces, traits, enums, functions and constants, including declarations guarded by `class_exists()`, `function_exists()` and similar checks.
3. Resolves all names to their fully qualified form.
4. Merges symbols across every source file.
5. Removes duplicates.
6. Sorts the final lists and writes them to `symbols/**/*.json`.

The implementation lives in `build/`, with a dedicated test suite in `build/tests`.

---

## Automatic updates

`bin/generate.sh` downloads the latest allowed versions of every supported package and regenerates only those whose upstream versions have changed. The resolved versions are recorded in `symbols/versions.json`.

`bin/release.sh` runs the generator and automatically commits, tags and pushes the repository whenever generated files change.

A daily GitHub Actions workflow simply executes `bin/release.sh`. All automation lives in the scripts, making the same release process available both locally and in CI.

---

## Versioning

This project follows semantic versioning (`vX.Y.Z`).

* Patch releases are created automatically whenever generated symbols change.
* Minor and major releases are created manually for breaking or structural changes, such as changing the public API or removing a supported package.
* The upstream package versions used to generate each release are recorded in `symbols/versions.json`.

---

## Local development

### Requirements

* PHP 8.1 or newer
* Composer

Generate symbols:

```bash
bin/generate.sh                    # Regenerate packages whose upstream versions changed
bin/generate.sh --force            # Regenerate all packages
bin/generate.sh --only=wordpress   # Regenerate a single package
```

### Using Docker

A `docker-compose.yml` file is included, allowing the entire build pipeline to run using Docker alone.

```bash
docker compose run --rm build
docker compose run --rm build bin/generate.sh --force
docker compose run --rm build bin/generate.sh --only=acf
docker compose run --rm --entrypoint sh build
```

The container includes PHP, Composer and Git, with the repository mounted into the container, so the output matches running the scripts locally.

---

## Testing

```bash
composer --working-dir=build install
build/vendor/bin/phpunit --configuration build/phpunit.xml.dist
```

Or using Docker:

```bash
docker compose run --rm --entrypoint sh build -c "composer --working-dir=build install && build/vendor/bin/phpunit --configuration build/phpunit.xml.dist"
```

Tests use local fixtures only and do not require network access.

---

## Contributing

Bug reports, feature requests and pull requests are welcome.

Please run the test suite before submitting changes.

---

## License

Released under the MIT License. See [LICENSE](LICENSE) for details.
