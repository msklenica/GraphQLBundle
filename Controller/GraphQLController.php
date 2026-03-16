<?php

declare(strict_types=1);

/**
 * Date: 25.11.15
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Youshido\GraphQLBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Youshido\GraphQLBundle\Config\Constants;
use Youshido\GraphQLBundle\Exception\UnableToInitializeSchemaServiceException;
use Youshido\GraphQLBundle\Execution\Payload\PayloadParser;
use Youshido\GraphQLBundle\Execution\Processor;

class GraphQLController extends AbstractController
{
    public function __construct(protected ParameterBagInterface $params)
    {
    }

    /**
     * @throws \Exception
     *
     * @return JsonResponse
     */
    public function defaultAction(): JsonResponse
    {
        try {
            $this->initializeSchemaService();
        } catch (UnableToInitializeSchemaServiceException) {
            return new JsonResponse(
                [['message' => 'An error occurred while processing your request']],
                500,
                $this->getResponseHeaders()
            );
        }

        if ($this->container->get(Constants::SERVICE_REQUEST_STACK)->getCurrentRequest()->getMethod() === Constants::HTTP_METHOD_OPTIONS) {
            return $this->createEmptyResponse();
        }

        [$queries, $isMultiQueryRequest] = $this->getPayload();

        $queryResponses = array_map(fn($queryData) => $this->executeQuery($queryData['query'], $queryData['variables']), $queries);

        $response = new JsonResponse($isMultiQueryRequest ? $queryResponses : $queryResponses[0], 200, $this->getParam(Constants::PARAM_RESPONSE_HEADERS));

        if ($this->getParam(Constants::PARAM_RESPONSE_JSON_PRETTY)) {
            $response->setEncodingOptions($response->getEncodingOptions() | JSON_PRETTY_PRINT);
        }

        return $response;
    }

    protected function createEmptyResponse(): JsonResponse
    {
        return new JsonResponse([], 200, $this->getResponseHeaders());
    }

    protected function executeQuery(string $query, array $variables): array
    {
        /** @var Processor $processor */
        $processor = $this->container->get(Constants::SERVICE_GRAPHQL_PROCESSOR);
        $processor->processPayload($query, $variables);

        return $processor->getResponseData();
    }

    /**
     * Parse the GraphQL request payload into queries and metadata.
     *
     * Supports multiple formats:
     * - application/graphql: Raw GraphQL query
     * - application/json: Single or batch queries
     * - URL parameters: Query and variables
     *
     * @return array{0: array<array{query: string|null, variables: array}>, 1: bool}
     *         Tuple of [queries, isMultiQueryRequest]
     *
     * @throws \Exception
     */
    protected function getPayload(): array
    {
        $request = $this->container->get(Constants::SERVICE_REQUEST_STACK)->getCurrentRequest();
        $parser = new PayloadParser($request);
        $result = $parser->parse();

        return [$result['queries'], $result['isMultiQueryRequest']];
    }

    /**
     * @throws UnableToInitializeSchemaServiceException
     */
    protected function initializeSchemaService(): void
    {
        if ($this->container->initialized(Constants::SERVICE_GRAPHQL_SCHEMA)) {
            return;
        }

        $this->container->set(Constants::SERVICE_GRAPHQL_SCHEMA, $this->makeSchemaService());
    }

    /**
     * @return object
     *
     * @throws UnableToInitializeSchemaServiceException
     */
    protected function makeSchemaService(): object
    {
        if ($this->getSchemaService() && $this->container->has($this->getSchemaService())) {
            return $this->container->get($this->getSchemaService());
        }

        $schemaClass = $this->getSchemaClass();
        if (!$schemaClass || !class_exists($schemaClass)) {
            throw new UnableToInitializeSchemaServiceException();
        }

        if ($this->container->has($schemaClass)) {
            return $this->container->get($schemaClass);
        }

        return new $schemaClass();
    }

    protected function getSchemaClass(): ?string
    {
        return $this->getParam(Constants::PARAM_SCHEMA_CLASS);
    }

    protected function getSchemaService(): ?string
    {
        $serviceName = $this->getParam(Constants::PARAM_SCHEMA_SERVICE);

        if (str_starts_with($serviceName ?: '', '@')) {
            return substr($serviceName, 1);
        }

        return $serviceName;
    }

    protected function getResponseHeaders(): array
    {
        return $this->getParam(Constants::PARAM_RESPONSE_HEADERS);
    }

    protected function getParam(string $name): array|bool|string|int|float|\UnitEnum|null
    {
        return $this->params->get($name);
    }
}
