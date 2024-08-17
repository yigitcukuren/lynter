<?php

namespace Tests\Output;

use Lynter\Output\JsonFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Class JsonFormatterTest
 *
 * Tests the functionality of the JsonFormatter class.
 */
class JsonFormatterTest extends TestCase
{
    /**
     * Tests formatting with issues present.
     *
     * @return void
     */
    public function testFormatWithIssues(): void
    {
        $formatter = new JsonFormatter();

        $issues = [
            [
                'file' => './test_file.php',
                'line' => 10,
                'message' => "Function 'eval' is not allowed.",
            ],
            [
                'file' => './test_file.php',
                'line' => 15,
                'message' => "Function 'exec' is not allowed.",
            ],
        ];

        $expectedJson = json_encode($issues, JSON_PRETTY_PRINT);
        $actualJson = $formatter->format($issues);

        $this->assertJsonStringEqualsJsonString($expectedJson, $actualJson);
    }

    /**
     * Tests formatting with no issues present.
     *
     * @return void
     */
    public function testFormatWithoutIssues(): void
    {
        $formatter = new JsonFormatter();

        $issues = [];

        $expectedJson = json_encode($issues, JSON_PRETTY_PRINT);
        $actualJson = $formatter->format($issues);

        $this->assertJsonStringEqualsJsonString($expectedJson, $actualJson);
    }
}
