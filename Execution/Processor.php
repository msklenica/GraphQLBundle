<?php

declare(strict_types=1);

namespace Youshido\GraphQLBundle\Execution;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Youshido\GraphQL\Execution\Context\ExecutionContextInterface;
use Youshido\GraphQL\Execution\Processor as BaseProcessor;
use Youshido\GraphQL\Execution\ResolveInfo;
use Youshido\GraphQL\Field\AbstractField;
use Youshido\GraphQL\Field\Field;
use Youshido\GraphQL\Field\FieldInterface;
use Youshido\GraphQL\Parser\Ast\Field as AstField;
use Youshido\GraphQL\Parser\Ast\Interfaces\FieldInterface as AstFieldInterface;
use Youshido\GraphQL\Parser\Ast\Query;
use Youshido\GraphQL\Parser\Ast\Query as AstQuery;
use Youshido\GraphQL\Type\TypeService;
use Youshido\GraphQL\Exception\ResolveException;
use Youshido\GraphQLBundle\Event\ResolveEvent;
use Youshido\GraphQLBundle\Security\Manager\SecurityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Processor extends BaseProcessor
{
    private ?LoggerInterface $logger = null;

    private ?SecurityManagerInterface $securityManager = null;

    public function __construct(ExecutionContextInterface $executionContext, private readonly EventDispatcherInterface $eventDispatcher)
    {
        $this->executionContext = $executionContext;

        parent::__construct($executionContext->getSchema());
    }

    public function setSecurityManager(SecurityManagerInterface $securityManager): self
    {
        $this->securityManager = $securityManager;

        return $this;
    }

    /**
     * Process a GraphQL query payload with optional variables.
     *
     * Main entry point for executing GraphQL queries. Logs the query if a logger
     * is configured, then delegates to the parent processor for execution.
     *
     * @param mixed $payload The GraphQL query string or query document
     * @param array $variables Variables to pass to the query
     * @param array $reducers Optional reducers (passed to parent processor)
     */
    public function processPayload(mixed $payload, array $variables = [], array $reducers = []): void
    {
        if ($this->logger) {
            $this->logger->debug(sprintf('GraphQL query: %s', $payload), $variables);
        }

        parent::processPayload($payload, $variables);
    }

    /**
     * Resolve a GraphQL query operation with security validation.
     *
     * Checks operation-level security (RESOLVE_ROOT_OPERATION) before executing
     * the query. This allows blocking or allowing entire operations based on
     * authentication/authorization rules.
     *
     * @param Query $query The query operation to execute
     * @return mixed The query execution result
     *
     * @throws AccessDeniedException If operation-level security check fails
     */
    protected function resolveQuery(Query $query): mixed
    {
        $this->assertClientHasOperationAccess($query);

        return parent::resolveQuery($query);
    }

    private function dispatchResolveEvent(ResolveEvent $event, string $name): void
    {
        // Symfony 7.4+ uses dispatch(Event $event, string $eventName)
        $this->eventDispatcher->dispatch($event, $name);
    }

    /**
     * Resolve a GraphQL field with security checks, events, and service resolution.
     *
     * This is the core field resolution method. It performs the following steps:
     * 1. Parses field arguments from the AST
     * 2. Dispatches pre-resolve event for monitoring/caching
     * 3. Validates field access via security manager
     * 4. Sets container on fields that need it (ContainerAwareInterface)
     * 5. Resolves the field value using:
     *    - Service-based resolver (@service_name::method)
     *    - Callable resolver (closure/function)
     *    - Property accessor (direct property access)
     *    - Field's resolve method
     * 6. Dispatches post-resolve event for transformation/logging
     *
     * @param FieldInterface $field The field being resolved
     * @param AstFieldInterface $ast The AST representation of the field
     * @param mixed $parentValue The parent object/value context
     * @return mixed The resolved field value
     *
     * @throws ResolveException If a service reference is invalid or method doesn't exist
     * @throws AccessDeniedException If security checks fail
     */
    protected function doResolve(FieldInterface $field, AstFieldInterface $ast, mixed $parentValue = null): mixed
    {
        /** @var AstQuery|AstField $ast */
        $arguments = $this->parseArgumentsValues($field, $ast);
        $astFields = $ast instanceof AstQuery ? $ast->getFields() : [];

        $event = new ResolveEvent($field, $astFields);
        $this->dispatchResolveEvent($event, 'graphql.pre_resolve');

        $resolveInfo = $this->createResolveInfo($field, $astFields);
        $this->assertClientHasFieldAccess($resolveInfo);

        if (in_array(ContainerAwareInterface::class, class_implements($field) ?: [])) {
            /** @var ContainerAwareInterface $field */
            $field->setContainer($this->executionContext->getContainer()->getSymfonyContainer());
        }

        if (($field instanceof AbstractField) && ($resolveFunc = $field->getConfig()->getResolveFunction())) {
            if ($this->isServiceReference($resolveFunc)) {
                $service = substr((string) $resolveFunc[0], 1);
                $method  = $resolveFunc[1];
                if (!$this->executionContext->getContainer()->has($service)) {
                    throw new ResolveException(sprintf('Resolve service "%s" not found for field "%s"', $service, $field->getName()));
                }

                $serviceInstance = $this->executionContext->getContainer()->get($service);

                if (!method_exists($serviceInstance, $method)) {
                    throw new ResolveException(sprintf('Resolve method "%s" not found in "%s" service for field "%s"', $method, $service, $field->getName()));
                }

                $result = $serviceInstance->$method($parentValue, $arguments, $resolveInfo);
            } else {
                $result = $resolveFunc($parentValue, $arguments, $resolveInfo);
            }
        } elseif ($field instanceof Field) {
            $result = TypeService::getPropertyValue($parentValue, $field->getName());
        } else {
            $result = $field->resolve($parentValue, $arguments, $resolveInfo);
        }

        $event = new ResolveEvent($field, $astFields, $result);
        $this->dispatchResolveEvent($event, 'graphql.post_resolve');
        return $event->getResolvedValue();
    }

    /**
     * Validate that the client has access to execute the GraphQL operation.
     *
     * Checks if operation-level security is enabled and if the current user
     * is granted permission to resolve this operation via the security manager.
     *
     * @param Query $query The query operation to validate
     * @throws AccessDeniedException If security check fails and is enabled
     */
    private function assertClientHasOperationAccess(Query $query): void
    {
        if ($this->securityManager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_ROOT_OPERATION_ATTRIBUTE)
            && !$this->securityManager->isGrantedToOperationResolve($query)
        ) {
            throw $this->securityManager->createNewOperationAccessDeniedException($query);
        }
    }

    /**
     * Validate that the client has access to resolve the GraphQL field.
     *
     * Checks if field-level security is enabled and if the current user
     * is granted permission to resolve this field via the security manager.
     *
     * @param ResolveInfo $resolveInfo Information about the field being resolved
     * @throws AccessDeniedException If security check fails and is enabled
     */
    private function assertClientHasFieldAccess(ResolveInfo $resolveInfo): void
    {
        if ($this->securityManager->isSecurityEnabledFor(SecurityManagerInterface::RESOLVE_FIELD_ATTRIBUTE)
            && !$this->securityManager->isGrantedToFieldResolve($resolveInfo)
        ) {
            throw $this->securityManager->createNewFieldAccessDeniedException($resolveInfo);
        }
    }

    /**
     * Check if a resolver function is a service reference.
     *
     * Service references use the syntax ['@service_name', 'methodName'] to delegate
     * field resolution to a registered service container service.
     *
     * @param mixed $resolveFunc The resolver function to check
     * @return bool True if this is a service reference, false otherwise
     */
    private function isServiceReference(mixed $resolveFunc): bool
    {
        return is_array($resolveFunc) && count($resolveFunc) === 2 && str_starts_with((string) $resolveFunc[0], '@');
    }

    /**
     * Set an optional logger instance for query logging.
     *
     * If a logger is set, GraphQL queries will be logged at DEBUG level
     * along with their variables for debugging and monitoring.
     *
     * @param LoggerInterface|null $logger Optional PSR-3 logger instance
     */
    public function setLogger(?LoggerInterface $logger = null): void
    {
        $this->logger = $logger;
    }
}
