<?php

namespace Lynter;

use PhpParser\Node;

/**
 * Interface RuleInterface
 *
 * Defines the contract for all rules to follow.
 */
interface RuleInterface
{
    /**
     * Determine if this rule applies to the given AST node.
     *
     * @param Node $node The AST node to check.
     *
     * @return bool True if the rule applies, false otherwise.
     */
    public function appliesTo(Node $node): bool;

    /**
     * Get the error message for a violation of this rule.
     *
     * @param Node $node The AST node that violated the rule.
     *
     * @return string The formatted error message.
     */
    public function getErrorMessage(Node $node): string;
}
