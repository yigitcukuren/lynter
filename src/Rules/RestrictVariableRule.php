<?php

namespace Lynter\Rules;

use Lynter\RuleInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;

/**
 * Class RestrictVariableRule
 *
 * A rule that restricts the use of certain variables in the code.
 */
class RestrictVariableRule implements RuleInterface
{
    /**
     * @var array<string> The list of variables to restrict.
     */
    private array $restrictedVariables;

    /**
     * @var string The message template for reporting violations.
     */
    private string $message;

    /**
     * RestrictVariableRule constructor.
     *
     * @param array<string, mixed> $config The configuration for this rule.
     */
    public function __construct(array $config)
    {
        $this->restrictedVariables = $config['variables'] ?? [];
        $this->message = $config['message'] ?? "Variable '{name}' is restricted.";
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
        return $node instanceof Variable && in_array('$' . $this->resolveVariableName($node), $this->restrictedVariables, true);
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
        $variableName = $this->resolveVariableName($node);

        if ($variableName === null) {
            return 'Unknown variable name violated the rule.';
        }

        return str_replace('{name}', '$' . $variableName, $this->message);
    }

    /**
     * Resolves the name of the variable from the node.
     *
     * @param Node $node The AST node to resolve the name from.
     *
     * @return string|null The name of the variable, or null if it cannot be resolved.
     */
    private function resolveVariableName(Node $node): ?string
    {
        if ($node instanceof Variable) {
            // If the variable name is a simple string, return it directly
            if (is_string($node->name)) {
                return $node->name;
            } elseif ($node->name instanceof Node\Expr) {
                // Handle complex cases where the variable name is an expression
                return $this->resolveComplexVariableName($node->name);
            }
        }

        // Return null if the variable name could not be resolved
        return null;
    }

    /**
     * Resolves a complex variable name that is an expression.
     *
     * @param Node\Expr $expr The expression representing the variable name.
     *
     * @return string|null The resolved variable name, or null if it cannot be resolved.
     */
    private function resolveComplexVariableName(Node\Expr $expr): ?string
    {
        // Add logic here to resolve complex variable names, for example:
        if ($expr instanceof Node\Scalar\String_) {
            return $expr->value;
        }

        // Add additional cases as necessary for your application

        return null;
    }
}
