<?php

declare(strict_types=1);

namespace WPTechnix\PhpScoperWordPressExcludesBuild\NodeVisitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Collects fully-qualified symbol names from a pruned, name-resolved AST
 * (must run after NodeVisitor\NameResolver so that `namespacedName` is set).
 *
 * Classes, interfaces, traits and enums are merged into a single "classes"
 * bucket, matching PHP-Scoper's own configuration schema: `exclude-classes`
 * covers all class-like symbols, there is no separate exclude-interfaces or
 * exclude-traits option.
 */
final class Categorize extends NodeVisitorAbstract
{
    /** @var string[] */
    private array $classes = [];

    /** @var string[] */
    private array $functions = [];

    /** @var string[] */
    private array $constants = [];

    public function beforeTraverse(array $nodes): ?array
    {
        $this->classes = [];
        $this->functions = [];
        $this->constants = [];

        return null;
    }

    public function leaveNode(Node $node): ?int
    {
        if (
            $node instanceof Node\Stmt\Class_
            || $node instanceof Node\Stmt\Interface_
            || $node instanceof Node\Stmt\Trait_
            || $node instanceof Node\Stmt\Enum_
        ) {
            $this->classes[] = $this->namespacedName($node);

            return null;
        }

        if ($node instanceof Node\Stmt\Function_) {
            $this->functions[] = $this->namespacedName($node);

            return null;
        }

        if ($node instanceof Node\Stmt\Const_) {
            foreach ($node->consts as $const) {
                $this->constants[] = $this->namespacedName($const);
            }

            return null;
        }

        if ($node instanceof Node\Stmt\Expression && $node->expr instanceof Node\Expr\FuncCall) {
            $this->collectDefineConstant($node->expr);
        }

        return null;
    }

    /** @return string[] */
    public function classes(): array
    {
        return $this->classes;
    }

    /** @return string[] */
    public function functions(): array
    {
        return $this->functions;
    }

    /** @return string[] */
    public function constants(): array
    {
        return $this->constants;
    }

    /**
     * @param Node\Stmt\ClassLike|Node\Stmt\Function_|Node\Const_ $node
     */
    private function namespacedName(Node $node): string
    {
        $namespacedName = $node->namespacedName ?? null;

        if ($namespacedName instanceof Node\Name) {
            return $namespacedName->toString();
        }

        // Should not happen once NameResolver has run first; fall back to
        // the bare declared name rather than losing the symbol entirely.
        return (string) $node->name;
    }

    private function collectDefineConstant(Node\Expr\FuncCall $call): void
    {
        if (! $call->name instanceof Node\Name || strtolower($call->name->toString()) !== 'define') {
            return;
        }

        $firstArg = $call->args[0] ?? null;

        if (! $firstArg instanceof Node\Arg) {
            return;
        }

        $nameValue = $firstArg->value;

        if (! $nameValue instanceof Node\Scalar\String_) {
            // Dynamic constant name (variable, concatenation, constant
            // expression, ...) cannot be resolved statically. Skip it rather
            // than aborting the whole generation run - unlike curated stub
            // files, real source (action-scheduler, plugin-update-checker)
            // occasionally defines constants dynamically.
            return;
        }

        $this->constants[] = $nameValue->value;
    }
}
