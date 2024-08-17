<?php

namespace Lynter;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

/**
 * Class Analyzer
 *
 * Analyzes PHP files by applying registered rules to the Abstract Syntax Tree (AST).
 */
class Analyzer
{
    /**
     * @var \PhpParser\Parser The parser used to generate the AST.
     */
    private $parser;

    /**
     * @var RuleManager The manager that handles rule application.
     */
    private RuleManager $ruleManager;

    /**
     * Analyzer constructor.
     *
     * @param RuleManager $ruleManager The manager that handles rule application.
     */
    public function __construct(RuleManager $ruleManager)
    {
        $this->parser = (new ParserFactory())->createForHostVersion();
        $this->ruleManager = $ruleManager;
    }

    /**
     * Analyze a PHP file and return any issues found.
     *
     * @param string $filePath The path to the PHP file to analyze.
     *
     * @return array<int, array<string, mixed>> An array of issues found in the file.
     *
     * @throws \Exception If the file cannot be read or parsed.
     */
    public function analyzeFile(string $filePath): array
    {
        $code = file_get_contents($filePath);

        if ($code === false) {
            throw new \Exception("Failed to read file: $filePath");
        }

        try {
            $ast = $this->parser->parse($code);
            if ($ast === null) {
                throw new \Exception("Failed to parse the file: $filePath");
            }

            $traverser = new NodeTraverser();
            $visitor = new class ($this->ruleManager, $filePath) extends NodeVisitorAbstract {
                /**
                 * @var RuleManager The manager that applies rules to the code.
                 */
                private RuleManager $ruleManager;

                /**
                 * @var string The path to the file being analyzed.
                 */
                private string $file;

                /**
                 * @var array<int, array<string, mixed>> The list of issues found in the file.
                 */
                private array $issues = [];

                /**
                 * Constructor for the anonymous class.
                 *
                 * @param RuleManager $ruleManager The manager that applies rules to the code.
                 * @param string      $file        The path to the file being analyzed.
                 */
                public function __construct(RuleManager $ruleManager, string $file)
                {
                    $this->ruleManager = $ruleManager;
                    $this->file = $file;
                }

                /**
                 * Applies rules to each node as it is encountered in the AST.
                 *
                 * @param Node $node The AST node being visited.
                 *
                 * @return Node|null Always returns null as no node modifications are made.
                 */
                public function enterNode(Node $node): ?Node
                {
                    foreach ($this->ruleManager->applyRules($node) as $message) {
                        $this->issues[] = [
                            'file' => $this->file,
                            'line' => $node->getLine(),
                            'message' => $message,
                        ];
                    }
                    return null;
                }

                /**
                 * Returns the list of issues found during the analysis.
                 *
                 * @return array<int, array<string, mixed>> The list of issues.
                 */
                public function getIssues(): array
                {
                    return $this->issues;
                }
            };

            $traverser->addVisitor($visitor);
            $traverser->traverse($ast);

            return $visitor->getIssues();

        } catch (\PhpParser\Error $e) {
            return [
                [
                    'file' => $filePath,
                    'line' => 0, // Line number for general errors
                    'message' => 'Parse error: ' . $e->getMessage(),
                ]
            ];
        }
    }
}
