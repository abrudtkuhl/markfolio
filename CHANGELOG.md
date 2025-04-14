# Changelog

All notable changes to `abrudtkuhl/markfolio` will be documented in this file.

## 1.0.0 - 2024-03-20

- Initial release
- Markdown rendering with front matter support
- Custom layouts per page
- Built-in caching
- Laravel Folio integration
- Artisan command for creating pages

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial package structure
- Laravel Folio integration for file-based routing with Markdown
- PageParser for converting Markdown to HTML with front matter support
- MarkdownRenderer middleware to process markdown files through Folio
- Artisan `make:markdown-page` command for generating new pages
- Configuration options for content directory and caching
- ContentManager for querying and filtering Markdown pages
- Import functionality for HTML, WordPress, URLs, and CSV data
- Custom blade view for rendering markdown content

### Changed
- N/A

### Fixed
- N/A 