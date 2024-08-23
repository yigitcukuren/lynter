<?php

namespace Lynter\Rules;

use Lynter\RuleInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;

/**
 * Class RestrictClassRule
 *
 * A rule that restricts the instantiation of certain classes in the code.
 */
class RestrictClassRule implements RuleInterface
{
    /**
     * @var array<string> List of class names or patterns to restrict.
     */
    private array $values;

    /**
     * @var string The matching strategy ('exact' or 'pattern').
     */
    private string $matcher;

    /**
     * @var string The message to display when a restricted class is used.
     */
    private string $message;

    /**
     * RestrictClassRule constructor.
     *
     * @param array<string, mixed> $config The configuration for the rule.
     */
    public function __construct(array $config)
    {
        $this->values = $config['values'] ?? [];
        $this->matcher = $config['matcher'] ?? 'exact';
        $this->message = $config['message'] ?? "Class '{value}' is restricted.";
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
        if ($node instanceof New_ && $node->class instanceof Name) {
            $className = $node->class->toString();

            if ($this->matcher === 'exact') {
                return in_array($className, $this->values, true);
            } elseif ($this->matcher === 'pattern') {
                foreach ($this->values as $pattern) {
                    if (preg_match($pattern, $className)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Generates an error message when a restricted class is used.
     *
     * @param Node $node The AST node that triggered the rule.
     *
     * @return string The formatted error message.
     */
    public function getErrorMessage(Node $node): string
    {
        if ($node instanceof New_ && $node->class instanceof Name) {
            $className = $node->class->toString();
        } else {
            $className = 'unknown';
        }

        return str_replace('{value}', $className, $this->message);
    }
}
