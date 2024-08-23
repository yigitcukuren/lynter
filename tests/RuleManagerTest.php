<?php

namespace Tests;

use Lynter\RuleManager;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PHPUnit\Framework\TestCase;

/**
 * Class RuleManagerTest
 *
 * Tests the functionality of the RuleManager class.
 */
class RuleManagerTest extends TestCase
{
    /**
     * Tests applying rules with an exact matcher for functions.
     *
     * @return void
     */
    public function testApplyRulesWithExactMatcher(): void
    {
        $config = [
            'rules' => [
                [
                    'name' => 'restrict-functions',
                    'rule' => 'restrictFunction',
                    'matcher' => 'exact',
                    'values' => ['shell_exec'],
                    'message' => "This function '{value}' is not allowed.",
                ],
            ],
        ];

        $ruleManager = new RuleManager($config);
        $node = new FuncCall(new Name('shell_exec'));
        $issues = $ruleManager->applyRules($node);

        $this->assertCount(1, $issues);
        $this->assertSame("This function 'shell_exec' is not allowed.", $issues[0]);
    }

    /**
     * Tests applying rules with a pattern matcher for functions.
     *
     * @return void
     */
    public function testApplyRulesWithPatternMatcher(): void
    {
        $config = [
            'rules' => [
                [
                    'name' => 'restrict-functions-regex',
                    'rule' => 'restrictFunction',
                    'matcher' => 'pattern',
                    'values' => ['/^debug_/'],
                    'message' => "This function matching '{value}' is not allowed.",
                ],
            ],
        ];

        $ruleManager = new RuleManager($config);
        $node = new FuncCall(new Name('debug_log'));
        $issues = $ruleManager->applyRules($node);

        $this->assertCount(1, $issues);
        $this->assertSame("This function matching 'debug_log' is not allowed.", $issues[0]);
    }

    /**
     * Tests applying rules with an exact matcher for variables.
     *
     * @return void
     */
    public function testApplyRulesWithExactVariableMatcher(): void
    {
        $config = [
            'rules' => [
                [
                    'name' => 'restrict-variables',
                    'rule' => 'restrictVariable',
                    'matcher' => 'exact',
                    'values' => ['$_GET'],
                    'message' => "This variable '{value}' is restricted.",
                ],
            ],
        ];

        $ruleManager = new RuleManager($config);
        $node = new Variable('_GET');
        $issues = $ruleManager->applyRules($node);

        $this->assertCount(1, $issues);
        $this->assertSame("This variable '\$_GET' is restricted.", $issues[0]);
    }

    /**
     * Tests applying rules with a pattern matcher for variables.
     *
     * @return void
     */
    public function testApplyRulesWithPatternVariableMatcher(): void
    {
        $config = [
            'rules' => [
                [
                    'name' => 'restrict-variables-regex',
                    'rule' => 'restrictVariable',
                    'matcher' => 'pattern',
                    'values' => ['/^\$temp/'],
                    'message' => "This variable matching '{value}' is restricted.",
                ],
            ],
        ];

        $ruleManager = new RuleManager($config);
        $node = new Variable('tempVar');
        $issues = $ruleManager->applyRules($node);

        $this->assertCount(1, $issues);
        $this->assertSame("This variable matching '\$tempVar' is restricted.", $issues[0]);
    }

    /**
     * Tests applying rules with an exact matcher for classes.
     *
     * @return void
     */
    public function testApplyRulesWithExactClassMatcher(): void
    {
        $config = [
            'rules' => [
                [
                    'name' => 'restrict-classes',
                    'rule' => 'restrictClass',
                    'matcher' => 'exact',
                    'values' => ['MyRestrictedClass'],
                    'message' => "Instantiation of '{value}' is not allowed.",
                ],
            ],
        ];

        $ruleManager = new RuleManager($config);
        $node = new New_(new Name('MyRestrictedClass'));
        $issues = $ruleManager->applyRules($node);

        $this->assertCount(1, $issues);
        $this->assertSame("Instantiation of 'MyRestrictedClass' is not allowed.", $issues[0]);
    }

    /**
     * Tests applying rules with a pattern matcher for classes.
     *
     * @return void
     */
    public function testApplyRulesWithPatternClassMatcher(): void
    {
        $config = [
            'rules' => [
                [
                    'name' => 'restrict-classes-regex',
                    'rule' => 'restrictClass',
                    'matcher' => 'pattern',
                    'values' => ['/^Legacy/'],
                    'message' => "Instantiation of class matching '{value}' is not allowed.",
                ],
            ],
        ];

        $ruleManager = new RuleManager($config);
        $node = new New_(new Name('LegacyClass'));
        $issues = $ruleManager->applyRules($node);

        $this->assertCount(1, $issues);
        $this->assertSame("Instantiation of class matching 'LegacyClass' is not allowed.", $issues[0]);
    }

    /**
     * Tests handling of multiple rules in the configuration.
     *
     * @return void
     */
    public function testApplyRulesWithMultipleMatchers(): void
    {
        $config = [
            'rules' => [
                [
                    'name' => 'restrict-functions',
                    'rule' => 'restrictFunction',
                    'matcher' => 'exact',
                    'values' => ['shell_exec'],
                    'message' => "This function '{value}' is not allowed.",
                ],
                [
                    'name' => 'restrict-variables',
                    'rule' => 'restrictVariable',
                    'matcher' => 'exact',
                    'values' => ['$_GET'],
                    'message' => "This variable '{value}' is restricted.",
                ],
            ],
        ];

        $ruleManager = new RuleManager($config);
        $nodeFunc = new FuncCall(new Name('shell_exec'));
        $nodeVar = new Variable('_GET');

        $issuesFunc = $ruleManager->applyRules($nodeFunc);
        $issuesVar = $ruleManager->applyRules($nodeVar);

        $this->assertCount(1, $issuesFunc);
        $this->assertCount(1, $issuesVar);
        $this->assertSame("This function 'shell_exec' is not allowed.", $issuesFunc[0]);
        $this->assertSame("This variable '\$_GET' is restricted.", $issuesVar[0]);
    }
}
