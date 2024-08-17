<?php

namespace Lynter\Output;

class JsonFormatter implements FormatterInterface
{
    public function format(array $issues): string
    {
        return json_encode($issues, JSON_PRETTY_PRINT);
    }
}
