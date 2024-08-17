<?php

namespace Tests\Output;

use Lynter\Output\RawFormatter;
use PHPUnit\Framework\TestCase;

class RawFormatterTest extends TestCase
{
    public function testFormatWithNoIssues(): void
    {
        $formatter = new RawFormatter();
        $output = $formatter->format([]);
        $this->assertStringContainsString('No issues found.', $output);
    }

    public function testFormatWithIssues(): void
    {
        $issues = [
            [
                'file' => './test.php',
                'line' => 10,
                'message' => 'Function \'eval\' is not allowed.'
            ]
        ];

        $formatter = new RawFormatter();
        $output = $formatter->format($issues);
        $this->assertStringContainsString('Function \'eval\' is not allowed.', $output);
    }
}
