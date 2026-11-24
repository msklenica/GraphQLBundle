# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Security

- **Security**: Replaced bundled GraphiQL 0.9.2 (`Resources/public/graphiql.min.js`) with GraphiQL **1.4.7** and React/ReactDOM **17.0.2** production UMD builds. Fixes CVE-2021-41248 (schema template injection in GraphiQL < 1.4.7) in the explorer template `Resources/views/Feature/explorer.html.twig`.
- Added `.gitattributes` excluding `Tests/` from Composer dist archives; `Resources/public` stays packaged.

### Fixed

- **Security**: Fixed batch query state mutation in PayloadParser where variables from one query could leak to subsequent queries in batch requests (Issue #2)
- **Security**: Fixed HTTP 500 error responses returning 200 status code and exposing internal schema class names in error messages (Issue #20)
- **Security**: Added descriptive context to AccessDeniedException messages including field and operation names for better debugging (Issues #4, #19)

### Added

- **Security**: Added configurable maximum payload size limit (10MB default) to prevent denial-of-service attacks via large JSON payloads (Issue #18)
- **Security**: Enhanced default CORS headers with support for Authorization header and proper Access-Control-Max-Age (Issue #18)
- **Code Quality**: Created Constants class (Config/Constants.php) to centralize all magic strings for improved maintainability (Smell #2)
- **Testing**: Added comprehensive integration tests for GraphQLController covering single queries, batch queries, different content types, error handling, and CORS support (Issue #17)

### Changed

- **Code Quality**: Consolidated duplicate variable parsing methods in PayloadParser into single `parseVariables()` method (Smell #1)
- **Code Quality**: Updated GraphQLController and PayloadParser to use Constants class for all service and parameter names
- **Refactoring**: Removed Symfony 4.2 compatibility code (KernelVersionHelper class) since bundle now requires Symfony 7.4+ (Issue #3)
- **Refactoring**: Fixed parameter name typo in Processor::setSecurityManager() - `$securityManger` → `$securityManager` (Issue #16)

### Improved

- **Documentation**: Added inline documentation for new security features and payload validation
- **Documentation**: Enhanced PHPDoc blocks for PayloadParser and GraphQLController methods with parameter and return type details

### Removed

- Removed KernelVersionHelper class (was checking Symfony 4.2 compatibility, no longer needed)
- Removed KernelVersionHelperTest test case (associated with removed helper)

---

## [2.0.2] - 2026-11-24

### Security

- Upgraded the bundled GraphiQL explorer from 0.9.2 to 1.4.1, fixing CVE-2021-41248 (XSS in GraphiQL < 1.4.1).
- Upgraded the bundled React assets from 15.0.1 to 17.0.2, as required by GraphiQL 1.4.1.

## [1.0.0] - 2024-XX-XX (Previous Release)

### Added

- Initial release of GraphQL Bundle for Symfony 7.4+
- GraphQL request processing with query and batch query support
- Security voters for field and operation-level access control
- Request payload parsing with support for multiple content types
- Response header customization via configuration
- Event dispatching for GraphQL query resolution

### Features

- Full GraphQL server integration with Symfony Framework
- Support for single and batch queries
- Security manager for access control
- Customizable field and operation authorization
- Event-driven architecture for query resolution
- Logging support for GraphQL queries
