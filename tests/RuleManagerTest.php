<?php

namespace Tests;

use Lynter\RuleManager;
use PhpParser\Node\Expr\FuncCall;
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
     * Tests applying rules when a restricted function is used.
     *
     * @return void
     */
    public function testApplyRulesWithRestrictedFunction(): void
    {
        $config = [
            'rules' => [
                'restrictFunction' => [
                    'functions' => ['eval'],
                    'message' => "Function '{name}' is not allowed.",
                ],
            ],
        ];

        $ruleManager = new RuleManager($config);
        $node = new FuncCall(new Name('eval'));
        $issues = $ruleManager->applyRules($node);

        $this->assertCount(1, $issues);
        $this->assertSame("Function 'eval' is not allowed.", $issues[0]);
    }

    /**
     * Tests applying rules when there are no issues.
     *
     * @return void
     */
    public function testApplyRulesWithNoIssues(): void
    {
        $config = [
            'rules' => [
                'restrictFunction' => [
                    'functions' => ['eval'],
                    'message' => "Function '{name}' is not allowed.",
                ],
            ],
        ];

        $ruleManager = new RuleManager($config);
        $node = new FuncCall(new Name('someOtherFunction'));
        $issues = $ruleManager->applyRules($node);

        $this->assertCount(0, $issues);
    }
}
