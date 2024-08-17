<?php

namespace Lynter\Output;

interface FormatterInterface
{
    /**
     * Format the analysis results.
     *
     * @param  array  $issues The array of issues found during analysis.
     * @return string The formatted output.
     */
    public function format(array $issues): string;
}
