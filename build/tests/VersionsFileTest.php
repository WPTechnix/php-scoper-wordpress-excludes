<?php

declare(strict_types=1);

namespace WPTechnix\PhpScoperWordPressExcludesBuild\Tests;

use PHPUnit\Framework\TestCase;
use WPTechnix\PhpScoperWordPressExcludesBuild\VersionsFile;

final class VersionsFileTest extends TestCase
{
    private string $tmpFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpFile = sys_get_temp_dir() . '/wptechnix-versions-test-' . bin2hex(random_bytes(8)) . '.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->tmpFile)) {
            unlink($this->tmpFile);
        }

        parent::tearDown();
    }

    public function test_missing_file_yields_no_previous_versions(): void
    {
        $versions = VersionsFile::load($this->tmpFile);

        self::assertFalse($versions->isUnchanged('wordpress', ['php-stubs/wordpress-stubs' => '6.5.0']));
    }

    public function test_matching_versions_are_reported_unchanged_regardless_of_key_order(): void
    {
        $versions = VersionsFile::load($this->tmpFile);
        $versions->record(
            'wordpress',
            [
                'php-stubs/wordpress-globals' => '0.5.0',
                'php-stubs/wordpress-stubs' => '6.5.0',
            ],
            '2026-07-09T00:00:00+00:00'
        );
        $versions->save($this->tmpFile);

        $reloaded = VersionsFile::load($this->tmpFile);

        self::assertTrue($reloaded->isUnchanged('wordpress', [
            'php-stubs/wordpress-stubs' => '6.5.0',
            'php-stubs/wordpress-globals' => '0.5.0',
        ]));
    }

    public function test_changed_version_is_reported_as_changed(): void
    {
        $versions = VersionsFile::load($this->tmpFile);
        $versions->record('wordpress', ['php-stubs/wordpress-stubs' => '6.5.0'], '2026-07-09T00:00:00+00:00');

        self::assertFalse($versions->isUnchanged('wordpress', ['php-stubs/wordpress-stubs' => '6.5.1']));
    }

    public function test_unrelated_package_does_not_affect_another_packages_record(): void
    {
        $versions = VersionsFile::load($this->tmpFile);
        $versions->record('wordpress', ['php-stubs/wordpress-stubs' => '6.5.0'], '2026-07-09T00:00:00+00:00');

        self::assertFalse($versions->isUnchanged('woocommerce', ['php-stubs/woocommerce-stubs' => '9.0.0']));
    }
}
