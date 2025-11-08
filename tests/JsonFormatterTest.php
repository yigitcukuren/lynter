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

        $expectedJson = json_encode(['issues' => $issues], JSON_PRETTY_PRINT);
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

        $expectedJson = json_encode(['issues' => []], JSON_PRETTY_PRINT);
        $actualJson = $formatter->format([]);

        $this->assertJsonStringEqualsJsonString($expectedJson, $actualJson);
    }

    /**
     * Ensures metadata is embedded under the summary key.
     */
    public function testFormatIncludesSummaryMetadata(): void
    {
        $formatter = new JsonFormatter();

        $issues = [
            ['file' => 'foo.php', 'line' => 10, 'message' => 'msg'],
        ];

        $summary = [
            'files' => 10,
            'time' => 1.234,
            'rate' => 8.1,
            'issues' => 1,
            'parallel' => 2,
            'batch' => 32,
        ];

        $expectedJson = json_encode(
            [
                'issues' => $issues,
                'summary' => $summary,
            ],
            JSON_PRETTY_PRINT
        );

        $actualJson = $formatter->format($issues, $summary);

        $this->assertJsonStringEqualsJsonString($expectedJson, $actualJson);
    }
}
