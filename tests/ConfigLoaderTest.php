<?php

namespace Tests;

use Lynter\ConfigLoader;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigLoaderTest
 *
 * Tests the functionality of the ConfigLoader class.
 */
class ConfigLoaderTest extends TestCase
{
    /**
     * Tests loading a valid configuration file.
     *
     * @return void
     */
    public function testLoadValidConfig(): void
    {
        $config = ConfigLoader::load(__DIR__ . '/fixtures/valid_config.yml');
        $this->assertArrayHasKey('rules', $config);
        $this->assertIsArray($config['rules']);
    }

    /**
     * Tests loading an invalid configuration file with syntax issues.
     *
     * @return void
     */
    public function testLoadInvalidSyntaxConfig(): void
    {
        $this->expectException(\Exception::class);
        ConfigLoader::load(__DIR__ . '/fixtures/invalid_syntax_config.yml');
    }

    /**
     * Tests loading a non-existent configuration file.
     *
     * @return void
     */
    public function testLoadNonExistentConfig(): void
    {
        $this->expectException(\Exception::class);
        ConfigLoader::load(__DIR__ . '/fixtures/non_existent_config.yml');
    }

    /**
     * Tests loading a configuration file with missing fields.
     *
     * @return void
     */
    public function testLoadConfigWithMissingFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ConfigLoader::load(__DIR__ . '/fixtures/missing_fields_config.yml');
    }

    /**
     * Tests loading a configuration file with unsupported rule types.
     *
     * @return void
     */
    public function testLoadConfigWithUnsupportedRule(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ConfigLoader::load(__DIR__ . '/fixtures/unsupported_rule_config.yml');
    }

    /**
     * Tests loading a configuration file with unsupported matchers.
     *
     * @return void
     */
    public function testLoadConfigWithUnsupportedMatcher(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ConfigLoader::load(__DIR__ . '/fixtures/unsupported_matcher_config.yml');
    }
}
