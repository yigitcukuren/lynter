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
     * @var array<string> The list of function names or patterns to restrict.
     */
    private array $values;

    /**
     * @var string The matching strategy ('exact' or 'pattern').
     */
    private string $matcher;

    /**
     * @var string The message to display when a restricted function is used.
     */
    private string $message;

    /**
     * RestrictFunctionRule constructor.
     *
     * @param array<string, mixed> $config The configuration for the rule.
     */
    public function __construct(array $config)
    {
        $this->values = $config['values'] ?? [];
        $this->matcher = $config['matcher'] ?? 'exact';
        $this->message = $config['message'] ?? "Function '{value}' is restricted.";
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
        if ($node instanceof FuncCall && $node->name instanceof Name) {
            $functionName = $node->name->toString();

            if ($this->matcher === 'exact') {
                return in_array($functionName, $this->values, true);
            } elseif ($this->matcher === 'pattern') {
                foreach ($this->values as $pattern) {
                    if (preg_match($pattern, $functionName)) {
                        return true;
                    }
                }
            }
        } elseif ($node instanceof Eval_) {
            return in_array('eval', $this->values, true);
        }

        return false;
    }

    /**
     * Generates an error message when a restricted function is used.
     *
     * @param Node $node The AST node that triggered the rule.
     *
     * @return string The formatted error message.
     */
    public function getErrorMessage(Node $node): string
    {
        if ($node instanceof FuncCall && $node->name instanceof Name) {
            $functionName = $node->name->toString();
        } elseif ($node instanceof Eval_) {
            $functionName = 'eval';
        } else {
            $functionName = 'unknown';
        }

        return str_replace('{value}', $functionName, $this->message);
    }
}
