<?php

declare(strict_types=1);

namespace WPTechnix\PhpScoperWordPressExcludesBuild;

use PhpParser\Error as PhpParserError;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use WPTechnix\PhpScoperWordPressExcludesBuild\NodeVisitor\Categorize;
use WPTechnix\PhpScoperWordPressExcludesBuild\NodeVisitor\DeclarationCollector;

/**
 * Walks a set of PHP files/directories and produces a merged, deduped,
 * alphabetically sorted list of classes/functions/constants declared in
 * them - the raw material for a package's symbols/*.json exclude lists.
 */
final class Generator
{
    public function __construct(private readonly Parser $parser)
    {
    }

    /**
     * @param string[] $inputs Absolute file or directory paths.
     * @return array{classes: string[], functions: string[], constants: string[]}
     */
    public function generate(array $inputs): array
    {
        $classes = [];
        $functions = [];
        $constants = [];

        foreach ($this->resolveFiles($inputs) as $file) {
            $result = $this->parseOneFile($file);
            $classes = array_merge($classes, $result['classes']);
            $functions = array_merge($functions, $result['functions']);
            $constants = array_merge($constants, $result['constants']);
        }

        return [
            'classes' => $this->dedupeSort($classes),
            'functions' => $this->dedupeSort($functions),
            'constants' => $this->dedupeSort($constants),
        ];
    }

    /**
     * @return array{classes: string[], functions: string[], constants: string[]}
     */
    private function parseOneFile(string $file): array
    {
        $code = file_get_contents($file);

        if ($code === false) {
            throw new RuntimeException("Unable to read file: {$file}");
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new DeclarationCollector());
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($categorize = new Categorize());

        try {
            $ast = $this->parser->parse($code);
        } catch (PhpParserError $error) {
            throw new RuntimeException("Failed to parse {$file}: {$error->getMessage()}", 0, $error);
        }

        if ($ast === null) {
            throw new RuntimeException("Failed to parse {$file}: parser returned no AST.");
        }

        $traverser->traverse($ast);

        return [
            'classes' => $categorize->classes(),
            'functions' => $categorize->functions(),
            'constants' => $categorize->constants(),
        ];
    }

    /**
     * @param string[] $inputs
     * @return string[]
     */
    private function resolveFiles(array $inputs): array
    {
        $files = [];

        foreach ($inputs as $input) {
            if (is_dir($input)) {
                $finder = (new Finder())->files()->in($input)->name('*.php')->sortByName(true);

                foreach ($finder as $fileInfo) {
                    $files[] = $fileInfo->getRealPath();
                }

                continue;
            }

            if (is_file($input)) {
                $files[] = $input;

                continue;
            }

            throw new RuntimeException("Input path does not exist: {$input}");
        }

        return $files;
    }

    /**
     * @param string[] $items
     * @return string[]
     */
    private function dedupeSort(array $items): array
    {
        $items = array_values(array_unique($items));
        sort($items, SORT_NATURAL | SORT_FLAG_CASE);

        return $items;
    }
}
