# AGENTS.md - GraphQL Bundle Development Guide

This document provides guidance for agentic coding systems operating on the GraphQLBundle repository.

## Build, Lint, and Test Commands

### Docker Setup (Recommended)
All testing and development should be done via Docker container to ensure consistency.

**Initialize Docker environment:**
```bash
docker compose up -d                              # Start containers
docker exec app git config --global --add safe.directory /var/www/html  # Fix git permissions
docker exec -w /var/www/html app composer install # Install dependencies
```

### Core Commands (via Docker)
- **Run all tests**: `docker exec -w /var/www/html app ./vendor/bin/phpunit`
- **Run single test**: `docker exec -w /var/www/html app ./vendor/bin/phpunit --filter testDefaultConfigIsUsed`
- **Run tests in a directory**: `docker exec -w /var/www/html app ./vendor/bin/phpunit Tests/DependencyInjection/`
- **Install dependencies**: `docker exec -w /var/www/html app composer install`
- **Update dependencies**: `docker exec -w /var/www/html app composer update`
- **Code modernization**: `docker exec -w /var/www/html app ./vendor/bin/rector process`
- **Code modernization dry-run**: `docker exec -w /var/www/html app ./vendor/bin/rector process --dry-run`

**Cleanup:**
```bash
docker compose down    # Stop and remove containers
```

### PHPUnit Configuration
- Config file: `phpunit.xml.dist`
- Test bootstrap: `vendor/autoload.php`
- Test directory: `Tests/`
- Coverage excluded: `Resources/`, `Tests/`, `vendor/`
- Docker container: `app`
- Working directory in container: `/var/www/html`

### Local Development Setup
For local development, the `99designs/graphql` package is configured to use the local path repository at `../GraphQL-php`. This allows testing the bundle with changes to the GraphQL library before pushing to the remote repository.

**Configuration details:**
- `docker-compose.yml`: Mounts both GraphQLBundle (`/var/www/html`) and GraphQL-php (`/var/www/GraphQL-php`)
- `composer.json`: Uses a path repository pointing to `/var/www/GraphQL-php`
- Version constraint: `@dev` to accept development versions from the path repository

When the GraphQL-php library is updated, the changes will be automatically reflected in the bundle's tests. To revert to the remote version, update the repository and version constraint in `composer.json`.

## Project Overview

**Type**: Symfony Bundle (PHP 8.4+)
**Purpose**: GraphQL Server integration for Symfony Framework
**Namespace**: `Youshido\GraphQLBundle`
**Dependencies**: Symfony 7.4, 99designs/graphql, PHPUnit 9.6

## Code Style Guidelines

### Formatting & Structure
- **Language**: PHP 8.4 with strict typing
- **Indentation**: 4 spaces (PSR-12)
- **Line Length**: No hard limit, but keep reasonable (~100-120 chars)
- **File Header**: Include author and date block comment (see examples below)

### Type Declarations
- **Always use strict_types**: Add `declare(strict_types=1);` after PHP opening tag
- **Type hints**: Use for all parameters and return types (no mixed/null without union)
- **Union types**: Use `|` syntax (e.g., `array|bool|string|int`)
- **Return types**: Always specify, use `void` if no return value

Example:
```php
<?php
declare(strict_types=1);

namespace Youshido\GraphQLBundle\Controller;

public function getParam(string $name): array|bool|string|int|float|\UnitEnum|null
```

### Naming Conventions
- **Classes**: PascalCase (e.g., `GraphQLController`, `AbstractContainerAwareField`)
- **Methods**: camelCase, prefixed with verb when appropriate (e.g., `executeQuery`, `getPayload`, `initializeSchemaService`)
- **Properties**: camelCase with visibility keyword (e.g., `protected ParameterBagInterface $params`)
- **Constants**: UPPER_SNAKE_CASE
- **File names**: Match class name (e.g., `GraphQLController.php` for class `GraphQLController`)

### Import Statements
- **Order**: Group imports logically
  1. Symfony core components first
  2. Bundle-specific components
  3. External vendor packages
- **Use statements**: One per line, alphabetically sorted within groups
- **Never use**: Wildcard imports (`use Foo\*;`)

Example:
```php
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Youshido\GraphQLBundle\Exception\UnableToInitializeSchemaServiceException;
use Youshido\GraphQLBundle\Execution\Processor;
```

### Error Handling
- **Exceptions**: Create specific exception classes extending `\Exception`
- **Exception placement**: Store in `Exception/` directory with descriptive names
- **Catch specific exceptions**: Use match expressions for known exception types
- **Document throws**: Use `@throws` in PHPDoc for all exceptions

Example:
```php
try {
    $this->initializeSchemaService();
} catch (UnableToInitializeSchemaServiceException) {
    return new JsonResponse([['message' => 'Schema class does not exist']]);
}
```

### Classes & Methods
- **Visibility**: Always explicit (`public`, `protected`, `private`)
- **Constructor promotion**: Use PHP 8.0+ constructor property promotion
- **Traits**: Use for shared functionality (e.g., `ContainerAwareTrait`)
- **Inheritance**: Extend base classes when appropriate
- **PHPDoc blocks**: Include for complex methods with `@param`, `@return`, `@throws`

Example:
```php
class GraphQLController extends AbstractController
{
    public function __construct(protected ParameterBagInterface $params)
    {
    }
    
    protected function executeQuery($query, $variables): array
    {
        /** @var Processor $processor */
        $processor = $this->container->get('graphql.processor');
        $processor->processPayload($query, $variables);
        return $processor->getResponseData();
    }
}
```

### String Operations
- **Use modern syntax**: `str_starts_with()`, `str_ends_with()` (PHP 8.0+)
- **Ternary shorthand**: Use `??` for null coalescing
- **Arrow functions**: Use for simple callbacks in array_map, array_filter, etc.

Example:
```php
$queryResponses = array_map(fn($queryData) => $this->executeQuery($queryData['query'], $queryData['variables']), $queries);
$variables = is_string($variables) ? json_decode($variables, true) : $variables;
```

### Testing
- **Framework**: PHPUnit 9.6
- **Base class**: Extend `PHPUnit\Framework\TestCase`
- **Naming**: `*Test` suffix (e.g., `GraphQLExtensionTest`)
- **Methods**: `test*` prefix (e.g., `testDefaultConfigIsUsed()`)
- **Assertions**: Use modern assertions (`assertEquals`, `assertTrue`, `assertNull`)
- **Setup/Teardown**: Use `setUp()` and `tearDown()` methods when needed

## Directory Structure
```
Command/                  - CLI commands
Config/                   - Configuration and rules
Controller/               - HTTP controllers
DependencyInjection/      - DI extension and configuration
Event/                    - Event classes and subscribers
Exception/                - Custom exception classes
Execution/                - Query execution logic
Field/                    - GraphQL field definitions
Resources/                - Templates, configs, assets
Security/                 - Security voters and managers
Tests/                    - PHPUnit test suites
```

## Git Workflow
- Use descriptive commit messages
- Run tests before committing: `./vendor/bin/phpunit`
- Use Rector for code modernization: `./vendor/bin/rector process --dry-run` first
- Keep commits atomic and focused
