<?php

namespace Lynter;

use Symfony\Component\Yaml\Yaml;
use InvalidArgumentException;

/**
 * Class ConfigLoader
 *
 * Responsible for loading and parsing the YAML configuration file.
 */
class ConfigLoader
{
    /**
     * @var array<string> The list of supported rules.
     */
    private static array $supportedRules = [
        'restrictFunction',
        'restrictVariable',
        'restrictClass',
    ];

    /**
     * @var array<string> The list of supported matchers.
     */
    private static array $supportedMatchers = [
        'exact',
        'pattern',
    ];

    /**
     * Load and parse the YAML configuration file.
     *
     * @param string $configFile The path to the YAML configuration file.
     *
     * @return array<string, mixed> The parsed configuration as an associative array.
     *
     * @throws InvalidArgumentException If the configuration is invalid.
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

        self::validateConfig($config);

        return $config;
    }

    /**
     * Validate the configuration to ensure it adheres to expected formats.
     *
     * @param array<string, mixed> $config The parsed configuration array.
     *
     * @return void
     *
     * @throws InvalidArgumentException If the configuration is invalid.
     */
    private static function validateConfig(array $config): void
    {
        if (!isset($config['rules']) || !is_array($config['rules'])) {
            throw new InvalidArgumentException("The 'rules' section is missing or invalid in the configuration.");
        }

        foreach ($config['rules'] as $rule) {
            if (!isset($rule['name']) || !is_string($rule['name'])) {
                throw new InvalidArgumentException("Each rule must have a 'name' defined as a string.");
            }

            if (!isset($rule['rule']) || !in_array($rule['rule'], self::$supportedRules, true)) {
                throw new InvalidArgumentException("Unsupported rule type '{$rule['rule']}' found in the configuration.");
            }

            if (!isset($rule['matcher']) || !in_array($rule['matcher'], self::$supportedMatchers, true)) {
                throw new InvalidArgumentException("Unsupported matcher '{$rule['matcher']}' found in the configuration.");
            }

            if (!isset($rule['values']) || !is_array($rule['values'])) {
                throw new InvalidArgumentException("Each rule must have a 'values' array defined.");
            }

            if (!isset($rule['message']) || !is_string($rule['message'])) {
                throw new InvalidArgumentException("Each rule must have a 'message' defined as a string.");
            }
        }

        if (isset($config['exclude']) && !is_array($config['exclude'])) {
            throw new InvalidArgumentException("The 'exclude' section must be an array if defined.");
        }
    }
}
