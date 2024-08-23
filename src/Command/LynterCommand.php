<?php

namespace Lynter\Command;

use Lynter\Analyzer;
use Lynter\ConfigLoader;
use Lynter\Output\ColorHelper;
use Lynter\Output\JsonFormatter;
use Lynter\Output\RawFormatter;
use Lynter\RuleManager;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Class LynterCommand
 *
 * Handles the analysis of PHP files or directories using the Lynter tool.
 * Supports parallel execution and various output formats.
 */
class LynterCommand extends Command
{
    /**
     * Configures the command name, description, arguments, and options.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('analyze') // Explicitly set the command name
            ->setDescription(
                'Analyze PHP files or directories for coding standards and restrictions.'
            )
            ->addArgument(
                'paths',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'The files or directories to analyze.'
            )
            ->addOption(
                'config',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to a custom configuration file',
                'lynter.yml'
            )
            ->addOption(
                'output',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output format (raw, json)',
                'raw'
            )
            ->addOption(
                'parallel',
                null,
                InputOption::VALUE_OPTIONAL,
                'Number of parallel processes to use',
                1
            );
    }

    /**
     * Executes the analysis based on the provided inputs and options.
     *
     * @param InputInterface  $input  The input interface.
     * @param OutputInterface $output The output interface.
     *
     * @return int The exit status code.
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $paths = $input->getArgument('paths');
        $configFile = $input->getOption('config');
        $outputFormat = $input->getOption('output');
        $parallelProcesses = (int) $input->getOption('parallel');

        if (!file_exists($configFile)) {
            $output->writeln(
                ColorHelper::red("Configuration file not found: $configFile")
            );
            return Command::FAILURE;
        }

        try {
            $config = ConfigLoader::load($configFile);
        } catch (ParseException $e) {
            $output->writeln(
                ColorHelper::red(
                    "Error parsing configuration file: " . $e->getMessage()
                )
            );
            return Command::FAILURE;
        }

        $ruleManager = new RuleManager($config);
        $analyzer = new Analyzer($ruleManager);

        // Collect all files to be analyzed
        $filesToAnalyze = $this->collectFiles(
            $paths,
            $config['exclude'] ?? []
        );

        // Run analysis in parallel if more than one process is specified
        if ($parallelProcesses > 1) {
            $issues = $this->runInParallel(
                $filesToAnalyze,
                $parallelProcesses,
                $configFile
            );
        } else {
            $issues = [];
            foreach ($filesToAnalyze as $file) {
                $issues = array_merge(
                    $issues,
                    $analyzer->analyzeFile($file)
                );
            }
        }

        // Output the results in the user-specified format
        $this->outputResults($issues, $outputFormat, $output);

        // Return failure code if issues are found
        if (!empty($issues)) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Runs the analysis in parallel processes and collects results.
     *
     * @param array  $filesToAnalyze    The files to analyze.
     * @param int    $parallelProcesses The number of parallel processes to use.
     * @param string $configFile        The configuration file path.
     *
     * @return array The combined list of issues from all processes.
     */
    private function runInParallel(
        array $filesToAnalyze,
        int $parallelProcesses,
        string $configFile
    ): array {
        $processes = [];
        $issues = [];

        // Split the files into chunks for each process
        $chunks = array_chunk(
            $filesToAnalyze,
            (int) ceil(count($filesToAnalyze) / $parallelProcesses)
        );

        foreach ($chunks as $chunk) {
            $process = new Process([
                PHP_BINARY,
                $_SERVER['SCRIPT_FILENAME'],
                'analyze',
                '--config=' . $configFile,
                '--output=json', // Force JSON output for parallel processing
                '--',
                ...$chunk
            ]);

            $process->start();
            $processes[] = $process;
        }

        foreach ($processes as $process) {
            $process->wait();
            $output = $process->getOutput();
            $decodedOutput = json_decode($output, true);

            if (
                json_last_error() === JSON_ERROR_NONE
                && is_array($decodedOutput)
            ) {
                $issues = array_merge($issues, $decodedOutput);
            } else {
                throw new \RuntimeException(
                    "Invalid output from process: $output"
                );
            }
        }

        return $issues;
    }

    /**
     * Collects all files to be analyzed based on the paths provided,
     * excluding those in the directories listed in the 'exclude' config.
     *
     * @param array $paths   The paths to collect files from.
     * @param array $exclude The list of directories to exclude.
     *
     * @return array The list of files to analyze.
     */
    private function collectFiles(
        array $paths,
        array $exclude
    ): array {
        $filesToAnalyze = [];
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path)
                );
                foreach ($files as $file) {
                    $relativePath = $file->getPathname();
                    if (
                        $file->isFile()
                        && $file->getExtension() === 'php'
                        && !$this->isExcluded($relativePath, $exclude)
                    ) {
                        $filesToAnalyze[] = $relativePath;
                    }
                }
            } elseif (
                is_file($path)
                && pathinfo($path, PATHINFO_EXTENSION) === 'php'
                && !$this->isExcluded($path, $exclude)
            ) {
                $filesToAnalyze[] = $path;
            }
        }

        return $filesToAnalyze;
    }

    /**
     * Determines if a file should be excluded based on the exclude paths.
     *
     * @param string $filePath The file path to check.
     * @param array  $exclude  The list of directories to exclude.
     *
     * @return bool True if the file should be excluded, false otherwise.
     */
    private function isExcluded(
        string $filePath,
        array $exclude
    ): bool {
        foreach ($exclude as $excludePath) {
            if (strpos($filePath, $excludePath) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Outputs the results to the console.
     *
     * @param array           $issues       The issues found during analysis.
     * @param string          $outputFormat The format to use for output.
     * @param OutputInterface $output       The output interface.
     *
     * @return void
     */
    private function outputResults(
        array $issues,
        string $outputFormat,
        OutputInterface $output
    ): void {
        switch ($outputFormat) {
            case 'json':
                $formatter = new JsonFormatter();
                break;
            case 'raw':
            default:
                $formatter = new RawFormatter();
                break;
        }

        $output->write($formatter->format($issues));
    }
}
