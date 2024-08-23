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
     * @var array<string> The list of variable names or patterns to restrict.
     */
    private array $values;

    /**
     * @var string The matching strategy ('exact' or 'pattern').
     */
    private string $matcher;

    /**
     * @var string The message to display when a restricted variable is used.
     */
    private string $message;

    /**
     * RestrictVariableRule constructor.
     *
     * @param array<string, mixed> $config The configuration for the rule.
     */
    public function __construct(array $config)
    {
        $this->values = $config['values'] ?? [];
        $this->matcher = $config['matcher'] ?? 'exact';
        $this->message = $config['message'] ?? "Variable '{value}' is restricted.";
    }

    /**
     * Determines if this rule applies to the given AST node.
     *
     * @param Node $node The AST node to check.
     *
     * @return bool True if the rule applies, false otherwise.
     */
    public function appliesTo(Node $node): bool
    {
        if ($node instanceof Variable) {
            $variableName = '$' . $node->name;

            if ($this->matcher === 'exact') {
                return in_array($variableName, $this->values, true);
            } elseif ($this->matcher === 'pattern') {
                foreach ($this->values as $pattern) {
                    if (preg_match($pattern, $variableName)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Generates an error message when a restricted variable is used.
     *
     * @param Node $node The AST node that triggered the rule.
     *
     * @return string The formatted error message.
     */
    public function getErrorMessage(Node $node): string
    {
        if ($node instanceof Variable) {
            $variableName = '$' . $node->name;
        } else {
            $variableName = 'unknown';
        }

        return str_replace('{value}', $variableName, $this->message);
    }
}
