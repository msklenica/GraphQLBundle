<?php

declare(strict_types=1);

namespace Youshido\GraphQLBundle\Tests\Execution\Payload;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Youshido\GraphQLBundle\Execution\Payload\PayloadParser;

class PayloadParserTest extends TestCase
{
    public function testParseQueryFromUrlParameters(): void
    {
        $request = Request::create('/?query={user{id}}');
        $parser = new PayloadParser($request);
        $result = $parser->parse();

        $this->assertFalse($result['isMultiQueryRequest']);
        $this->assertCount(1, $result['queries']);
        $this->assertEquals('{user{id}}', $result['queries'][0]['query']);
        $this->assertEmpty($result['queries'][0]['variables']);
    }

    public function testParseQueryWithVariablesFromUrl(): void
    {
        $variables = json_encode(['userId' => 123]);
        $request = Request::create("/?query={user(id:\$id){id}}&variables={$variables}");
        $parser = new PayloadParser($request);
        $result = $parser->parse();

        $this->assertFalse($result['isMultiQueryRequest']);
        $this->assertCount(1, $result['queries']);
        $this->assertEquals('{user(id:$id){id}}', $result['queries'][0]['query']);
        $this->assertEquals(['userId' => 123], $result['queries'][0]['variables']);
    }

    public function testParseApplicationGraphqlContentType(): void
    {
        $query = '{ user { id name } }';
        $request = Request::create('/', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/graphql'], $query);
        $parser = new PayloadParser($request);
        $result = $parser->parse();

        $this->assertFalse($result['isMultiQueryRequest']);
        $this->assertCount(1, $result['queries']);
        $this->assertEquals($query, $result['queries'][0]['query']);
        $this->assertEmpty($result['queries'][0]['variables']);
    }

    public function testParseApplicationJsonSingleQuery(): void
    {
        $payload = [
            'query' => '{ user { id } }',
            'variables' => ['userId' => 42],
        ];
        $request = Request::create(
            '/',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $parser = new PayloadParser($request);
        $result = $parser->parse();

        $this->assertFalse($result['isMultiQueryRequest']);
        $this->assertCount(1, $result['queries']);
        $this->assertEquals('{ user { id } }', $result['queries'][0]['query']);
        $this->assertEquals(['userId' => 42], $result['queries'][0]['variables']);
    }

    public function testParseApplicationJsonBatchQueries(): void
    {
        $payload = [
            ['query' => '{ user { id } }', 'variables' => ['userId' => 1]],
            ['query' => '{ posts { title } }', 'variables' => ['limit' => 10]],
        ];
        $request = Request::create(
            '/',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $parser = new PayloadParser($request);
        $result = $parser->parse();

        $this->assertTrue($result['isMultiQueryRequest']);
        $this->assertCount(2, $result['queries']);
        $this->assertEquals('{ user { id } }', $result['queries'][0]['query']);
        $this->assertEquals(['userId' => 1], $result['queries'][0]['variables']);
        $this->assertEquals('{ posts { title } }', $result['queries'][1]['query']);
        $this->assertEquals(['limit' => 10], $result['queries'][1]['variables']);
    }

    public function testParseJsonWithStringVariables(): void
    {
        $variables = json_encode(['userId' => 42]);
        $payload = [
            'query' => '{ user { id } }',
            'variables' => $variables,
        ];
        $request = Request::create(
            '/',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $parser = new PayloadParser($request);
        $result = $parser->parse();

        $this->assertFalse($result['isMultiQueryRequest']);
        $this->assertCount(1, $result['queries']);
        $this->assertEquals(['userId' => 42], $result['queries'][0]['variables']);
    }

    public function testParseEmptyRequest(): void
    {
        $request = Request::create('/');
        $parser = new PayloadParser($request);
        $result = $parser->parse();

        $this->assertFalse($result['isMultiQueryRequest']);
        $this->assertCount(1, $result['queries']);
        $this->assertNull($result['queries'][0]['query']);
        $this->assertEmpty($result['queries'][0]['variables']);
    }

    public function testParseInvalidJsonFallsBackToUrlParameters(): void
    {
        $request = Request::create(
            '/?query={user{id}}',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'not valid json'
        );
        $parser = new PayloadParser($request);
        $result = $parser->parse();

        $this->assertFalse($result['isMultiQueryRequest']);
        $this->assertCount(1, $result['queries']);
        $this->assertEquals('{user{id}}', $result['queries'][0]['query']);
    }

    public function testParseUrlVariablesAsArrayParameter(): void
    {
        $variables = json_encode(['key' => 'value']);
        $request = Request::create("/?variables={$variables}");
        $parser = new PayloadParser($request);
        $result = $parser->parse();

        $this->assertFalse($result['isMultiQueryRequest']);
        $this->assertEquals(['key' => 'value'], $result['queries'][0]['variables']);
    }

    public function testParseInvalidVariablesJsonInUrl(): void
    {
        $request = Request::create('/?variables=invalid-json');
        $parser = new PayloadParser($request);
        $result = $parser->parse();

        $this->assertFalse($result['isMultiQueryRequest']);
        $this->assertEmpty($result['queries'][0]['variables']);
    }

    public function testParseBatchQueryWithMissingQuery(): void
    {
        $payload = [
            ['query' => '{ user { id } }', 'variables' => ['userId' => 1]],
            ['variables' => ['limit' => 10]], // Missing query, should be null (not use previous)
        ];
        $request = Request::create(
            '/',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $parser = new PayloadParser($request);
        $result = $parser->parse();

        $this->assertTrue($result['isMultiQueryRequest']);
        $this->assertCount(2, $result['queries']);
        // Query is missing, so it should be null (not reuse previous query)
        $this->assertNull($result['queries'][1]['query']);
        $this->assertEquals(['limit' => 10], $result['queries'][1]['variables']);
    }

    public function testParseBatchQueryWithUrlParameterFallback(): void
    {
        $variables = json_encode(['default' => true]);
        $payload = [
            ['query' => '{ user { id } }'],
            ['query' => '{ posts { title } }'],
        ];
        $request = Request::create(
            '/?variables=' . urlencode($variables),
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $parser = new PayloadParser($request);
        $result = $parser->parse();

        $this->assertTrue($result['isMultiQueryRequest']);
        $this->assertCount(2, $result['queries']);
        // URL parameter variables should only apply to first query, not subsequent ones
        $this->assertEquals(['default' => true], $result['queries'][0]['variables']);
        $this->assertEquals([], $result['queries'][1]['variables']);
    }

    public function testParseApplicationJsonBatchQueriesWithoutExplicitVariables(): void
    {
        $payload = [
            ['query' => '{ user { id } }'],
            ['query' => '{ posts { title } }'],
        ];
        $request = Request::create(
            '/',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $parser = new PayloadParser($request);
        $result = $parser->parse();

        $this->assertTrue($result['isMultiQueryRequest']);
        $this->assertCount(2, $result['queries']);
        $this->assertEmpty($result['queries'][0]['variables']);
        $this->assertEmpty($result['queries'][1]['variables']);
    }

    public function testParseComplexNestedVariables(): void
    {
        $variables = [
            'userId' => 42,
            'filters' => [
                'status' => 'active',
                'tags' => ['graphql', 'php'],
            ],
        ];
        $payload = [
            'query' => '{ user(id: $userId) { id } }',
            'variables' => $variables,
        ];
        $request = Request::create(
            '/',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $parser = new PayloadParser($request);
        $result = $parser->parse();

        $this->assertFalse($result['isMultiQueryRequest']);
        $this->assertEquals($variables, $result['queries'][0]['variables']);
    }

    public function testParseIgnoresNonArrayItemsInBatch(): void
    {
        $payload = [
            ['query' => '{ user { id } }'],
            'not an array', // Should be skipped
            ['query' => '{ posts { title } }'],
        ];
        $request = Request::create(
            '/',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $parser = new PayloadParser($request);
        $result = $parser->parse();

        // Should process only the array items, not the string
        $this->assertTrue($result['isMultiQueryRequest']);
        $this->assertCount(2, $result['queries']);
    }
}
