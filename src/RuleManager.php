<?php

namespace Lynter;

use PhpParser\Node;

/**
 * Class RuleManager
 *
 * Manages the registration and application of rules based on the configuration.
 */
class RuleManager
{
    /**
     * @var array<RuleInterface> The list of registered rules.
     */
    private array $rules = [];

    /**
     * RuleManager constructor.
     *
     * @param array<string, array<string, mixed>> $config The configuration array from the YAML file.
     */
    public function __construct(array $config)
    {
        $this->loadRules($config['rules']);
    }

    /**
     * Load and register rules based on the configuration.
     *
     * @param array<string, array<string, mixed>> $rulesConfig The configuration array for rules.
     *
     * @return void
     */
    private function loadRules(array $rulesConfig): void
    {
        foreach ($rulesConfig as $ruleConfig) {
            $className = 'Lynter\\Rules\\' . ucfirst($ruleConfig['rule']) . 'Rule';
            if (class_exists($className)) {
                $this->rules[] = new $className($ruleConfig);
            }
        }
    }

    /**
     * Apply all registered rules to a given node.
     *
     * @param Node $node The AST node to check against the rules.
     *
     * @return array<int, string> An array of issues found.
     */
    public function applyRules(Node $node): array
    {
        $issues = [];

        foreach ($this->rules as $rule) {
            if ($rule->appliesTo($node)) {
                $issues[] = $rule->getErrorMessage($node);
            }
        }

        return $issues;
    }
}
