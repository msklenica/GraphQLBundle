<?php

declare(strict_types=1);

namespace Youshido\GraphQLBundle\Execution\Payload;

use Symfony\Component\HttpFoundation\Request;
use Youshido\GraphQLBundle\Config\Constants;

/**
 * Parses GraphQL request payloads from HTTP requests.
 *
 * Handles both single and batch queries in multiple formats:
 * - application/graphql: Raw GraphQL query
 * - application/json: Single query or batch of queries
 * - URL query parameters: Query and variables
 */
class PayloadParser
{
    /**
     * Maximum allowed payload size in bytes (10MB default).
     * Can be configured to prevent denial-of-service attacks.
     */
    private const MAX_PAYLOAD_SIZE = 10 * 1024 * 1024; // 10MB

    public function __construct(private readonly Request $request)
    {
    }

    /**
     * Parse the request payload into an array of queries and metadata.
     *
     * @return array{queries: array<array{query: string|null, variables: array}>, isMultiQueryRequest: bool}
     *         Returns tuple with array of queries and flag indicating batch request
     * @throws \InvalidArgumentException If payload exceeds maximum size limit
     */
    public function parse(): array
    {
        $query = $this->request->query->get('query');
        $variables = $this->parseVariables($this->request->query->get('variables') ?? []);

        $content = $this->request->getContent();

        // Validate payload size to prevent denial-of-service attacks
        if (!empty($content) && strlen($content) > self::MAX_PAYLOAD_SIZE) {
            throw new \InvalidArgumentException(sprintf(
                'Request payload exceeds maximum allowed size of %d bytes',
                self::MAX_PAYLOAD_SIZE
            ));
        }

        if (!empty($content)) {
            return $this->parseRequestBody($content, $query, $variables);
        }

        return [
            'queries' => [
                [
                    'query' => $query,
                    'variables' => $variables,
                ],
            ],
            'isMultiQueryRequest' => false,
        ];
    }

    /**
     * Parse variables from request parameters (handles both string and array formats).
     *
     * @param mixed $variables Raw variables from request
     * @return array Parsed variables array
     */
    private function parseVariables(mixed $variables): array
    {
        if (is_string($variables)) {
            $decoded = json_decode($variables, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($variables) ? $variables : [];
    }

    /**
     * Parse the request body based on Content-Type.
     *
     * @param string $content Request body content
     * @param string|null $fallbackQuery Query from URL parameters
     * @param array $fallbackVariables Variables from URL parameters
     * @return array{queries: array<array{query: string|null, variables: array}>, isMultiQueryRequest: bool}
     */
    private function parseRequestBody(string $content, ?string $fallbackQuery, array $fallbackVariables): array
    {
        $contentType = $this->request->headers->get(Constants::HEADER_CONTENT_TYPE, '');

        if (str_starts_with((string) $contentType, Constants::CONTENT_TYPE_GRAPHQL)) {
            return [
                'queries' => [
                    [
                        'query' => $content,
                        'variables' => [],
                    ],
                ],
                'isMultiQueryRequest' => false,
            ];
        }

        return $this->parseJsonBody($content, $fallbackQuery, $fallbackVariables);
    }

    /**
     * Parse JSON request body (handles single and batch queries).
     *
     * @param string $content JSON request body
     * @param string|null $fallbackQuery Query from URL parameters
     * @param array $fallbackVariables Variables from URL parameters
     * @return array{queries: array<array{query: string|null, variables: array}>, isMultiQueryRequest: bool}
     */
    private function parseJsonBody(string $content, ?string $fallbackQuery, array $fallbackVariables): array
    {
        $params = json_decode($content, true);

        if (!is_array($params)) {
            return [
                'queries' => [
                    [
                        'query' => $fallbackQuery,
                        'variables' => $fallbackVariables,
                    ],
                ],
                'isMultiQueryRequest' => false,
            ];
        }

        // Check if this is a batch query (array of queries) or single query
        $isMultiQueryRequest = isset($params[0]) && is_array($params[0]);
        if (!$isMultiQueryRequest) {
            $params = [$params];
        }

        return [
            'queries' => $this->buildQueryArray($params, $fallbackQuery, $fallbackVariables),
            'isMultiQueryRequest' => $isMultiQueryRequest,
        ];
    }

    /**
     * Build query array from parsed parameters.
     *
     * @param array $params Array of query parameters
     * @param string|null $fallbackQuery Query from URL parameters
     * @param array $fallbackVariables Variables from URL parameters
     * @return array<array{query: string|null, variables: array}> Array of query definitions
     */
    private function buildQueryArray(array $params, ?string $fallbackQuery, array $fallbackVariables): array
    {
        $queries = [];
        $isFirstQuery = true;

        foreach ($params as $queryParams) {
            if (!is_array($queryParams)) {
                continue;
            }

            // Use query from params or fall back to URL parameter
            $query = $queryParams['query'] ?? $fallbackQuery;

            // Parse variables: use provided variables or fall back to URL parameters for first query only
            $variables = [];
            if (isset($queryParams['variables'])) {
                $variables = $this->parseVariables($queryParams['variables']);
            } elseif ($isFirstQuery) {
                $variables = $fallbackVariables;
            }

            $queries[] = [
                'query' => $query,
                'variables' => $variables,
            ];

            $isFirstQuery = false;
        }

        return $queries;
    }
}

