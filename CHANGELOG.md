# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.2 [2025-11-13]

### Added

- Add explicit support for PHP 8.5 in composer.json constraints.
- [Development Docker Image] Add `PHP_VERSION` and `WITH_XDEBUG` environment variables and build args to make Docker image more flexible.

### Changed

- Remove composer.json repository override for 'phoneburner/php-coding-standard' (as it is now available on Packagist)
- [Development Docker Image] Switch from PECL to PIE for installing PHP extensions.
- [Development Docker Image] Optional Xdebug extension is no longer installed by default.
- [Development Docker Image] Install git, fixing Composer root-version warning message.

### Fixed

- Fix whitespace issues in .gitattributes

## [1.0.1] - 2025-07-30

### Added

- This changelog.

[1.0.0]: https://github.com/phoneburner/api-handler/releases/tag/v1.0.1

## [1.0.0] - 2025-07-30

### Added

- Initial stable release of the API Handler library
- Support for PHP 8.4 alongside 8.2 and 8.3
- CRUD operations through dedicated handlers:
    - CreateHandler for resource creation
    - ReadHandler for resource retrieval
    - UpdateHandler for resource modification
    - DeleteHandler for resource removal
- Response transformation capabilities with TransformableResponse
- PSR-7 and PSR-15 compliance for HTTP message interfaces and server request handlers
- Middleware support through DispatchMiddleware
- Flexible response factories

### Changed

- Updated for Tortilla 2 compatibility with static returns
- Modified response handling to return mutated instances instead of the same instance
- Using the develop branch for HTTP Tortilla dependency

### Fixed

- Allow null return for all hydrator calls
- Fixed PHPStan return annotations to properly handle null values
- Resolved static analysis issues with PHPStan
- Addressed code quality issues with Rector

### Security

- Initial security review completed for v1.0.0 release

[1.0.0]: https://github.com/phoneburner/api-handler/releases/tag/v1.0.0
