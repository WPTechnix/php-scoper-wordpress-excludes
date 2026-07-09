#!/usr/bin/env php
<?php

declare(strict_types=1);

use PhpParser\ParserFactory;
use WPTechnix\PhpScoperWordPressExcludesBuild\Generator;
use WPTechnix\PhpScoperWordPressExcludesBuild\VersionsFile;

require __DIR__ . '/vendor/autoload.php';

/**
 * @param string[] $data
 */
function writeJsonFile(string $path, array $data): void
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    file_put_contents($path, $json . "\n");
}

$buildDir = __DIR__;
$vendorDir = $buildDir . '/vendor';

$force = false;
$only = null;
$repoRoot = dirname($buildDir);

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--force') {
        $force = true;
    } elseif (str_starts_with($arg, '--only=')) {
        $only = substr($arg, strlen('--only='));
    } elseif (str_starts_with($arg, '--repo-root=')) {
        $repoRoot = substr($arg, strlen('--repo-root='));
    } else {
        fwrite(STDERR, "Unknown option: {$arg}\n");
        exit(1);
    }
}

$repoRoot = rtrim($repoRoot, '/\\');
$symbolsDir = $repoRoot . '/symbols';
$versionsPath = $symbolsDir . '/versions.json';

/** @var array<string, array{require: array<string, string>, inputs: string[]}> $packages */
$packages = require $buildDir . '/config/packages.php';

if ($only !== null && ! isset($packages[$only])) {
    fwrite(STDERR, "Unknown package: {$only}\n");
    exit(1);
}

$parser = (new ParserFactory())->createForNewestSupportedVersion();
$generator = new Generator($parser);
$versions = VersionsFile::load($versionsPath);

$hadFailure = false;

foreach ($packages as $name => $definition) {
    if ($only !== null && $only !== $name) {
        continue;
    }

    echo "==> {$name}\n";

    $resolved = [];

    foreach (array_keys($definition['require']) as $composerPackage) {
        $resolved[$composerPackage] = \Composer\InstalledVersions::isInstalled($composerPackage)
            ? (\Composer\InstalledVersions::getPrettyVersion($composerPackage) ?? 'unknown')
            : 'unknown';
    }

    ksort($resolved);

    $packageDir = "{$symbolsDir}/{$name}";
    $outputsExist = is_file("{$packageDir}/classes.json")
        && is_file("{$packageDir}/functions.json")
        && is_file("{$packageDir}/constants.json");

    if (! $force && $outputsExist && $versions->isUnchanged($name, $resolved)) {
        $versionList = [];
        foreach ($resolved as $pkg => $ver) {
            $versionList[] = "{$pkg}:{$ver}";
        }
        echo '    unchanged (' . implode(', ', $versionList) . ") - skipping\n";
        continue;
    }

    $inputs = array_map(
        static fn (string $path): string => $vendorDir . '/' . $path,
        $definition['inputs']
    );

    try {
        $result = $generator->generate($inputs);
    } catch (\Throwable $e) {
        fwrite(STDERR, "    FAILED: {$e->getMessage()}\n");
        $hadFailure = true;
        continue;
    }

    if (! is_dir($packageDir) && ! mkdir($packageDir, 0777, true) && ! is_dir($packageDir)) {
        fwrite(STDERR, "    FAILED: unable to create directory {$packageDir}\n");
        $hadFailure = true;
        continue;
    }

    writeJsonFile("{$packageDir}/classes.json", $result['classes']);
    writeJsonFile("{$packageDir}/functions.json", $result['functions']);
    writeJsonFile("{$packageDir}/constants.json", $result['constants']);

    $versions->record($name, $resolved, gmdate('c'));

    echo '    classes=' . count($result['classes'])
        . ' functions=' . count($result['functions'])
        . ' constants=' . count($result['constants']) . "\n";
}

$versions->save($versionsPath);

if ($hadFailure) {
    fwrite(STDERR, "\nOne or more packages failed to generate. See above.\n");
    exit(1);
}

exit(0);
