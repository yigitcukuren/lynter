<?php

namespace Lynter;

use Symfony\Component\Yaml\Yaml;

/**
 * Class ConfigLoader
 *
 * Responsible for loading and parsing the YAML configuration file.
 */
class ConfigLoader
{
    /**
     * Load and parse the YAML configuration file.
     *
     * @param string $configFile The path to the YAML configuration file.
     *
     * @return array<string, mixed> The parsed configuration as an associative array.
     *
     * @throws \Exception If the configuration file does not exist or contains invalid YAML.
     */
    public static function load(string $configFile): array
    {
        if (!file_exists($configFile)) {
            throw new \Exception("Configuration file not found: $configFile");
        }

        $config = Yaml::parseFile($configFile);

        if ($config === null) {
            throw new \Exception("Invalid YAML in configuration file: $configFile");
        }

        return $config;
    }
}
