<?php

namespace Tests\Rules;

use Lynter\Rules\RestrictClassRule;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPUnit\Framework\TestCase;

/**
 * Class RestrictClassRuleTest
 *
 * Tests the functionality of the RestrictClassRule class.
 */
class RestrictClassRuleTest extends TestCase
{
    /**
     * Tests the restriction of specific class instantiations.
     *
     * @return void
     */
    public function testRestrictClassRule(): void
    {
        $config = [
            'values' => ['MyRestrictedClass'],
            'matcher' => 'exact',
            'message' => "Instantiation of '{value}' is not allowed.",
        ];

        $rule = new RestrictClassRule($config);

        // Test for 'MyRestrictedClass'
        $node = new New_(new Name('MyRestrictedClass'));
        $result = $rule->appliesTo($node);
        $this->assertTrue($result);
        $this->assertSame(
            "Instantiation of 'MyRestrictedClass' is not allowed.",
            $rule->getErrorMessage($node)
        );

        // Test for a class not in the list
        $node = new New_(new Name('AllowedClass'));
        $result = $rule->appliesTo($node);
        $this->assertFalse($result);
    }

    /**
     * Tests the restriction of class instantiations using regex patterns.
     *
     * @return void
     */
    public function testRestrictClassRegexRule(): void
    {
        $config = [
            'values' => ['/^Legacy/'],
            'matcher' => 'pattern',
            'message' => "Instantiation of class matching '{value}' is not allowed.",
        ];

        $rule = new RestrictClassRule($config);

        // Test for a class starting with 'Legacy'
        $node = new New_(new Name('LegacyClass'));
        $result = $rule->appliesTo($node);
        $this->assertTrue($result);
        $this->assertSame(
            "Instantiation of class matching 'LegacyClass' is not allowed.",
            $rule->getErrorMessage($node)
        );

        // Test for a class not matching the pattern
        $node = new New_(new Name('ModernClass'));
        $result = $rule->appliesTo($node);
        $this->assertFalse($result);
    }

    /**
      * Tests handling of non-matching class names.
      *
       * @return void
      */
    public function testNonMatchingClassName(): void
    {
        $config = [
            'values' => ['MyRestrictedClass'],
            'matcher' => 'exact',
            'message' => "Instantiation of '{value}' is not allowed.",
        ];

        $rule = new RestrictClassRule($config);

        // Test with a non-matching class name (a class that is not restricted)
        $node = new New_(new Name('NonRestrictedClass'));
        $result = $rule->appliesTo($node);
        $this->assertFalse($result);

        // Test with a valid name, but constructed using an array of parts
        $node = new New_(new Name(['Some', 'Namespaced', 'Class']));
        $result = $rule->appliesTo($node);
        $this->assertFalse($result);
    }
}
