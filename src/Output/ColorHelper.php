<?php

namespace Lynter\Output;

/**
 * Class ColorHelper
 *
 * Provides utility methods for applying ANSI colors to strings.
 */
class ColorHelper
{
    public const COLOR_RESET = "\033[0m";
    public const COLOR_GREEN = "\033[32m";
    public const COLOR_RED = "\033[31m";
    public const COLOR_YELLOW = "\033[33m";
    public const COLOR_CYAN = "\033[36m";

    /**
     * Apply green color to the string.
     *
     * @param  string $text The text to color.
     * @return string The colorized string.
     */
    public static function green(string $text): string
    {
        return self::COLOR_GREEN . $text . self::COLOR_RESET;
    }

    /**
     * Apply red color to the string.
     *
     * @param  string $text The text to color.
     * @return string The colorized string.
     */
    public static function red(string $text): string
    {
        return self::COLOR_RED . $text . self::COLOR_RESET;
    }

    /**
     * Apply soft red color to the string.
     *
     * @param  string $text The text to color.
     * @return string The colorized string.
     */
    public static function softRed(string $text): string
    {
        return "\033[91m$text\033[0m"; // Softer Red (Bright Red)
    }

    /**
     * Apply yellow color to the string.
     *
     * @param  string $text The text to color.
     * @return string The colorized string.
     */
    public static function yellow(string $text): string
    {
        return self::COLOR_YELLOW . $text . self::COLOR_RESET;
    }

    /**
     * Apply cyan color to the string.
     *
     * @param  string $text The text to color.
     * @return string The colorized string.
     */
    public static function cyan(string $text): string
    {
        return self::COLOR_CYAN . $text . self::COLOR_RESET;
    }

    /**
     * Apply gray color to the string.
     *
     * @param  string $text The text to color.
     * @return string The colorized string.
     */
    public static function gray(string $text): string
    {
        return "\033[90m$text\033[0m"; // Gray
    }
}
