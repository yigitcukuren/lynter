<?php

namespace Tests\Rules;

use Lynter\Rules\RestrictVariableRule;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\TestCase;

/**
 * Class RestrictVariableRuleTest
 *
 * Tests the functionality of the RestrictVariableRule class.
 */
class RestrictVariableRuleTest extends TestCase
{
    /**
     * Tests the restriction of specific variables.
     *
     * @return void
     */
    public function testRestrictVariableRule(): void
    {
        $config = [
            'values' => ['$_GET', '$_POST'],
            'matcher' => 'exact',
            'message' => "This variable '{value}' is restricted.",
        ];

        $rule = new RestrictVariableRule($config);

        // Test for '$_GET' variable
        $node = new Variable('_GET');
        $result = $rule->appliesTo($node);
        $this->assertTrue($result);
        $this->assertSame("This variable '\$_GET' is restricted.", $rule->getErrorMessage($node));

        // Test for '$_POST' variable
        $node = new Variable('_POST');
        $result = $rule->appliesTo($node);
        $this->assertTrue($result);
        $this->assertSame("This variable '\$_POST' is restricted.", $rule->getErrorMessage($node));

        // Test for a variable not in the list
        $node = new Variable('non_restricted_variable');
        $result = $rule->appliesTo($node);
        $this->assertFalse($result);
    }

    /**
     * Tests the restriction of variables using regex patterns.
     *
     * @return void
     */
    public function testRestrictVariableRegexRule(): void
    {
        $config = [
            'values' => ['/^\$temp/', '/^\$data/'],
            'matcher' => 'pattern',
            'message' => "This variable matching '{value}' is restricted.",
        ];

        $rule = new RestrictVariableRule($config);

        // Test for a variable starting with '$temp'
        $node = new Variable('tempVar');
        $result = $rule->appliesTo($node);
        $this->assertTrue($result);
        $this->assertSame("This variable matching '\$tempVar' is restricted.", $rule->getErrorMessage($node));

        // Test for a variable starting with '$data'
        $node = new Variable('dataField');
        $result = $rule->appliesTo($node);
        $this->assertTrue($result);
        $this->assertSame("This variable matching '\$dataField' is restricted.", $rule->getErrorMessage($node));

        // Test for a variable not matching the pattern
        $node = new Variable('nonTempVar');
        $result = $rule->appliesTo($node);
        $this->assertFalse($result);
    }

    /**
     * Tests handling of invalid variable names.
     *
     * @return void
     */
    public function testInvalidVariableName(): void
    {
        $config = [
            'values' => ['$_GET'],
            'matcher' => 'exact',
            'message' => "This variable '{value}' is restricted.",
        ];

        $rule = new RestrictVariableRule($config);

        // Test with an invalid variable name (not a string)
        $node = new Variable(1234);
        $result = $rule->appliesTo($node);
        $this->assertFalse($result);
    }
}
