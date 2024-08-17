<?php

namespace Lynter\Output;

/**
 * Class JsonFormatter
 *
 * Formats analysis results as a JSON string.
 */
class JsonFormatter implements FormatterInterface
{
    /**
     * Format the analysis results as a JSON string.
     *
     * @param  array  $issues The array of issues found during analysis.
     * @return string The formatted output as a JSON string.
     */
    public function format(array $issues): string
    {
        return json_encode($issues, JSON_PRETTY_PRINT);
    }
}
