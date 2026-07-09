<?php

declare(strict_types=1);

namespace WPTechnix\PhpScoperWordPressExcludesBuild\NodeVisitor;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;

/**
 * Prunes the AST down to top-level symbol declarations (classes, interfaces,
 * traits, enums, functions, top-level constants) and define()/\define() calls.
 *
 * Everything else (if/switch/try/foreach/namespace/closures/...) is left
 * transparent so declarations nested inside runtime guards such as
 * `if (!class_exists('Foo')) { class Foo {} }` - a very common pattern in
 * real WordPress-ecosystem source, as opposed to curated stub-only files -
 * are still found. Only the body of a recognized declaration itself is
 * skipped, since nothing relevant to an exclude list can live inside a
 * class/function body.
 */
final class DeclarationCollector extends NodeVisitorAbstract
{
    public function enterNode(Node $node): ?int
    {
        return $this->isDeclaration($node) ? NodeVisitor::DONT_TRAVERSE_CHILDREN : null;
    }

    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt && ! $this->isDeclaration($node)) {
            return NodeVisitor::REMOVE_NODE;
        }

        return null;
    }

    private function isDeclaration(Node $node): bool
    {
        return $node instanceof Node\Stmt\Class_
            || $node instanceof Node\Stmt\Interface_
            || $node instanceof Node\Stmt\Trait_
            || $node instanceof Node\Stmt\Enum_
            || $node instanceof Node\Stmt\Function_
            || $node instanceof Node\Stmt\Const_
            || $this->isDefineCall($node);
    }

    private function isDefineCall(Node $node): bool
    {
        if (! $node instanceof Node\Stmt\Expression) {
            return false;
        }

        $expr = $node->expr;

        if (! $expr instanceof Node\Expr\FuncCall) {
            return false;
        }

        if (! $expr->name instanceof Node\Name) {
            return false;
        }

        // Name::toString() yields the same string for `define(...)` and the
        // fully-qualified `\define(...)` form, so both are matched here.
        return strtolower($expr->name->toString()) === 'define';
    }
}
