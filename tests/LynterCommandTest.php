<?php

namespace Tests\Command;

use Lynter\Command\LynterCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class LynterCommandTest extends TestCase
{
    public function xtestExecuteWithNoIssues(): void
    {
        $application = new Application();
        $command = new LynterCommand();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'paths' => [__DIR__ . '/fixtures/no_issues.php'],
            '--config' => __DIR__ . '/fixtures/valid_config.yml',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('No issues found.', $output);
    }

    public function testExecuteWithIssues(): void
    {
        $application = new Application();
        $command = new LynterCommand();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'paths' => [__DIR__ . '/fixtures/with_issues.php'],
            '--config' => __DIR__ . '/fixtures/valid_config.yml',
        ]);

        $output = $commandTester->getDisplay();

        // Check for the restricted 'eval' function
        $this->assertStringContainsString('Function \'eval\' is not allowed.', $output);

        // Check for another restricted function, e.g., 'exec'
        $this->assertStringContainsString('Function \'shell_exec\' is not allowed.', $output);

        // Check for a non-restricted function to ensure it does not trigger an error
        $this->assertStringNotContainsString('Function \'nonRestrictedFunction\' is not allowed.', $output);
    }
}
