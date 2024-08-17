<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__ . '/') // Scans the src directory for PHP files
    ->name('*.php')
    ->exclude('vendor'); // Excludes vendor files

return (new Config())
    ->setRules([
        '@PSR12' => true, // Enforces PSR12 standards
        'array_syntax' => ['syntax' => 'short'], // Enforces short array syntax
        'binary_operator_spaces' => [
            'default' => 'single_space'
        ],
        'blank_line_after_namespace' => true, // Adds blank line after the namespace declaration
        'blank_line_after_opening_tag' => true,
        'no_unused_imports' => true, // Removes unused imports
        'ordered_imports' => ['sort_algorithm' => 'alpha'], // Orders imports alphabetically
        'phpdoc_align' => ['align' => 'vertical'], // Aligns PHPDoc tags vertically
        'single_trait_insert_per_statement' => true // Enforces one trait per statement
    ])
    ->setFinder($finder);
