<?php

declare(strict_types=1);

namespace Youshido\GraphQLBundle\Config;

/**
 * Service and parameter name constants used throughout the GraphQL bundle.
 * 
 * Centralizing these constants reduces magic strings and makes refactoring easier.
 */
class Constants
{
    // Service names
    public const SERVICE_GRAPHQL_PROCESSOR = 'graphql.processor';
    public const SERVICE_GRAPHQL_SCHEMA = 'graphql.schema';
    public const SERVICE_REQUEST_STACK = 'request_stack';

    // Parameter names
    public const PARAM_SCHEMA_CLASS = 'graphql.schema_class';
    public const PARAM_SCHEMA_SERVICE = 'graphql.schema_service';
    public const PARAM_LOGGER = 'graphql.logger';
    public const PARAM_MAX_COMPLEXITY = 'graphql.max_complexity';
    public const PARAM_RESPONSE_JSON_PRETTY = 'graphql.response.json_pretty';
    public const PARAM_RESPONSE_HEADERS = 'graphql.response.headers';
    public const PARAM_SECURITY_GUARD_CONFIG = 'graphql.security.guard_config';
    public const PARAM_SECURITY_BLACK_LIST = 'graphql.security.black_list';
    public const PARAM_SECURITY_WHITE_LIST = 'graphql.security.white_list';

    // Content-Type header values
    public const CONTENT_TYPE_GRAPHQL = 'application/graphql';
    public const CONTENT_TYPE_JSON = 'application/json';
    public const HEADER_CONTENT_TYPE = 'Content-Type';

    // HTTP methods
    public const HTTP_METHOD_OPTIONS = 'OPTIONS';
    public const HTTP_METHOD_GET = 'GET';
    public const HTTP_METHOD_POST = 'POST';

    private function __construct()
    {
        // Constants class - no instantiation
    }
}
