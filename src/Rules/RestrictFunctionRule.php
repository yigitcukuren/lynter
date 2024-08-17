<?php

namespace Lynter\Rules;

use Lynter\RuleInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\Eval_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;

/**
 * Class RestrictFunctionRule
 *
 * A rule that restricts the use of certain functions in the code.
 */
class RestrictFunctionRule implements RuleInterface
{
    /**
     * @var array<string> The list of functions to restrict.
     */
    private array $restrictedFunctions;

    /**
     * @var string The message template for reporting violations.
     */
    private string $message;

    /**
     * RestrictFunctionRule constructor.
     *
     * @param array<string, mixed> $config The configuration for this rule.
     */
    public function __construct(array $config)
    {
        $this->restrictedFunctions = $config['functions'] ?? [];
        $this->message = $config['message'] ?? "Function '{name}' is restricted.";
    }

    /**
     * Determine if this rule applies to the given AST node.
     *
     * @param Node $node The AST node to check.
     *
     * @return bool True if the rule applies, false otherwise.
     */
    public function appliesTo(Node $node): bool
    {
        return ($node instanceof FuncCall && $node->name instanceof Name && in_array($node->name->toString(), $this->restrictedFunctions, true))
            || ($node instanceof Eval_ && in_array('eval', $this->restrictedFunctions, true));
    }

    /**
     * Get the error message for a violation of this rule.
     *
     * @param Node $node The AST node that violated the rule.
     *
     * @return string The formatted error message.
     */
    public function getErrorMessage(Node $node): string
    {
        if ($node instanceof FuncCall && $node->name instanceof Name) {
            $functionName = $node->name->toString();
        } elseif ($node instanceof Eval_) {
            $functionName = 'eval';  // Hard-code 'eval' for Eval_ nodes
        } else {
            $functionName = 'unknown';
        }

        return str_replace('{name}', $functionName, $this->message);
    }
}
