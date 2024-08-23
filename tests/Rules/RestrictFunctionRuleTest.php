<?php

namespace Tests\Rules;

use Lynter\Rules\RestrictFunctionRule;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Eval_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PHPUnit\Framework\TestCase;

/**
 * Class RestrictFunctionRuleTest
 *
 * Tests the functionality of the RestrictFunctionRule class.
 */
class RestrictFunctionRuleTest extends TestCase
{
    /**
     * Tests the restriction of specific function calls with exact matching.
     *
     * @return void
     */
    public function testRestrictFunctionRule(): void
    {
        $config = [
            'values' => ['eval', 'exec', 'shell_exec'],
            'matcher' => 'exact',
            'message' => "This function '{value}' is not allowed.",
        ];

        $rule = new RestrictFunctionRule($config);

        // Test for 'eval' function
        $node = new FuncCall(new Name('eval'));
        $result = $rule->appliesTo($node);
        $this->assertTrue($result);
        $this->assertSame("This function 'eval' is not allowed.", $rule->getErrorMessage($node));

        // Test for 'exec' function
        $node = new FuncCall(new Name('exec'));
        $result = $rule->appliesTo($node);
        $this->assertTrue($result);
        $this->assertSame("This function 'exec' is not allowed.", $rule->getErrorMessage($node));

        // Test for 'shell_exec' function
        $node = new FuncCall(new Name('shell_exec'));
        $result = $rule->appliesTo($node);
        $this->assertTrue($result);
        $this->assertSame("This function 'shell_exec' is not allowed.", $rule->getErrorMessage($node));

        // Test for 'eval' expression
        $node = new Eval_(new ConstFetch(new FullyQualified('null')));
        $result = $rule->appliesTo($node);
        $this->assertTrue($result);
        $this->assertSame("This function 'eval' is not allowed.", $rule->getErrorMessage($node));

        // Test for a function not in the list
        $node = new FuncCall(new Name('non_restricted_function'));
        $result = $rule->appliesTo($node);
        $this->assertFalse($result);
    }

    /**
     * Tests the restriction of function calls using regex patterns.
     *
     * @return void
     */
    public function testRestrictFunctionRegexRule(): void
    {
        $config = [
            'values' => ['/^debug_/', '/^test_/'],
            'matcher' => 'pattern',
            'message' => "This function matching '{value}' is not allowed.",
        ];

        $rule = new RestrictFunctionRule($config);

        // Test for a function starting with 'debug_'
        $node = new FuncCall(new Name('debug_log'));
        $result = $rule->appliesTo($node);
        $this->assertTrue($result);
        $this->assertSame("This function matching 'debug_log' is not allowed.", $rule->getErrorMessage($node));

        // Test for a function starting with 'test_'
        $node = new FuncCall(new Name('test_function'));
        $result = $rule->appliesTo($node);
        $this->assertTrue($result);
        $this->assertSame("This function matching 'test_function' is not allowed.", $rule->getErrorMessage($node));

        // Test for a function not matching the pattern
        $node = new FuncCall(new Name('log_debug'));
        $result = $rule->appliesTo($node);
        $this->assertFalse($result);
    }

    /**
      * Tests handling of non-matching function names.
      *
       * @return void
      */
    public function testNonMatchingFunctionName(): void
    {
        $config = [
            'values' => ['eval'],
            'matcher' => 'exact',
            'message' => "This function '{value}' is not allowed.",
        ];

        $rule = new RestrictFunctionRule($config);

        // Test with a non-matching function name (a function that is not restricted)
        $node = new FuncCall(new Name('non_matching_function'));
        $result = $rule->appliesTo($node);
        $this->assertFalse($result);

        // Test with a non-string name (will be treated as an invalid function name)
        $node = new FuncCall(new Name(['array_part1', 'array_part2']));
        $result = $rule->appliesTo($node);
        $this->assertFalse($result);
    }
}
