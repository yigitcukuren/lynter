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
     * @param array $issues   The array of issues found during analysis.
     * @param array $metadata Additional metadata such as summaries or stats.
     *
     * @return string The formatted output as a JSON string.
     */
    public function format(array $issues, array $metadata = []): string
    {
        $payload = [
            'issues' => array_values($issues),
        ];

        if (!empty($metadata)) {
            $payload['summary'] = $metadata;
        }

        return json_encode($payload, JSON_PRETTY_PRINT);
    }
}
