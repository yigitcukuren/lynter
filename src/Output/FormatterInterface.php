<?php

namespace Lynter\Output;

interface FormatterInterface
{
    /**
     * Format the analysis results.
     *
     * @param array $issues   The array of issues found during analysis.
     * @param array $metadata Additional data (e.g. summary) to render alongside the issues.
     *
     * @return string The formatted output.
     */
    public function format(array $issues, array $metadata = []): string;
}
