<?php

declare(strict_types=1);

namespace Youshido\GraphQLBundle\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Youshido\GraphQLBundle\Config\Constants;
use Youshido\GraphQLBundle\Controller\GraphQLController;

/**
 * Integration tests for GraphQLController.
 *
 * Tests the complete request/response cycle including:
 * - Error handling for schema initialization
 * - Response header configuration
 */
class GraphQLControllerTest extends TestCase
{
    public function testSchemaInitializationExceptionReturns500WithoutExposingSchemaName(): void
    {
        // When schema cannot be initialized, should return 500 without exposing internal schema class name
        $requestStack = new RequestStack();
        $container = new Container();
        
        // Request is not pushed to stack, so it returns null - schema will fail to initialize
        $parameterBag = new ParameterBag([
            Constants::PARAM_RESPONSE_HEADERS => [
                'Content-Type' => 'application/json',
            ],
            Constants::PARAM_RESPONSE_JSON_PRETTY => false,
            Constants::PARAM_SCHEMA_CLASS => 'NonExistentSchema',
            Constants::PARAM_SCHEMA_SERVICE => null,
        ]);

        $request = Request::create('/graphql', 'POST', [], [], [], [
            'CONTENT_TYPE' => Constants::CONTENT_TYPE_JSON,
        ], json_encode(['query' => '{ hello }']));

        $requestStack->push($request);
        $container->set(Constants::SERVICE_REQUEST_STACK, $requestStack);

        $controller = new GraphQLController($parameterBag);
        $controller->setContainer($container);

        $response = $controller->defaultAction();

        // Should return 500 status code
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());

        // Response should not expose internal schema class name
        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('message', $responseData[0]);
        $this->assertStringNotContainsString('NonExistentSchema', $responseData[0]['message']);
        // Should have generic error message
        $this->assertStringContainsString('error', strtolower((string) $responseData[0]['message']));
    }

    public function testResponseHeadersAreConfigurable(): void
    {
        // Response headers should be included from configuration
        $requestStack = new RequestStack();
        $container = new Container();

        $customHeaders = [
            'Content-Type' => 'application/json',
            'X-Custom-Header' => 'custom-value',
            'Access-Control-Allow-Origin' => 'https://example.com',
        ];

        $parameterBag = new ParameterBag([
            Constants::PARAM_RESPONSE_HEADERS => $customHeaders,
            Constants::PARAM_RESPONSE_JSON_PRETTY => false,
            Constants::PARAM_SCHEMA_CLASS => 'NonExistentSchema',
            Constants::PARAM_SCHEMA_SERVICE => null,
        ]);

        $request = Request::create('/graphql', 'POST', [], [], [], [
            'CONTENT_TYPE' => Constants::CONTENT_TYPE_JSON,
        ], json_encode(['query' => '{ hello }']));

        $requestStack->push($request);
        $container->set(Constants::SERVICE_REQUEST_STACK, $requestStack);

        $controller = new GraphQLController($parameterBag);
        $controller->setContainer($container);

        $response = $controller->defaultAction();

        // Check that configured headers are present in response
        foreach ($customHeaders as $headerName => $headerValue) {
            $this->assertTrue(
                $response->headers->has($headerName),
                sprintf('Expected header "%s" to be present in response', $headerName)
            );
        }
    }
}
