<?php

declare(strict_types=1);

namespace WPTechnix\PhpScoperWordPressExcludesBuild\Tests;

use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WPTechnix\PhpScoperWordPressExcludesBuild\Generator;

final class GeneratorTest extends TestCase
{
    private Generator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $this->generator = new Generator($parser);
    }

    public function test_global_namespace_declarations_are_collected(): void
    {
        $result = $this->generator->generate([$this->fixture('global-namespace.php')]);

        self::assertSame(
            ['BarTrait', 'FooEnum', 'FooInterface', 'FooTrait', 'WP_Abstract_Thing', 'WP_Error'],
            $result['classes']
        );
        self::assertSame(['bar', 'foo'], $result['functions']);
        self::assertSame(['DB_NAME', 'DB_PASSWORD', 'DB_USER'], $result['constants']);
    }

    public function test_multi_namespace_blocks_and_define_are_resolved_correctly(): void
    {
        $result = $this->generator->generate([$this->fixture('custom-namespace.php')]);

        self::assertSame(
            [
                'WP_CLI\\Autoloader',
                'WP_CLI\\Bootstrap\\AutoloaderStep',
                'WP_CLI\\Bootstrap\\BarTrait',
                'WP_CLI\\Bootstrap\\FooTrait',
            ],
            $result['classes']
        );
        self::assertSame(
            ['WP_CLI\\Utils\\get_upgrader', 'WP_CLI\\wp_not_installed'],
            $result['functions']
        );
        // define() constants are never namespace-qualified, regardless of
        // the enclosing namespace block.
        self::assertSame(['FOO'], $result['constants']);
    }

    public function test_conditional_declarations_inside_guards_are_collected(): void
    {
        $result = $this->generator->generate([$this->fixture('if-guard.php')]);

        self::assertSame(
            ['Guarded_Class', 'Switch_Guarded_Class', 'Try_Guarded_Class'],
            $result['classes']
        );
        self::assertSame(['guarded_function'], $result['functions']);
        self::assertSame(['GUARDED_CONST'], $result['constants']);
    }

    public function test_declarations_inside_closures_are_still_collected(): void
    {
        $result = $this->generator->generate([$this->fixture('closures.php')]);

        self::assertSame(['Class_In_Closure'], $result['classes']);
        self::assertSame(['function_in_closure'], $result['functions']);
    }

    public function test_dynamic_define_name_is_skipped_without_throwing(): void
    {
        $result = $this->generator->generate([$this->fixture('dynamic-define.php')]);

        self::assertSame(['STATIC_CONST'], $result['constants']);
    }

    public function test_directory_input_is_walked_recursively(): void
    {
        $result = $this->generator->generate([$this->fixture('directory-input')]);

        self::assertSame(['Dir_Class_A', 'Dir_Class_B'], $result['classes']);
        self::assertSame(['dir_function_a'], $result['functions']);
        self::assertSame(['DIR_CONST_B'], $result['constants']);
    }

    public function test_multiple_input_files_are_merged_and_deduped(): void
    {
        $result = $this->generator->generate([
            $this->fixture('merge-a.php'),
            $this->fixture('merge-b.php'),
        ]);

        self::assertSame(['Only_In_A', 'Only_In_B', 'Shared_Class'], $result['classes']);
        self::assertSame(['shared_function'], $result['functions']);
    }

    public function test_missing_input_path_throws(): void
    {
        $this->expectException(RuntimeException::class);

        $this->generator->generate([$this->fixture('does-not-exist.php')]);
    }

    private function fixture(string $name): string
    {
        return __DIR__ . '/fixtures/' . $name;
    }
}
