<?php

namespace Lynter\Rules;

use Lynter\RuleInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;

/**
 * Class RestrictClassRule
 *
 * A rule that restricts the use of certain classes in the code.
 */
class RestrictClassRule implements RuleInterface
{
    /**
     * @var array<string> The list of classes to restrict.
     */
    private array $restrictedClasses;

    /**
     * @var string The message template for reporting violations.
     */
    private string $message;

    /**
     * RestrictClassRule constructor.
     *
     * @param array<string, mixed> $config The configuration for this rule.
     */
    public function __construct(array $config)
    {
        $this->restrictedClasses = $config['classes'] ?? [];
        $this->message = $config['message'] ?? "Class '{name}' is restricted.";
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
        if ($node instanceof New_ || $node instanceof StaticCall) {
            $className = $this->resolveClassName($node);
            return $className !== null && in_array($className, $this->restrictedClasses, true);
        }

        return false;
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
        $className = $this->resolveClassName($node);

        if ($className === null) {
            return 'Unknown class name violated the rule.';
        }

        return str_replace('{name}', $className, $this->message);
    }

    /**
     * Resolves the class name from the given node.
     *
     * @param Node $node The AST node to resolve the class name from.
     *
     * @return string|null The class name if it can be resolved, null otherwise.
     */
    private function resolveClassName(Node $node): ?string
    {
        if ($node instanceof New_ || $node instanceof StaticCall) {
            if ($node->class instanceof Name) {
                return $node->class->toString();
            }
        }

        return null;
    }
}
