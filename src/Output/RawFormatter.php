<?php

namespace Lynter\Output;

/**
 * Class RawFormatter
 *
 * Formats analysis results as raw text output in a format similar to PHPStan, with colors.
 */
class RawFormatter implements FormatterInterface
{
    /**
     * Format the analysis results as raw text.
     *
     * @param  array  $issues The array of issues found during analysis.
     * @return string The formatted output as a string.
     */
    public function format(array $issues): string
    {
        $output = "";

        if (empty($issues)) {
            $output .= ColorHelper::green("No issues found.") . PHP_EOL;
        } else {
            // Group issues by file
            $groupedIssues = $this->groupIssuesByFile($issues);

            foreach ($groupedIssues as $file => $fileIssues) {
                // File header with no leading empty row
                $output .= ColorHelper::gray(" ------ " . str_repeat("-", 82)) . PHP_EOL;
                $output .= "  " . ColorHelper::cyan("Line") . "   " . ColorHelper::yellow($file) . PHP_EOL;
                $output .= ColorHelper::gray(" ------ " . str_repeat("-", 82)) . PHP_EOL;

                foreach ($fileIssues as $issue) {
                    if (isset($issue['line'], $issue['message'])) {
                        $output .= sprintf(
                            "  %s%-6d %s",
                            ColorHelper::cyan(':'),
                            $issue['line'],
                            ColorHelper::softRed($issue['message'])  // Consistent color for error messages
                        ) . PHP_EOL;
                    }
                }
            }
        }

        return $output;
    }

    /**
     * Groups the issues by the file they were found in.
     *
     * @param  array $issues The array of issues to group.
     * @return array The grouped issues.
     */
    private function groupIssuesByFile(array $issues): array
    {
        $grouped = [];
        foreach ($issues as $issue) {
            if (isset($issue['file'])) {
                $grouped[$issue['file']][] = $issue;
            }
        }
        return $grouped;
    }
}
