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
     * @var array<int, string> Normalized list of variable names without the leading $.
     */
    private array $restricted = [];

    /**
     * @var array<int, string> Legacy configuration values (with $ prefix).
     */
    private array $legacyValues = [];

    /**
     * @var string Either 'exact' or 'pattern' for legacy configurations.
     */
    private string $legacyMatcher = 'exact';

    /**
     * @var string Violation message template containing the {value} placeholder.
     */
    private string $message = "Use of restricted variable '{value}' is not allowed.";

    /**
     * RestrictVariableRule constructor.
     *
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $vars = $config['variables'] ?? $config['variable'] ?? [];
        if (is_string($vars)) {
            $vars = [$vars];
        }
        if (is_array($vars)) {
            $filteredVars = array_filter($vars, fn ($v) => is_string($v) && $v !== '');
            /** @var array<int, string> $mappedVars */
            $mappedVars = array_map(
                fn (string $v): string => $this->normalizeVariableName($v),
                array_values($filteredVars)
            );
            $this->restricted = array_values(array_unique($mappedVars));
        }

        $values = $config['values'] ?? [];
        if (is_string($values)) {
            $values = [$values];
        }
        if (is_array($values)) {
            $filteredValues = array_filter($values, fn ($v) => is_string($v) && $v !== '');
            /** @var array<int, string> $filteredValues */
            $filteredValues = array_values($filteredValues);
            $this->legacyValues = $filteredValues;
        }

        $matcher = $config['matcher'] ?? 'exact';
        if (is_string($matcher) && in_array($matcher, ['exact', 'pattern'], true)) {
            $this->legacyMatcher = $matcher;
        }

        if (isset($config['message']) && is_string($config['message']) && $config['message'] !== '') {
            $message = $config['message'];
            $this->message = $message;
        }
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
        if (!$node instanceof Variable) {
            return false;
        }
        if (!is_string($node->name)) {
            return false;
        }
        $name = $this->normalizeVariableName($node->name);
        if ($name === '') {
            return false;
        }
        if (in_array($name, $this->restricted, true)) {
            return true;
        }
        if (empty($this->legacyValues)) {
            return false;
        }
        $withDollar = '$' . $name;
        if ($this->legacyMatcher === 'exact') {
            return in_array($withDollar, $this->legacyValues, true);
        }
        foreach ($this->legacyValues as $pattern) {
            $result = @preg_match($pattern, $withDollar);
            if ($result === 1) {
                return true;
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
        $variableName = 'unknown';
        if ($node instanceof Variable) {
            if (is_string($node->name)) {
                $variableName = '$' . $node->name;
            } else {
                $variableName = '${expr}';
            }
        }

        return str_replace('{value}', $variableName, $this->message);
    }

    /**
     * Normalizes a variable name by removing any leading $ and trimming whitespace.
     */
    private function normalizeVariableName(string $name): string
    {
        $normalized = ltrim(trim($name), '$');
        return $normalized;
    }
}
