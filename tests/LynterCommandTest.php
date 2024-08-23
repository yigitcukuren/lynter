<?php

namespace Tests\Command;

use Lynter\Command\LynterCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class LynterCommandTest
 *
 * Tests the functionality of the LynterCommand class.
 */
class LynterCommandTest extends TestCase
{
    /**
     * Tests the execution of the command when no issues are found.
     *
     * @return void
     */
    public function testExecuteWithNoIssues(): void
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

    /**
     * Tests the execution of the command when issues are found.
     *
     * @return void
     */
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
        $this->assertStringContainsString("This function 'eval' is not allowed.", $output);
    }
}
