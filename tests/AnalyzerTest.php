<?php

namespace Tests;

use Lynter\Analyzer;
use Lynter\RuleManager;
use PHPUnit\Framework\TestCase;

/**
 * Class AnalyzerTest
 *
 * Tests the functionality of the Analyzer class.
 */
class AnalyzerTest extends TestCase
{
    /**
     * Tests analyzing a file that contains issues.
     *
     * @return void
     */
    public function testAnalyzeFileWithIssues(): void
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
        $analyzer = new Analyzer($ruleManager);

        $issues = $analyzer->analyzeFile(__DIR__ . '/fixtures/with_issues.php');

        $this->assertCount(1, $issues);
        $this->assertSame("Function 'eval' is not allowed.", $issues[0]['message']);
    }

    /**
     * Tests analyzing a file that contains no issues.
     *
     * @return void
     */
    public function testAnalyzeFileWithNoIssues(): void
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
        $analyzer = new Analyzer($ruleManager);

        $issues = $analyzer->analyzeFile(__DIR__ . '/fixtures/no_issues.php');

        $this->assertCount(0, $issues);
    }
}
