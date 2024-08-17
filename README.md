# Lynter

Lynter is a PHP code analysis tool focused on restricting specific usages within your codebase. It allows you to enforce custom rules, such as restricting certain functions, variables, or classes, ensuring that your code adheres to specific standards. Lynter can be used in continuous integration pipelines or as a pre-commit hook in your Git workflow.

## Table of Contents

1. [Installation](#installation)
2. [Usage](#usage)
3. [Configuration](#configuration)
4. [Built-in Rules](#built-in-rules)
5. [Excluding Files and Directories](#excluding-files-and-directories)
6. [Parallel Execution](#parallel-execution)
7. [Output Formats](#output-formats)
8. [Examples](#examples)
9. [Testing](#testing)
10. [Contributing](#contributing)
11. [License](#license)

## Installation

To install Lynter, use Composer:

```bash
composer require --dev yigitcukuren/lynter
```

Make sure to include Lynter in your `composer.json` file's `require-dev` section for development dependencies.

## Usage

Lynter can be run from the command line to analyze PHP files or directories. Here's the basic syntax:

```bash
./vendor/bin/lynter analyze [options] <paths>
```

### Options

- `--config=<file>`: Specify the path to the YAML configuration file (default: `lynter.yml`).
- `--output=<format>`: Specify the output format (`raw`, `json`). Default is `raw`.
- `--parallel=<number>`: Specify the number of parallel processes to use for analysis.

### Example Usage

```bash
./vendor/bin/lynter analyze src --config=lynter.yml --output=raw --parallel=4
```

This command will analyze the `src` directory using the configuration file `lynter.yml`, output results in raw format, and utilize 4 parallel processes.

## Configuration

Lynter is configured using a YAML file. The default configuration file is `lynter.yml`. You can specify custom rules, messages, and exclusions in this file.

### Example Configuration

```yaml
rules:
  restrictFunction:
    functions:
      - eval
      - exec
    message: "Function '{name}' is not allowed."
  
  restrictVariable:
    variables:
      - $_GET
      - $_POST
    message: "Variables '{name}' is restricted."
  
  restrictClass:
    classes:
      - MyRestrictedClass
      - LegacyClass
    message: "Instantiation of '{name}' is not allowed."

exclude:
  - vendor/
  - tests/
```

### Configuration Details

- **rules**: Define the rules for restricting functions, variables, and classes. Each rule type (`restrictFunction`, `restrictVariable`, `restrictClass`) accepts an array of names to restrict and a custom message template.
- **exclude**: Specify directories or files to exclude from analysis.

## Built-in Rules

Lynter comes with the following built-in rules:

### 1. `restrictFunction`
Restrict the usage of specific functions.

#### Example
```yaml
rules:
  restrictFunction:
    functions:
      - eval
      - exec
    message: "Function '{name}' is not allowed."
```

### 2. `restrictVariable`
Restrict the usage of specific global variables.

#### Example
```yaml
rules:
  restrictVariable:
    variables:
      - $_GET
      - $_POST
    message: "Variables '{name}' is restricted."
```

### 3. `restrictClass`
Restrict the instantiation of specific classes.

#### Example
```yaml
rules:
  restrictClass:
    classes:
      - MyRestrictedClass
      - LegacyClass
    message: "Instantiation of '{name}' is not allowed."
```

## Excluding Files and Directories

You can exclude specific files or directories from being analyzed by using the `exclude` option in your configuration file.

### Example

```yaml
exclude:
  - vendor/
  - tests/
  - src/legacy/
```

## Parallel Execution

Lynter supports parallel execution to speed up the analysis of large codebases. You can specify the number of parallel processes using the `--parallel` option.

### Example

```bash
./vendor/bin/lynter analyze src --parallel=4
```

This will run Lynter with 4 parallel processes.

## Output Formats

Lynter supports two output formats: `raw` and `json`.

### Raw Output

The default output format is `raw`, which provides a human-readable summary of the issues.

### JSON Output

For machine-readable output, use the `json` format.

```bash
./vendor/bin/lynter analyze src --output=json
```

## Examples

### Example 1: Analyzing a Directory

```bash
./vendor/bin/lynter analyze src --config=lynter.yml
```

### Example 2: Analyzing Multiple Files

```bash
./vendor/bin/lynter analyze src/Example.php src/AnotherExample.php --config=lynter.yml
```

### Example 3: Running in Parallel

```bash
./vendor/bin/lynter analyze src --config=lynter.yml --parallel=4
```

### Example 4: JSON Output

```bash
./vendor/bin/lynter analyze src --output=json
```

## Testing

Lynter includes a set of PHPUnit tests to ensure the integrity of the tool.

### Running Tests

To run the tests, use the following command:

```bash
./vendor/bin/phpunit
```

This will execute the test suite and provide feedback on any issues.

## Contributing

Contributions are welcome! Please fork this repository, make your changes, and submit a pull request.

### Steps to Contribute

1. Fork the repository.
2. Create a new branch for your feature or bugfix.
3. Write tests for your changes.
4. Ensure all tests pass.
5. Submit a pull request.

### Code Style

This project follows PSR-12 coding standards. Please ensure your code adheres to these guidelines.

## License

Lynter is open-source software licensed under the MIT license. See the `LICENSE` file for more information.
