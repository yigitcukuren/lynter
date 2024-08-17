<?php

namespace Tests\Output;

use Lynter\Output\RawFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Class RawFormatterTest
 *
 * Tests the functionality of the RawFormatter class.
 */
class RawFormatterTest extends TestCase
{
    /**
     * Tests formatting output when no issues are found.
     *
     * @return void
     */
    public function testFormatWithNoIssues(): void
    {
        $formatter = new RawFormatter();
        $output = $formatter->format([]);
        $this->assertStringContainsString('No issues found.', $output);
    }

    /**
     * Tests formatting output when issues are found.
     *
     * @return void
     */
    public function testFormatWithIssues(): void
    {
        $issues = [
            [
                'file' => './test.php',
                'line' => 10,
                'message' => "Function 'eval' is not allowed."
            ]
        ];

        $formatter = new RawFormatter();
        $output = $formatter->format($issues);
        $this->assertStringContainsString("Function 'eval' is not allowed.", $output);
    }
}
