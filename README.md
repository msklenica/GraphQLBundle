# Symfony GraphQL Bundle

A robust [Symfony 7.4+](https://symfony.com/) bundle integrating a pure [PHP GraphQL Server](http://github.com/youshido/graphql/) implementation.

## Features

- ✅ **Full RFC Specification Compliance** - Complete GraphQL specification implementation
- ✅ **Symfony 7.4+ Support** - Modern PHP 8.4+ with strict typing
- ✅ **Security First** - Built-in field and operation-level access control
- ✅ **Performance Optimized** - DoS protection with payload size limits
- ✅ **Batch Query Support** - Handle single and batch GraphQL requests efficiently
- ✅ **Event-Driven** - Pre/post-resolve events for custom logic
- ✅ **Container Integration** - Full Symfony container awareness for fields
- ✅ **Well-Tested** - 53+ unit and integration tests with excellent coverage

## Installation

### Prerequisites
- PHP 8.4+
- Symfony 7.4+
- Composer

### Install the bundle

```bash
composer require youshido/graphql-bundle
```

### Register the bundle

In your `config/bundles.php`:
```php
return [
    // ... other bundles
    Youshido\GraphQLBundle\GraphQLBundle::class => ['all' => true],
];
```

### Configure routing

In your `config/routes.yaml`:
```yaml
graphql:
    resource: "@GraphQLBundle/Controller/"
```

## Quick Start

### 1. Create a GraphQL Schema

```php
namespace App\GraphQL;

use Youshido\GraphQL\Schema\AbstractSchema;
use Youshido\GraphQL\Type\ListType;
use Youshido\GraphQL\Type\NonNullType;
use Youshido\GraphQL\Type\StringType;

class AppSchema extends AbstractSchema
{
    public function build($config): void
    {
        $config->query(new RootQuery());
    }
}
```

### 2. Configure the schema in `config/packages/graphql.yaml`

```yaml
graphql:
    schema_class: App\GraphQL\AppSchema
    response:
        json_pretty: true
        headers:
            'Access-Control-Allow-Origin': '*'
    security:
        guard:
            field: false
            operation: false
```

### 3. Test your endpoint

Access `http://localhost:8000/graphql` or use curl:

```bash
curl http://localhost:8000/graphql \
  -H "Content-Type: application/json" \
  -d '{"query":"{ hello }"}'
```

## Core Features

### Security: Field & Operation Guards

Control access at the field and operation level:

```yaml
graphql:
    security:
        guard:
            field: true        # Enable field-level security
            operation: true    # Enable operation-level security
        black_list: ['admin']  # Block specific operations
        white_list: ['public'] # Allow only specific operations
```

Implement a security voter:

```php
use Youshido\GraphQLBundle\Security\Manager\SecurityManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class GraphQLVoter extends Voter
{
    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, [
            SecurityManagerInterface::RESOLVE_FIELD_ATTRIBUTE,
            SecurityManagerInterface::RESOLVE_ROOT_OPERATION_ATTRIBUTE,
        ]);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if ($attribute === SecurityManagerInterface::RESOLVE_FIELD_ATTRIBUTE) {
            // Field-level access control
            return true; // Allow or deny based on your logic
        }
        
        return true;
    }
}
```

### Container-Aware Fields

Access Symfony services directly in field resolvers:

```php
use Youshido\GraphQLBundle\Field\AbstractContainerAwareField;
use Youshido\GraphQL\Type\StringType;

class UserField extends AbstractContainerAwareField
{
    public function getType(): StringType
    {
        return new StringType();
    }

    public function resolve($value, array $args): string
    {
        // Access container services
        $logger = $this->container->get('logger');
        $logger->info('Resolving user field');
        
        return 'user_data';
    }

    public function getName(): string
    {
        return 'user';
    }
}
```

### Service Method Resolvers

Use Symfony services as field resolvers:

```php
use Youshido\GraphQL\Field\Field;
use Youshido\GraphQL\Type\StringType;

$query->addField(new Field([
    'name' => 'cache_dir',
    'type' => new StringType(),
    'resolve' => ['@my_resolver_service', 'getCacheDir'] // Call service method
]));
```

### Event Hooks

Monitor and transform GraphQL resolution:

```php
use Youshido\GraphQLBundle\Event\ResolveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GraphQLResolveSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'graphql.pre_resolve'  => 'onPreResolve',
            'graphql.post_resolve' => 'onPostResolve',
        ];
    }

    public function onPreResolve(ResolveEvent $event): void
    {
        // Implement caching, logging, or validation
    }

    public function onPostResolve(ResolveEvent $event): void
    {
        // Transform results, log queries, etc.
    }
}
```

Register in `config/services.yaml`:
```yaml
App\Subscriber\GraphQLResolveSubscriber:
    tags:
        - { name: 'graphql.event_subscriber' }
```

## Configuration Reference

```yaml
graphql:
    # Your main GraphQL schema class
    schema_class: App\GraphQL\AppSchema
    
    # Optional: Service ID for the schema (alternative to schema_class)
    schema_service: ~
    
    # Optional: Logger service ID for GraphQL queries
    logger: ~
    
    # Maximum complexity threshold (0 = unlimited)
    max_complexity: 0
    
    response:
        # Pretty-print JSON responses
        json_pretty: false
        
        # Custom response headers
        headers:
            'Content-Type': 'application/json'
            'Access-Control-Allow-Origin': '*'
    
    security:
        guard:
            field: false        # Enable field-level security
            operation: false    # Enable operation-level security
        
        # Blacklist operations (blocks specified operations)
        black_list: []
        
        # Whitelist operations (allows only specified operations)
        white_list: []
```

## Performance & Security

### Payload Size Limits

The bundle enforces a 10MB maximum payload size by default to prevent DoS attacks:

```php
// Thrown as InvalidArgumentException with message containing max size
$parser = new PayloadParser($request);
$result = $parser->parse();
```

### Best Practices

1. **Enable field security for sensitive data** - Use security voters for fine-grained control
2. **Implement rate limiting** - Add Symfony rate limiters to your routes
3. **Monitor complexity** - Set `max_complexity` threshold to prevent expensive queries
4. **Use HTTPS in production** - Always encrypt GraphQL endpoints in production
5. **Validate input** - Leverage GraphQL schema validation for type safety

## Recent Improvements (v2.x)

✨ **Security Fixes**
- Fixed batch query state mutation preventing variable leakage between queries
- Improved error handling with HTTP 500 for configuration errors
- Enhanced exception messages with field/operation context

✨ **Performance & DoS Protection**
- Added configurable payload size limits (10MB default)
- Optimized variable parsing (eliminated duplication)

✨ **Code Quality**
- Created Constants class for centralized configuration
- Fixed parameter naming inconsistencies
- Removed legacy Symfony 4.2 compatibility code

See [CHANGELOG.md](CHANGELOG.md) for all improvements.

## Testing

Run the test suite:

```bash
docker compose up -d
docker exec app composer install
docker exec -w /var/www/html app ./vendor/bin/phpunit
```

The bundle includes 53+ tests covering:
- Query and batch query parsing
- Security voters and guards
- Field and operation resolution
- Error handling and edge cases

## Documentation

- [Official GraphQL Specification](https://spec.graphql.org/)
- [99designs/graphql Documentation](https://github.com/99designs/graphql-php)
- [Symfony Documentation](https://symfony.com/doc/)

## Contributing

Contributions are welcome! Please ensure:
- All tests pass: `./vendor/bin/phpunit`
- Code follows PSR-12 standards
- New features include tests

## License

See the LICENSE file in the repository.

## Support

For issues, questions, or feature requests, please visit the [GitHub repository](https://github.com/youshido/graphql-bundle).

