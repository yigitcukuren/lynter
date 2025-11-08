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
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Class LynterCommand
 *
 * Coordinates loading the configuration, dispatching analysis jobs,
 * and presenting the formatted output for the user.
 */
class LynterCommand extends Command
{
    /**
     * Configures the command name, description, arguments, and options.
     */
    protected function configure(): void
    {
        $this
            ->setName('analyze')
            ->setDescription('Analyze PHP files or directories for coding standards and restrictions.')
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
                0
            )
            ->addOption(
                'batch-size',
                null,
                InputOption::VALUE_OPTIONAL,
                'How many files per child process',
                32
            )
            ->addOption(
                'timeout',
                null,
                InputOption::VALUE_OPTIONAL,
                'Seconds before a child process is terminated (0=disabled)',
                300
            );
    }

    /**
     * Executes the analyzer with the given inputs/options.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $start = microtime(true);

        $paths = $input->getArgument('paths');
        $configFile = $input->getOption('config');
        $outputFormat = $input->getOption('output');
        $parallelProcesses = (int) $input->getOption('parallel');
        $batchSize = (int) $input->getOption('batch-size');
        $timeout = (int) $input->getOption('timeout');

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

        if (getenv('LYNTER_CHILD') === '1') {
            $parallelProcesses = 1;
        }

        $ruleManager = new RuleManager($config);
        $analyzer = new Analyzer($ruleManager);

        $filesToAnalyze = $this->collectFiles(
            $paths,
            $config['exclude'] ?? []
        );
        $filesCount = count($filesToAnalyze);

        if ($parallelProcesses <= 0) {
            $parallelProcesses = $this->detectCores();
            $parallelProcesses = max(1, min($parallelProcesses, max(1, $filesCount)));
        }

        if ($parallelProcesses > 1) {
            $issues = $this->runInParallel(
                $filesToAnalyze,
                $parallelProcesses,
                $configFile,
                max(1, $batchSize),
                max(0, $timeout)
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

        $elapsed = max(0.000001, microtime(true) - $start);
        $summary = $this->createSummary($filesCount, $elapsed, count($issues), $parallelProcesses, $batchSize);

        $this->outputResults($issues, $outputFormat, $output, $summary);

        if ($outputFormat !== 'json') {
            $this->writeSummary($output, $summary);
        }

        if (!empty($issues)) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Distributes files across child processes to speed up analysis.
     *
     * @param array<int, string> $filesToAnalyze
     * @param int                $parallelProcesses
     * @param string             $configFile
     * @param int                $batchSize
     * @param int                $timeout
     *
     * @return array<int, array<string, mixed>>
     */
    private function runInParallel(
        array $filesToAnalyze,
        int $parallelProcesses,
        string $configFile,
        int $batchSize,
        int $timeout
    ): array {
        $issues = [];

        $queue = new \SplQueue();
        foreach ($filesToAnalyze as $file) {
            $queue->enqueue($file);
        }

        $bin = realpath(__DIR__ . '/../../bin/lynter') ?: ($_SERVER['SCRIPT_FILENAME'] ?? '');
        if (!$bin || !is_file($bin)) {
            throw new \RuntimeException('Cannot locate lynter binary.');
        }

        $running = [];

        $spawn = function () use (&$queue, &$running, $configFile, $batchSize, $timeout, $bin): void {
            if ($queue->isEmpty()) {
                return;
            }

            $batch = [];
            while ($queue->count() > 0 && count($batch) < $batchSize) {
                $batch[] = $queue->dequeue();
            }

            $args = [
                PHP_BINARY,
                $bin,
                'analyze',
                '--output=json',
                '--no-ansi',
                '--config=' . $configFile,
                '--parallel=1',
                '--',
                ...$batch,
            ];

            $env = getenv();
            if (!is_array($env)) {
                $env = [];
            }
            $env['LYNTER_CHILD'] = '1';

            $process = new Process($args, null, $env);
            if ($timeout > 0) {
                $process->setTimeout($timeout);
            } else {
                $process->setTimeout(null);
            }
            $process->start();

            $running[] = ['process' => $process, 'files' => $batch];
        };

        for ($i = 0; $i < $parallelProcesses && !$queue->isEmpty(); $i++) {
            $spawn();
        }

        while (!empty($running)) {
            foreach ($running as $idx => $slot) {
                $proc = $slot['process'];
                if ($proc->isRunning()) {
                    continue;
                }

                $output = $proc->getOutput();
                $decoded = json_decode($output, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $childIssues = $decoded;
                    if (!$this->isListArray($decoded)) {
                        $childIssues = $decoded['issues'] ?? null;
                    }

                    if (!is_array($childIssues)) {
                        throw new \RuntimeException('Child process JSON output missing issues key.');
                    }

                    $issues = array_merge($issues, $childIssues);
                } else {
                    $code = $proc->getExitCode();
                    $err = trim($proc->getErrorOutput());
                    $preview = substr($output, 0, 400);
                    throw new \RuntimeException(
                        "Child process produced invalid output (exit {$code}).\n".
                        "STDERR:\n{$err}\n\n".
                        "STDOUT first 400 chars:\n{$preview}"
                    );
                }

                unset($running[$idx]);
            }

            while (count($running) < $parallelProcesses && !$queue->isEmpty()) {
                $spawn();
            }

            usleep(10000);
        }

        return $issues;
    }

    /**
     * Recursively collects PHP files from the provided paths.
     *
     * @param array<int, string> $paths
     * @param array<int, string> $exclude
     *
     * @return array<int, string>
     */
    private function collectFiles(
        array $paths,
        array $exclude
    ): array {
        $filesToAnalyze = [];
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path)
                );
                foreach ($it as $file) {
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

        sort($filesToAnalyze, SORT_STRING);
        $filesToAnalyze = array_values(array_unique($filesToAnalyze));

        return $filesToAnalyze;
    }

    /**
     * Determines whether the given file should be skipped.
     *
     * @param string             $filePath
     * @param array<int, string> $exclude
     *
     * @return bool
     */
    private function isExcluded(string $filePath, array $exclude): bool
    {
        $normalizedFile = $this->resolvePath($filePath);
        if ($normalizedFile === '') {
            $normalizedFile = $this->normalizePath($filePath);
        }

        foreach ($exclude as $ex) {
            $resolved = $this->resolvePath($ex);
            if ($resolved === '') {
                continue;
            }

            if ($normalizedFile === $resolved) {
                return true;
            }

            $prefix = $resolved . '/';
            $fileWithSlash = $normalizedFile . '/';
            if (strpos($fileWithSlash, $prefix) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Renders the analysis results using the requested formatter.
     *
     * @param array<int, array<string, mixed>> $issues
     * @param string                           $outputFormat
     * @param OutputInterface                  $output
     * @param array<string, int|float>         $summary
     *
     * @return void
     */
    private function outputResults(
        array $issues,
        string $outputFormat,
        OutputInterface $output,
        array $summary
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

        $output->write($formatter->format($issues, $summary));
    }

    /**
     * Attempts to detect the number of CPU cores to use as a parallel hint.
     *
     * @return int
     */
    private function detectCores(): int
    {
        $n = 0;
        if (stripos(PHP_OS_FAMILY, 'Windows') !== false) {
            $n = (int) getenv('NUMBER_OF_PROCESSORS');
        } else {
            $n = $this->readCpuCount(['getconf', '_NPROCESSORS_ONLN']);
            if ($n <= 0) {
                $n = $this->readCpuCount(['nproc']);
            }
        }
        if ($n <= 0) {
            $n = 4;
        }
        return $n;
    }

    /**
     * Attempts to read an integer CPU count from the given command.
     *
     * @param array<int, string> $command
     */
    private function readCpuCount(array $command): int
    {
        try {
            $process = new Process($command);
            $process->run();
            if (!$process->isSuccessful()) {
                return 0;
            }
            $output = trim($process->getOutput());
            if ($output === '') {
                return 0;
            }
            return (int) $output;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Prints a short runtime summary to STDERR when verbose output is requested.
     *
     * @param OutputInterface                                                          $output
     * @param array{files:int,time:float,rate:float,issues:int,parallel:int,batch:int} $summary
     *
     * @return void
     */
    private function writeSummary(OutputInterface $output, array $summary): void
    {
        if (!$output instanceof ConsoleOutputInterface || !$output->isVerbose()) {
            return;
        }

        $msg = sprintf(
            "Summary: files=%d time=%.2fs rate=%.2f files/s issues=%d parallel=%d batch=%d",
            $summary['files'],
            $summary['time'],
            $summary['rate'],
            $summary['issues'],
            $summary['parallel'],
            $summary['batch']
        );

        $output->getErrorOutput()->writeln($msg);
    }

    /**
     * Converts the given path into a normalized absolute path if possible.
     */
    private function resolvePath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return '';
        }

        if (!$this->isAbsolutePath($trimmed)) {
            $root = getcwd() ?: dirname(__DIR__, 2);
            $trimmed = $root . DIRECTORY_SEPARATOR . $trimmed;
        }

        $real = realpath($trimmed);

        return $this->normalizePath($real !== false ? $real : $trimmed);
    }

    /**
     * Normalizes directory separators and removes redundant trailing slashes.
     */
    private function normalizePath(string $path): string
    {
        if ($path === '') {
            return '';
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path) ?: $path;

        if (strlen($path) > 1) {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    /**
     * Determines whether the provided path is absolute on the current platform.
     */
    private function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if ($path[0] === '/' || $path[0] === '\\') {
            return true;
        }

        return (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }

    /**
     * Builds the summary payload shared between JSON output and verbose console logs.
     *
     * @return array{files:int,time:float,rate:float,issues:int,parallel:int,batch:int}
     */
    private function createSummary(int $files, float $secs, int $issueCount, int $parallel, int $batch): array
    {
        $rate = $files > 0 ? $files / $secs : 0.0;

        return [
            'files' => $files,
            'time' => round($secs, 4),
            'rate' => round($rate, 4),
            'issues' => $issueCount,
            'parallel' => $parallel,
            'batch' => $batch,
        ];
    }

    /**
     * Lightweight replacement for array_is_list for older PHP versions.
     */
    private function isListArray(array $array): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($array);
        }

        $expectedKey = 0;
        foreach ($array as $key => $_) {
            if ($key !== $expectedKey) {
                return false;
            }
            $expectedKey++;
        }

        return true;
    }
}
