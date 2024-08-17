<?php

namespace Tests;

use Lynter\ConfigLoader;
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
    }

    /**
     * Tests loading an invalid configuration file.
     *
     * @return void
     */
    public function testLoadInvalidConfig(): void
    {
        $this->expectException(\Exception::class);
        ConfigLoader::load(__DIR__ . '/fixtures/invalid_config.yml');
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
}
