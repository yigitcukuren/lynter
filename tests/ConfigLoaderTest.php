<?php

namespace Tests;

use Lynter\ConfigLoader;
use PHPUnit\Framework\TestCase;

class ConfigLoaderTest extends TestCase
{
    public function testLoadValidConfig(): void
    {
        $config = ConfigLoader::load(__DIR__ . '/fixtures/valid_config.yml');
        $this->assertArrayHasKey('rules', $config);
    }

    public function testLoadInvalidConfig(): void
    {
        $this->expectException(\Exception::class);
        ConfigLoader::load(__DIR__ . '/fixtures/invalid_config.yml');
    }

    public function testLoadNonExistentConfig(): void
    {
        $this->expectException(\Exception::class);
        ConfigLoader::load(__DIR__ . '/fixtures/non_existent_config.yml');
    }
}
